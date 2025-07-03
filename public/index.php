<?php 

require_once("app/init.php");

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

?>