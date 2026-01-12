<?php
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\FriendController;
use App\Controllers\UserController;
use App\Controllers\SaveController;
use App\Controllers\SearchController;
use App\Core\Auth;

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
}, false);


// users
Router::add("GET", "/api/get-user", function () {
    UserController::getUser();
}, true);

Router::add("GET", "/api/profile", function () {
    AuthController::index();
}, true);


// // posts
// Router::add("GET", "/api/get-posts", function () {
//     PostController::getPosts();
// }, true); // get all posts

Router::add("GET", "/api/get-user-post/{username}", function ($username) {
    PostController::getPostsByUsername($username);
}, true); // get posts by user

Router::add("GET", "/api/get-following-post", function () {
    PostController::getFollowingPosts();
}, true); // get following posts

Router::add("GET", "/api/get-friends-posts", function () {
    PostController::getPostsByFriends();
}, true); // get friens posts

Router::add("GET", "/api/get-post", function () {
    PostController::getPostsByPostId();
}, true);

Router::add("POST", "/api/create-post", function () {
    PostController::createPost();
}, true); // create post

Router::add("GET", "/api/edit-post", function () {
    PostController::editPost();
}, true); // edit post

Router::add("GET", "/api/edit-post-privacy", function () {
    PostController::editPostPrivacy();
}, true); // edit post by privacy

Router::add("POST", "/api/react-post", function () {
    PostController::reactPost();
}, true); // insert react

Router::add("POST", "/api/comment-post", function () {
    PostController::commentPost();
}, false); // insert comment

Router::add("GET", "/api/get-postAll", function () {
    PostController::getPosts();
}, false); // retrun all posts


Router::add("DELETE", "/api/delete-comment", function () {
    PostController::commentDelete();
}, true); // delete comment

Router::add("GET", "/api/get-comment/{post_id}", function ($post_id) {
    PostController::getComments($post_id);
}, true); // get Comments

Router::add("DELETE", "/api/delete-post", function () {
    PostController::postDelete();
}, true); // delete post

Router::add("GET", "/api/get-friends", function () {
    FriendController::getFriends();
}, true);
Router::add("POST", "/api/send-request", function () {
    FriendController::sendFriendRequest();
}, true);//send friend requent
Router::add("POST", "/api/response-request", function () {
    FriendController::responseFriendRequest();
}, true);//accept,reject,cancel friend request
Router::add("GET", "/api/get-sent-requests", function () {
    FriendController::getFriendRequest();
}, true);//get Friend Request
Router::add("GET", "/api/get-received-requests", function () {
    FriendController::getReceivedRequests();
}, true);
Router::add("GET", "/api/get-people-you-may-know", function () {
    FriendController::peopleYouMayKnow();
}, protected: true);



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

Router::add("GET", "/api/get-saved-posts/{list_id}", function ($list_id) {
    SaveController::getSavedPosts($list_id);
}, true); // get Saved Posts

Router::add("GET", "/api/update-saved-posts", function () {
    SaveController::updateSavedPosts();
}, true); // update saved posts

Router::add("GET", "/api/delete-saved-posts", function () {
    SaveController::deleteSavedPosts();
}, true); // delete saved posts

// Router::add("POST", "/auth/generateOTP", function () {
// AuthController::generateOTP();
// }, true); // generate otp 

// Router::add("POST", "/auth/verifyOTP", function () {
// AuthController::verifyOTP();
// }, true); // verify otp

// Router::add("POST", "/auth/send-email", function () {
// AuthController::sendEmail();
// }, false); // send email

Router::add("POST", "/auth/forget-password", function () {
    AuthController::forgetPassword();
}, true); // forget password

Router::add("POST", "/auth/reset-password", function () {
    AuthController::resetPassword();
}, true); // reset password

Router::add("POST", "/api/search", function () {
SearchController::search();
}, true); // search 

