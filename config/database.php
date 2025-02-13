<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'foodhub';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }

        return $this->conn;
    }
}
?>

<?php

function getDatabaseConnection() {

    $host = 'localhost';

    $username = 'root';

    $password = '';

    $database = 'foodhub';

    

    $conn = new mysqli($host, $username, $password, $database);

    

    if ($conn->connect_error) {

        die("Connection failed: " . $conn->connect_error);

    }

    

    return $conn;

}
