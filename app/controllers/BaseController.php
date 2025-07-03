<?php

require_once("");

abstract class BaseController {
    protected function sendResponse($success, $message, $data = null) {
        // kalau error headernya tarok sini
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }
        
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

    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
    
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg()
            ]);
            exit();
        }
    }

    protected function validateInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    protected function generateFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return "IMG_" . time() . "_" . uniqid() . "." . $extension;
    }
}


?>