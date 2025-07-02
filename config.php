<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'abdillah24'); // Ganti dengan password database Anda
define('DB_NAME', 'dokumen');

class Database {
    private $connection;
    
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
    
    public function getConnection() {
        return $this->connection;
    }
}

// Utility functions
function sendResponse($success, $message, $data = null) {
    $response = array(
        "success" => $success,
        "message" => $message
    );
    
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    echo json_encode($response);
    exit();
}

function validateInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return "IMG_" . time() . "_" . uniqid() . "." . $extension;
}
?>