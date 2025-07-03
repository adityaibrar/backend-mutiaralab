<?php
require_once '../app/config/config.php';
require_once '../app/core/route.php';
require_once '../app/core/Database.php';
require_once '../app/models/User.php';
require_once '../app/models/Document.php';
require_once '../app/core/Response.php';
require_once '../app/helpers/helpers.php';

$controller = isset($_GET['controller']) ? $_GET['controller'] : 'auth';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

Router::route(ucfirst($controller), $action);
