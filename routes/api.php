<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\UserController;

// public routes
Router::add("GET", "/", function () {
    echo json_encode(["status" => true, "message" => "success"]);
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


<<<<<<< HEAD
Router::add("GET", "/api/getUser", function () {
    UserController::user(); },true);

Router::add("GET", "/api/getUserPost", function () {
    PostController::getPostsByUserId(); },true); 

Router::add("GET", "/api/getFollowingpost", function () {
    PostController::getFollowingPosts(); }, true);   

// protected routes
=======
// users
Router::add("GET", "/api/get-user", function () {
    UserController::getUser();
}, true);

>>>>>>> 0806f4fc5b9363677a37b44eb84c3c12e7ce00ed
Router::add("GET", "/api/profile", function () {
    AuthController::index();
}, true);


// posts
Router::add("GET", "/api/get-post", function () {
    PostController::getPosts();
}, true); // get all posts

Router::add("GET", "/api/get-user-post", function () {
    PostController::getPostsByUserId();
}, true); // get posts by user

Router::add("GET", "/api/get-following-post", function () {
    PostController::getFollowingPosts();
}, true); // get following posts

Router::add("POST", "/api/create-post", function () {
    PostController::createPost();
}, true); // create post
