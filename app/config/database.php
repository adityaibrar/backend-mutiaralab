<?php

require_once('config.php');

class Database {
    private $connection;
    private $stmt; // statement
    private $dbh; // database handler
    
    public function __construct() {
        $this->connection = null;
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo json_encode(array(
                "success" => false,
                "message" => "Connection error: " . $exception->getMessage()
            ));
            exit();
        }
    }

    public function bind($params, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value);
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value);
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value);
                    $type = PDO::PARAM_NULL;
                    break;
                default: 
                    $type = PDO::PARAM_STR;
            }
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }

    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function execute() {
        $this->stmt->execute();
    }

    public function getAll() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOne() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }
}


?>
