<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\UserController;

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

Router::add("GET", "/api/getPost", function () {
    PostController::getPosts(); },true);

Router::add("GET", "/api/getUser", function () {
    UserController::user(); },true);

Router::add("GET", "/api/getUserPost", function () {
    PostController::getPostsByUserId(); },true); 

Router::add("GET", "/api/getFollowingpost", function () {
    PostController::getFollowingPosts(); }, true);   

// protected routes
Router::add("GET", "/api/profile", function () {
    AuthController::index();
}, true);