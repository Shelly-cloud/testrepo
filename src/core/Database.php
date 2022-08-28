<?php
//https://www.cloudways.com/blog/crud-with-php-data-objects/
require_once __dir__."/../config/config.php";

class Database
{
    private  $server = "mysql:host=".DB_HOST.";dbname=".DB_NAME;
    private  $user = DB_USER;
    private  $pass = DB_PASSWORD;
    private $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
    protected $con;
    public function openConnection()
    {
        try {
            $this->con = new PDO($this->server, $this->user, $this->pass, $this->options);
            return $this->con;
        } catch (PDOException $e) {

            echo "There is some problem in connection: " . $e->getMessage();
        }
    }

    public function closeConnection()
    {

        $this->con = null;
    }
}
