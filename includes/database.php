<?php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = ""; 
    private $database = "dti_jocos_db";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("A database error occurred. Please try again later.");
        }

        return $this->conn;
    }
}

?>