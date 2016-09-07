<?php

class Database
{
    public static function StartTransaction() {
        self::getConn()->begin_transaction();
    }

    /* @return bool */
    public static function CommitTransaction() {
        //returns true on success
        return self::getConn()->commit();
    }
    public static function Rollback() {
        self::getConn()->rollback();
    }

    /* @param $sql string
     * @return array
     */
    public static function QueryArray($sql) {
        $result = self::getConn()->query($sql);
        if ($result) {
            $ret = $result->fetch_all(MYSQLI_ASSOC);
            $result->close();
            return $ret;
        } else {
            return array();
        }
    }
    /* @param $sql string
     * @param $rowCallback Callable
     * @return int
     */
    public static function QueryCallback($sql, Callable $rowCallback)
    {
        $count = 0;
        $result = self::getConn()->query($sql, MYSQLI_ASSOC);

        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $rowCallback($row);
            $count++;
        }
        return $count;
    }

    /* @param $sql string
     * @return string[]
     */
    public static function QueryOneRow($sql)
    {
        $result = self::getConn()->query($sql);
        if ($result) {
            $ret = $result->fetch_assoc();
            $result->close();
            return $ret;
        } else {
            return null;
        }
    }


    /* @param $sqlWithoutSelect string
     * @return bool
     */
    public static function RecordExists($sqlWithoutSelect)
    {
        $sql = "SELECT EXISTS(SELECT 1 FROM $sqlWithoutSelect)";
        $result = self::getConn()->query($sql);
        if ($result)
            return ((int)$result->fetch_row()[0]) > 0;
        else
            return false;
    }

    /* @param $sqlWithoutSelect string
     * @return int
     */
    public static function RecordCount($sqlWithoutSelect)
    {
        $sql = "SELECT COUNT(*) AS count FROM $sqlWithoutSelect";
        $result = self::getConn()->query($sql);
        if ($result)
            return (int)($result->fetch_row()[0]);
        else
            return 0;
    }

    /* @param $sql string
     * @param $types string
     * @param $params array
     * @param $insertId int
     * @return int
     */
    public static function ExecutePreparedStatement($sql, $types, array $params, &$insertId = 0)
    {
        $typesAndParams = array();
        $typesAndParams[] = &$types;

        $n = count($params);
        for ($i = 0; $i < $n; $i++) { // with call_user_func_array, array params must be passed by reference
            $typesAndParams[] = &$params[$i];
        }

        $rowsAffected = 0;
        $stmt = self::getConn()->stmt_init();

        if ($stmt->prepare($sql)) {
            call_user_func_array(array($stmt, 'bind_param'), $typesAndParams);
            $stmt->execute();
            $rowsAffected = $stmt->affected_rows;
            $insertId = $stmt->insert_id;
            $stmt->close();
        }
        return $rowsAffected;
    }

    /* @param $sql string
     * @return int
     */
    public static function ExecuteQuery($sql)
    {
        $c = self::getConn();
        $c->query($sql);
        return $c->affected_rows;
    }

    /* @param $tableName string
     * @param $lookupBy string
     * @param $lookupByValue
     * @param $whereType string
     * @param $lookup string
     * @param $default
     * @return string
     */
    public static function LookupValue($tableName, $lookupBy, $lookupByValue, $whereType, $lookup, $default)
    {
        $sql = "SELECT $lookup FROM $tableName WHERE $lookupBy=?";
        if ($stmt = self::getConn()->prepare($sql)) {
            $stmt->bind_param($whereType, $lookupByValue);
            $stmt->execute();
            $stmt->bind_result($result);
            $stmt->fetch();
            $stmt->close();
            if (!is_null($result))
                return $result;
        }
        return $default;
    }

    private static $conn = null;

    /* @return mysqli */
    private static function getConn()
    {
        try {
            if (is_null(self::$conn)) {
                $dbName = 'memofrogdb';
                $username = 'memofrogdb';
                if (Session::IsProduction()) {
                    $host = 'mysql.memofrog.com';
                    /** @noinspection SpellCheckingInspection */
                    $password = 'T6tccsY2JX1a';
                } else {
                    $host = 'localhost';
                    $password = '123';
                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                }
                self::$conn = new mysqli($host, $username, $password, $dbName);
                self::$conn->query("SET time_zone = '+0:00';");
                self::$conn->set_charset('utf8mb4');
                if (self::$conn->connect_error)
                    die("Connection failed:<br>" . self::$conn->connect_error);
            }
            return self::$conn;
        } catch (Exception $e) {
            Util::LogTextFile('errors.txt', "Exception in getConn: " . strval($e));
            return null;
        }
    }
}