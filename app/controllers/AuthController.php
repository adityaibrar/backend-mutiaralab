<?php



class AuthController {
    private $userModel;
    private $db; 


    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User($this->db);
    }

    public function register() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['username']) || !isset($input['password'])) {
            Response::json(false, "Username and password are required", 400);
        }

        $username = validateInput($input["username"]);
        $password = validateInput($input["password"]);

        if (empty($username) || empty($password)) {
            Response::json(false, "Username and password cannot be empty", 400);
        }
    
        if (strlen($username) < 3) {
            Response::json(false, "Username must be at least 3 characters long", 400);
        }

        if (strlen($password) < 6) {
            Response::json(false, "Password must be at least 6 characters long", 400);
        }

        if($this->userModel->getUserByUsername($username)) {
            Response::json(false, "Username already exists", 400);
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $this->userModel->createUser($username, $hashedPassword);
            if($userId > 0) {
                Response::json(true, "Registration successful", 200, array(
                    "user_id" => $userId,
                    "username" => $username
                ));
            } else {
                Response::json(false, "Registration failed", 400);
            }
        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }    

    }

    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['username']) || !isset($input['password'])) {
            Response::json(false, "Username and password are required", 400);
        }

        $username = validateInput($input["username"]);
        $password = validateInput($input["password"]);


        if (empty($username) || empty($password)) {
            Response::json(false, "Username and password cannot be empty", 400);
        }

        try {
            // cobak kasih is_empty atau is_null nanti
            if($this->userModel->getUserByUsername($username)) {
                $user = $this->userModel->getUserByUsername($username);

                if(password_verify($password, $user["password"])) {
                    $sessionToken = bin2hex(random_bytes(32));

                    Response::json(true, "Login successful", 200, array(
                        "user_id" => $user['id'],
                        "username" => $user['username'],
                        "session_token" => $sessionToken
                    ));
                } else {
                    Response::json(false, "Invalid username or password", 400);
                }
            } else {
                Response::json(false, "Invalid username or password", 400);
            }
        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }

        
    }
}



?>