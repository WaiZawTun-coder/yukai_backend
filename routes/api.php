<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Controllers\SaveController;

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
}, false);

Router::add("POST", "/auth/refresh", function () {
    AuthController::refresh();
}, false);


// users
Router::add("GET", "/api/get-user", function () {
    UserController::getUser();
},false);

Router::add("GET", "/api/profile", function () {
    AuthController::index();
}, false);


// posts
Router::add("GET", "/api/get-post", function () {
    PostController::getPosts();
}, false); // get all posts

Router::add("GET", "/api/get-user-post", function () {
    PostController::getPostsByUserId();
}, false); // get posts by user

Router::add("GET", "/api/get-following-post", function () {
    PostController::getFollowingPosts();
}, false); // get following posts

Router::add("GET", "/api/get-post/{post_id}", function ($post_id) {
    PostController::getPostsByPostId($post_id);
}, false);

Router::add("POST", "/api/create-post", function () {
    PostController::createPost();
}, false); // create post

Router::add("POST", "/api/react-post", function () {
    PostController::reactPost();
}, false); // insert react

Router::add("POST", "/api/comment-post", function () {
    PostController::commentPost();
}, false); // insert comment

Router::add("POST", "/api/delete-comment", function () {
    PostController::commentDelete();
}, false); // delete comment

Router::add("GET", "/api/get-comment", function () {
    PostController::getComments();
}, false); // get Comments

Router::add("POST", "/api/delete-post", function () {
    PostController::postDelete();
}, false); // delete post

Router::add("GET", "/api/save-post", function () {
SaveController::savePost();
}, false); // save post

