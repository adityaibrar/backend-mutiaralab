<?php

require_once("config/database.php");
require_once("helper/helpers.php");

class User {
    private $table = "users";
    private $db;


    public function __construct()
    {
        $this->db = new Database;
    }


    public function getUserByUsername($username) {
        $this->db->query("SELECT id, username, password FROM ". $this->table ." WHERE username = :username");
        $this->db->bind(':username', $username);
        return $this->db->getOne();

    }

    public function createUser($username, $password) {
        
        
        $this->db->query("INSERT INTO users (username, password) VALUES (:username, :password)");
        $this->db->bind(':username', $username);
        $this->db->bind(":password", $password);
        $this->db->execute();

        return $this->db->rowCount();
    }

    
}



?>