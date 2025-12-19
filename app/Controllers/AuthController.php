<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

require_once __DIR__ . '/../../services/tokenService.php';
require_once __DIR__ . '/../../services/passwordService.php';

class AuthController{
    public static function index(){
        echo json_encode(["message" => "authController"]);
    }

    public static function login() {
        $conn = Database::connect();

        $input = Request::json();

        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($username === '') {
            Response::json([
                "status" => false,
                "message" => "Username or Email Required"
            ], 400);
        }

        if ($password === '') {
            Response::json([
                "status" => false,
                "message" => "Password Required"
            ], 400);
        }

        $sql = "SELECT * FROM users 
                WHERE email = ? OR username = ? OR display_username = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $username, $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "$username is not registered."
            ], 404);
        }

        $user = $result->fetch_assoc();

        if ((int)$user['is_active'] === 0) {
            Response::json([
                "status" => false,
                "message" => "Banned account"
            ], 403);
        }

        if (!PasswordService::verify($password, $user['password'])) {
            Response::json([
                "status" => false,
                "message" => "Incorrect password"
            ], 401);
        }

        $accessToken = TokenService::generateAccessToken($user['user_id']);
        [$refreshToken, $refreshHash] = TokenService::generateRefreshToken();

        $expireAt = date("Y-m-d H:i:s", time() + 604800);

        $update = $conn->prepare(
            "UPDATE users 
            SET refresh_token = ?, refresh_token_expire_time = ?
            WHERE user_id = ?"
        );
        $update->bind_param("ssi", $refreshHash, $expireAt, $user['user_id']);
        $update->execute();

        Response::json([
            "status" => true,
            "message" => "Access granted",
            "data" => [
                "username" => $user["username"],
                "email" => $user["email"],
                "display_username" => $user["display_username"],
                "profile_image" => $user["profile_image"],
                "cover_image" => $user["cover_image"],
                "access_token" => $accessToken,
                "refresh_token" => $refreshToken
            ]
        ]);
    }

    public static function register(){
        echo json_encode(["message" => "register"]);
    }

    public static function profile(){
        echo json_encode(["message" => "profile"]);
    }
}