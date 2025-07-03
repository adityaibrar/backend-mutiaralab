<?php
class Router {
    public static function route($controller, $action) {
        $controllerFile = "../app/controllers/" . $controller . "Controller.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerClass = $controller . "Controller";
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $action)) {
                    $controllerInstance->$action();
                } else {
                    self::error("Action not found");
                }
            } else {
                self::error("Controller class not found");
            }
        } else {
            self::error("Controller file not found");
        }
    }

    private static function error($message) {
        require_once 'Response.php';
        Response::json(false, $message, 400);
    }
}
