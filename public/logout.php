<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\AuthService;

$db = Database::getInstance()->getConnection();
$auth = new AuthService($db);
$auth->logout();

header('Location: login.php');
exit;