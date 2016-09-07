<?php

class TDatabaseTimeSync extends Test
{
    function __construct()
    {
        $this->name = 'Database / Webserver Time Sync';
    }

    public function Run()
    {
        /** @noinspection SqlResolve */
        Database::ExecuteQuery("DROP TABLE junk");

        $sql = "CREATE TABLE IF NOT EXISTS junk (id int(10) unsigned NOT NULL AUTO_INCREMENT,
                                           created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           txt varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                           PRIMARY KEY (id)
                                           )
                ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1";

        Database::ExecuteQuery($sql);
        /** @noinspection SqlResolve */
        Database::ExecuteQuery("INSERT INTO junk (txt) VALUES ('foo')");
        /** @noinspection SqlResolve */
        $row = Database::QueryOneRow("SELECT * FROM junk");

        $createdString = $row['created'];
        $created = strtotime($createdString);

        /** @noinspection SqlResolve */
        Database::ExecuteQuery("DROP TABLE junk");

        $nowString = Util::GetNowAsString();
        $now = strtotime($nowString);

        $diff = abs($created - $now);

        //$this->result .= "FrogDB Time: $createdString / Web Server Time: $nowString<br>";

        $this->assertIsTrue('A', $diff < 30, "FrogDB / Web server time diff: $diff seconds<br>FrogDB: $createdString<br>Web Server: $nowString");

        $this->finished = true;

    }

    public function OkForProduction()
    {
        return true;
    }
}
