<?php
require_once '../app/config/config.php';
require_once '../app/core/route.php';

$controller = isset($_GET['controller']) ? $_GET['controller'] : 'auth';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

Router::route(ucfirst($controller), $action);
