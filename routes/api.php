<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\public\auth\post;
// public routes
Router::add("GET", "/", function () {
    AuthController::index();
}, false);
Router::add("POST", "/auth/login", function () {
    AuthController::login();
}, false);
Router::add("POST", "/auth/register", function () {
    AuthController::register();
}, false);
Router::add("POST", "/auth/register/{username}", function ($username) {
    AuthController::register($username);
}, true);

Router::add("POST", "/auth/refresh", function () {
    AuthController::refresh();
}, false);

Router::add("POST", "/api/postOutput", function () {
    PostController::postOutput(); }, false);

// protected routes
Router::add("GET", "/api/profile", function () {
    AuthController::index();
}, true);