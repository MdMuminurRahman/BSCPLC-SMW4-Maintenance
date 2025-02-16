<?php
require_once '../app/controllers/Controller.php';
require_once '../app/controllers/ApiController.php';
require_once '../app/models/Database.php';
require_once '../app/models/Maintenance.php';
require_once '../app/models/Logger.php';

// Initialize error handling
ErrorHandler::init();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create API controller instance and handle the request
$apiController = new ApiController();
$apiController->handleRequest();