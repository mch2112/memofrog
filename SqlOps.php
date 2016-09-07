<?php

class SqlOps
{
    public static function DoOps()
    {
        // return empty string if nothing done

        /** @noinspection PhpUnusedLocalVariableInspection */
        $didSomething = false;

        // TODO: Drop effective_date fields in memos and history. Also some history fields: created, ip_at_create, ?.

        //Database::ExecuteQuery("ALTER TABLE tags DROP canonical_id");
        //Database::ExecuteQuery("ALTER TABLE shared_memo_status DROP priority");

        //self::ResetSortKeys();

//        Database::ExecuteQuery("DROP TABLE IF EXISTS transactions;");
//        Database::ExecuteQuery("CREATE TABLE transactions (id INT(10) UNSIGNED NOT NULL, guid VARCHAR(36) COLLATE UTF8MB4_BIN NOT NULL, user_id INT(10) UNSIGNED NOT NULL, created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, tries INT(11) NOT NULL DEFAULT 1 );");
//        Database::ExecuteQuery("ALTER TABLE transactions ADD PRIMARY KEY(id);");
//        Database::ExecuteQuery("ALTER TABLE transactions MODIFY id int(10) unsigned NOT NULL AUTO_INCREMENT;");
       // $didSomething = true;

        return $didSomething ? 'OK' : '';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function ResetSortKeys() {
        Database::ExecuteQuery('UPDATE shared_memo_status SET sort_key = NULL');
        ControllerW::UpdateSortKeys();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function grantUser()
    {
        if (!Session::IsProduction()) {
            Database::ExecuteQuery("GRANT ALL PRIVILEGES ON memofrogdb.* TO 'memofrogdb'@'localhost' IDENTIFIED BY '123';");
        }
    }
}