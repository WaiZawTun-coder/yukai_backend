<?php

use App\Controllers\AuthController;
use App\Controllers\DeviceController;
use App\Controllers\FriendController;
use App\Controllers\PostController;
use App\Controllers\PostHidingController;
use App\Controllers\UserController;
use App\Controllers\SaveController;
use App\Controllers\ChatController;
use App\Controllers\MessageController;
use App\Controllers\SearchController;
use App\Controllers\reportController;
use App\Core\Auth;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Router::add(
    "GET",
    "/",
    fn() =>
    print json_encode(["status" => true, "message" => "success"]),
    false
);

Router::add(
    "POST",
    "/auth/login",
    fn() =>
    AuthController::login(),
    false
);

Router::add(
    "POST",
    "/auth/register",
    fn() =>
    AuthController::register(),
    false
);

Router::add(
    "POST",
    "/auth/register/{username}",
    fn($username) =>
    AuthController::register($username),
    true
);

Router::add(
    "POST",
    "/auth/refresh",
    fn() =>
    AuthController::refresh(),
    false
);

Router::add(
    "POST",
    "/auth/forget-password",
    fn() =>
    AuthController::forgetPassword(),
    false
);

Router::add(
    "POST",
    "/auth/reset-password",
    fn() =>
    AuthController::resetPassword(),
    false
);


/*
|--------------------------------------------------------------------------
| User & Profile
|--------------------------------------------------------------------------
*/

Router::add(
    "GET",
    "/api/get-user",
    fn() =>
    UserController::getUser(),
    true
);

Router::add(
    "GET",
    "/api/profile",
    fn() =>
    AuthController::index(),
    true
);

Router::add(
    "POST",
    "/api/edit-user",
    fn() =>
    UserController::editUser(),
    true
);

Router::add(
    "POST",
    "/api/request-password-otp",
    fn() =>
    UserController::requestPasswordOTP(),
    true
);

Router::add(
    "POST",
    "/api/change-password",
    fn() =>
    UserController::changepassword(),
    true
);

Router::add(
    "POST",
    "/api/deactivate-user",
    fn() =>
    UserController::deactivateUser(),
    true
);

Router::add(
    "POST",
    "/api/deleted-account",
    fn() =>
    UserController::deletedAccount(),
    true
);


/*
|--------------------------------------------------------------------------
| Posts
|--------------------------------------------------------------------------
*/

Router::add(
    "GET",
    "/api/get-posts",
    fn() =>
    PostController::getPosts(),
    true
);

Router::add(
    "GET",
    "/api/get-user-post/{username}",
    fn($username) =>
    PostController::getPostsByUsername($username),
    true
);

Router::add(
    "GET",
    "/api/get-following-post",
    fn() =>
    PostController::getFollowingPosts(),
    true
);

Router::add(
    "GET",
    "/api/get-friends-posts",
    fn() =>
    PostController::getPostsByFriends(),
    true
);

Router::add(
    "GET",
    "/api/get-post-by-postId",
    fn() =>
    PostController::getPostsByPostId(),
    true
);

Router::add(
    "POST",
    "/api/create-post",
    fn() =>
    PostController::createPost(),
    true
);

Router::add(
    "GET",
    "/api/edit-post",
    fn() =>
    PostController::editPost(),
    true
);

Router::add(
    "GET",
    "/api/edit-post-privacy",
    fn() =>
    PostController::editPostPrivacy(),
    true
);

Router::add(
    "POST",
    "/api/react-post",
    fn() =>
    PostController::reactPost(),
    true
);

Router::add(
    "POST",
    "/api/comment-post",
    fn() =>
    PostController::commentPost(),
    false
);

Router::add(
    "DELETE",
    "/api/delete-comment",
    fn() =>
    PostController::commentDelete(),
    true
);

Router::add(
    "GET",
    "/api/get-comment/{post_id}",
    fn($post_id) =>
    PostController::getComments($post_id),
    true
);

Router::add(
    "DELETE",
    "/api/delete-post",
    fn() =>
    PostController::postDelete(),
    true
);

Router::add(
    "POST",
    "/api/edit-history",
    fn() =>
    PostController::editHistory(),
    false
);

Router::add(
    "GET",
    "/api/get-edit-history",
    fn() =>
    PostController::getEditHistory(),
    true
);


/*
|--------------------------------------------------------------------------
| Post Hiding
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/hide-post",
    fn() =>
    PostHidingController::hidePost(),
    true
);

Router::add(
    "POST",
    "/api/unhide-post",
    fn() =>
    PostHidingController::unhidePost(),
    true
);
/*
|--------------------------------------------------------------------------
| Post Tag
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/tag-post",
    fn() =>
    PostController::tagPost(),
    true
);
Router::add(
    "GET",
    "/api/update-tag-post",
    fn() =>
    PostController::updateTagPost(),
    true
);
Router::add(
    "GET",
    "/api/delete-tag-post",
    fn() =>
    PostController::deleteTagPost(),
    true
);
Router::add(
    "GET",
    "/api/get-tag-post",
    fn() =>
    PostController::getTagPost(),
    true
);


/*
|--------------------------------------------------------------------------
| Friends / Follow
|--------------------------------------------------------------------------
*/

