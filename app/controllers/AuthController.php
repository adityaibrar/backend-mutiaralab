<?php

require_once("helpers/helpers.php");

class AuthController extends BaseController {
    private $userModel;
    private $db; 
    private $conn;

    public function __construct()
    {
        $this->userModel = new User();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function register() {
        $input = $this->getJsonInput();

        if (!isset($input['username']) || !isset($input['password'])) {
            $this->sendResponse(false, "Username and password are required");
        }

        $username = validateInput($input["username"]);
        $password = validateInput($input["password"]);

        if (empty($username) || empty($password)) {
            $this->sendResponse(false, "Username and password cannot be empty");
        }
    
        if (strlen($username) < 3) {
            $this->sendResponse(false, "Username must be at least 3 characters long");
        }

        if (strlen($password) < 6) {
            $this->sendResponse(false, "Password must be at least 6 characters long");
        }

        if($this->userModel->getUserByUsername($username)) {
            $this->sendResponse(false, "Username already exists");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            if($this->userModel->createUser($username, $hashedPassword) > 0) {
                $userId = $this->conn->lastInsertId();
                $this->sendResponse(true, "Registration successful", array(
                    "user_id" => $userId,
                    "username" => $username
                ));
            } else {
                $this->sendResponse(false, "Registration failed");
            }
        } catch (PDOException $exception) {
            $this->sendResponse(false, "Database error: " . $exception->getMessage());
        }    

    }

    public function login() {
        $input = $this->getJsonInput();

        if (!isset($input['username']) || !isset($input['password'])) {
            $this->sendResponse(false, "Username and password are required");
        }

        $username = validateInput($input["username"]);
        $password = validateInput($input["password"]);


        if (empty($username) || empty($password)) {
            $this->sendResponse(false, "Username and password cannot be empty");
        }

        try {
            // cobak kasih is_empty atau is_null nanti
            if($this->userModel->getUserByUsername($username)) {
                $user = $this->userModel->getUserByUsername($username);

                if(password_verify($password, $user["password"])) {
                    $sessionToken = bin2hex(random_bytes(32));

                    $this->sendResponse(true, "Login successful", array(
                        "user_id" => $user['id'],
                        "username" => $user['username'],
                        "session_token" => $sessionToken
                    ));
                } else {
                    $this->sendResponse(false, "Invalid username or password");
                }
            } else {
                $this->sendResponse(false, "Invalid username or password");
            }
        } catch (PDOException $exception) {
            $this->sendResponse(false, "Database error: " . $exception->getMessage());
        }

        
    }
}



?>