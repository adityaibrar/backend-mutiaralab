<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'register':
        register($db);
        break;
    case 'login':
        login($db);
        break;
    case 'check_username':
        checkUsername($db);
        break;
    default:
        sendResponse(false, "Invalid action");
        break;
}

function register($db)
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['username']) || !isset($input['password'])) {
        sendResponse(false, "Username and password are required");
    }

    $username = validateInput($input['username']);
    $password = validateInput($input['password']);

    if (empty($username) || empty($password)) {
        sendResponse(false, "Username and password cannot be empty");
    }

    if (strlen($username) < 3) {
        sendResponse(false, "Username must be at least 3 characters long");
    }

    if (strlen($password) < 6) {
        sendResponse(false, "Password must be at least 6 characters long");
    }

    try {
        // Check if username already exists
        $checkQuery = "SELECT id FROM users WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            sendResponse(false, "Username already exists");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $insertQuery = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':password', $hashedPassword);

        if ($insertStmt->execute()) {
            $userId = $db->lastInsertId();
            sendResponse(true, "Registration successful", array(
                "user_id" => $userId,
                "username" => $username
            ));
        } else {
            sendResponse(false, "Registration failed");
        }
    } catch (PDOException $exception) {
        sendResponse(false, "Database error: " . $exception->getMessage());
    }
}

function login($db)
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['username']) || !isset($input['password'])) {
        sendResponse(false, "Username and password are required");
    }

    $username = validateInput($input['username']);
    $password = validateInput($input['password']);

    if (empty($username) || empty($password)) {
        sendResponse(false, "Username and password cannot be empty");
    }

    try {
        $query = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                // Generate simple session token (in production, use JWT or more secure method)
                $sessionToken = bin2hex(random_bytes(32));

                sendResponse(true, "Login successful", array(
                    "user_id" => $user['id'],
                    "username" => $user['username'],
                    "session_token" => $sessionToken
                ));
            } else {
                sendResponse(false, "Invalid username or password");
            }
        } else {
            sendResponse(false, "Invalid username or password");
        }
    } catch (PDOException $exception) {
        sendResponse(false, "Database error: " . $exception->getMessage());
    }
}

function checkUsername($db)
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['username'])) {
        sendResponse(false, "Username is required");
    }

    $username = validateInput($input['username']);

    try {
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $exists = $stmt->rowCount() > 0;

        sendResponse(true, "Username check completed", array(
            "exists" => $exists
        ));
    } catch (PDOException $exception) {
        sendResponse(false, "Database error: " . $exception->getMessage());
    }
}
