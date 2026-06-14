<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$controller = new App\Http\Controllers\AuthController();
echo method_exists($controller, 'showForgotPassword') ? 'Method exists' : 'Method not found';
echo "\n";
print_r(get_class_methods($controller));
?>