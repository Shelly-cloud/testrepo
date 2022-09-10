<?php
//https://www.cloudways.com/blog/crud-with-php-data-objects/
require_once __dir__."/../config/config.php";

class Database
{
    public static $host = "";
    public static $dbName = "";
    public static $user = "";
    public static $pass = "";
    public static $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

    public static function openConnection()
    {
        try {
            $con = new PDO("mysql:host=".self::$host.";dbname=".self::$dbName, self::$user, self::$pass, self::$options);
            return $con;
        } catch (PDOException $e) {
            echo "There is some problem in connection: " . $e->getMessage();
            Logger::error("[" . __FUNCTION__ . "] There is some problem in connection: Error : " . $e->getMessage());
        }
    }

    public static function closeConnection($con)
    {
        $con = null;
        return null;
    }
}
