<?php

use App\Controllers\AuthController;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Services\AuthService;

$userModel = new UserModel($conn);
$servidorModel = new ServidorModel($conn);
$authService = new AuthService($userModel, $servidorModel);

$authController = new AuthController($authService);

$router->add('GET', '/login', [$authController, 'showLogin']);
$router->add('GET', '/register', [$authController, 'showRegister']);

$router->add('POST', '/register', [$authController, 'register']);