Router::add(
    "GET",
    "/api/get-friends",
    fn() =>
    FriendController::getFriends(),
    true
);

Router::add(
    "POST",
    "/api/send-request",
    fn() =>
    FriendController::sendFriendRequest(),
    true
);

Router::add(
    "POST",
    "/api/response-request",
    fn() =>
    FriendController::responseFriendRequest(),
    true
);

Router::add(
    "GET",
    "/api/get-sent-requests",
    fn() =>
    FriendController::getFriendRequest(),
    true
);

Router::add(
    "GET",
    "/api/get-received-requests",
    fn() =>
    FriendController::getReceivedRequests(),
    true
);

Router::add(
    "GET",
    "/api/get-people-you-may-know",
    fn() =>
    FriendController::peopleYouMayKnow(),
    true
);

Router::add(
    "POST",
    "/api/follow",
    fn() =>
    FriendController::followUser(),
    false
);

Router::add(
    "POST",
    "/api/unfollow",
    fn() =>
    FriendController::unfollowUser(),
    false
);

Router::add(
    "POST",
    "/api/block-user",
    fn() =>
    FriendController::blockUser(),
    false
);

Router::add(
    "POST",
    "/api/unblock",
    fn() =>
    FriendController::unblockUser(),
    false
);

Router::add(
    "POST",
    "/api/unfriend",
    fn() =>
    FriendController::unfriend(),
    false
);


/*
|--------------------------------------------------------------------------
| Saved Posts
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/create-saved-list",
    fn() =>
    SaveController::createSavedLists(),
    true
);

Router::add(
    "POST",
    "/api/save-post",
    fn() =>
    SaveController::createSavedPosts(),
    true
);

Router::add(
    "GET",
    "/api/get-saved-lists",
    fn() =>
    SaveController::getSavedLists(),
    true
);

Router::add(
    "GET",
    "/api/get-saved-posts/{list_id}",
    fn($list_id) =>
    SaveController::getSavedPosts($list_id),
    true
);

Router::add(
    "GET",
    "/api/update-saved-posts",
    fn() =>
    SaveController::updateSavedPosts(),
    true
);

Router::add(
    "GET",
    "/api/delete-saved-posts",
    fn() =>
    SaveController::deleteSavedPosts(),
    true
);


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/search",
    fn() =>
    SearchController::search(),
    true
);


/*
|--------------------------------------------------------------------------
| Chats
|--------------------------------------------------------------------------
*/

Router::add(
    "GET",
    "/api/chats",
    fn() =>
    ChatController::getMyChats(),
    true
);

Router::add(
    "GET",
    "/api/chat",
    fn() =>
    ChatController::getChat(),
    true
);

Router::add(
    "POST",
    "/api/chats/private",
    fn() =>
    ChatController::createPrivateChat(),
    true
);

Router::add(
    "POST",
    "/api/chats/group",
    fn() =>
    ChatController::createGroupChat(),
    true
);

Router::add(
    "GET",
    "/api/chats/participants",
    fn() =>
    ChatController::getParticipants(),
    true
);

Router::add(
    "POST",
    "/api/chats/leave",
    fn() =>
    ChatController::leaveChat(),
    true
);

Router::add(
    "POST",
    "/api/chats/delete",
    fn() =>
    ChatController::deleteChat(),
    true
);


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/chat/get-messages",
    fn() =>
    MessageController::getMessages(),
    true
);

Router::add(
    "POST",
    "/api/chat/send-message",
    fn() =>
    MessageController::sendMessage(),
    true
);

Router::add(
    "POST",
    "/api/chat/edit-message",
    fn() =>
    MessageController::editMessage(),
    true
);

Router::add(
    "POST",
    "/api/chat/delete-message",
    fn() =>
    MessageController::deleteMessage(),
    true
);

Router::add(
    "POST",
    "/api/chat/update-receipt",
    fn() =>
    MessageController::updateReceipt(),
    true
);


/*
|--------------------------------------------------------------------------
| Devices & Keys
|--------------------------------------------------------------------------
*/

Router::add(
    "POST",
    "/api/register-device",
    fn() =>
    DeviceController::registerDevice(),
    true
);

Router::add(
    "GET",
    "/api/get-public-keys",
    fn() =>
    DeviceController::getPublicKeys(),
    true
);
//report post
Router::add(
    "POST",
    "/api/reportPost",
    fn()=>
    ReportController::reportPost(),
    false
);
