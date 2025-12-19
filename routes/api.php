<?php
use App\Controllers\AuthController;

// public routes
Router::add("GET", "/", function() { AuthController::index(); }, false);
Router::add("POST", "/api/login", function() { AuthController::login(); }, false);
Router::add("POST", "/api/register", function() { AuthController::register(); }, false);

// protected routes
Router::add("GET", "/api/profile", function() { AuthController::index(); }, true);