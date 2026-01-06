<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Controllers\SaveController;

// public routes
Router::add("GET", "/", function () {
    echo json_encode(["status" => true, "message" => "success"]);
}, true);
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
}, true);


// users
Router::add("GET", "/api/get-user", function () {
    UserController::getUser();
},true);

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

Router::add("GET", "/api/get-post/{post_id}", function ($post_id) {
    PostController::getPostsByPostId($post_id);
}, true);

Router::add("POST", "/api/create-post", function () {
    PostController::createPost();
}, true); // create post

Router::add("POST", "/api/react-post", function () {
    PostController::reactPost();
}, true); // insert react

Router::add("POST", "/api/comment-post", function () {
    PostController::commentPost();
}, true); // insert comment

Router::add("POST", "/api/delete-comment", function () {
    PostController::commentDelete();
}, true); // delete comment

Router::add("GET", "/api/get-comment", function () {
    PostController::getComments();
}, true); // get Comments

Router::add("POST", "/api/delete-post", function () {
    PostController::postDelete();
}, true); // delete post

// Router::add("POST", "/api/save-post", function () {
// SaveController::savePost();
// }, true); // save post

Router::add("POST", "/api/create-saved-list", function () {
SaveController::createSavedLists();
}, true); // create saved lists

Router::add("POST", "/api/save-post", function () {
SaveController::createSavedPosts();
}, true); // create saved posts

Router::add("GET", "/api/get-saved-lists", function () {
SaveController::getSavedLists();
}, true); // get Saved Lists

Router::add("GET", "/api/get-saved-posts", function () {
SaveController::getSavedPosts();
}, true); // get Saved Posts

Router::add("GET", "/api/get-friends-posts", function () {
PostController::getPostsByFriends();
}, true); // get friens posts




