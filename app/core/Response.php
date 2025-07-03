<?php
class Response {
    public static function json($success, $message, $statusCode, $data = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $res = ['success' => $success, 'message' => $message];
        if ($data !== null) $res['data'] = $data;
        echo json_encode($res);
        exit();
    }
}
