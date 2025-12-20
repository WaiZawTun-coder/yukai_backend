<?php
namespace App\Controllers;

use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Generator;
use App\Service\TokenService;
use App\Service\PasswordService;
use App\Service\ImageService;
use DateTime;

class AuthController
{
    public static function index()
    {
        echo json_encode(["message" => "authController"]);
    }

    public static function login()
    {
        $conn = Database::connect();
        $input = Request::json();

        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($username === '' || $password === '') {
            Response::json([
                "status" => false,
                "message" => "Username and Password required"
            ], 400);
        }

        $sql = "SELECT * FROM users 
                WHERE email = ? OR username = ? OR display_username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "Account not found"
            ], 404);
        }

        $user = $result->fetch_assoc();

        // 🔒 BLOCK INCOMPLETE REGISTRATION
        if ((int) $user['completed_step'] < 2) {
            Response::json([
                "status" => false,
                "message" => "Registration not completed"
            ], 403);
        }

        if ((int) $user['is_active'] === 0) {
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

        // Generate tokens
        $accessToken = TokenService::generateAccessToken($user['user_id']);
        [$refreshToken, $refreshHash] = TokenService::generateRefreshToken();

        $expireAt = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 30);

        $update = $conn->prepare("
            UPDATE users 
            SET refresh_token = ?, refresh_token_expire_time = ?
            WHERE user_id = ?
        ");
        $update->bind_param("ssi", $refreshHash, $expireAt, $user['user_id']);
        $update->execute();

        $isSecure = getenv("IS_SECURE") === "true";

        setcookie("refresh_token", $refreshToken, [
            "expires" => time() + 60 * 60 * 24 * 30,
            "path" => "/auth/refresh",
            "secure" => $isSecure,
            "httponly" => true,
            "samesite" => "Strict"
        ]);

        Response::json([
            "status" => true,
            "message" => "Login successful",
            "data" => [
                "username" => $user["username"],
                "email" => $user["email"],
                "display_username" => $user["display_username"],
                "profile_image" => $user["profile_image"],
                "cover_image" => $user["cover_image"],
                "access_token" => $accessToken
            ]
        ]);
    }

    // need to add protect in step 2
    public static function register($username = "")
    {
        $conn = Database::connect();
        $input = Request::json();
        $step = 1;

        if ($username !== "") {
            $stmt = $conn->prepare("SELECT completed_step FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) {
                Response::json(["status" => false, "message" => "User not found"], 404);
            }

            $step = ((int) $res->fetch_assoc()['completed_step']) + 1;
        }

        switch ($step) {
            case 1:
                $username = trim($input["username"] ?? "");
                $password = trim($input["password"] ?? "");
                $email = trim($input["email"] ?? "");

                if ($username === "" || $password === "" || $email === "") {
                    Response::json(["status" => false, "message" => "All fields required"], 400);
                }

                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();

                if ($stmt->get_result()->num_rows > 0) {
                    Response::json(["status" => false, "message" => "Email already registered"], 409);
                }

                $generatedUsername = Generator::generateUsername($username);
                $hash = PasswordService::hash($password);

                $stmt = $conn->prepare("INSERT INTO users (username, display_username, password, email, completed_step) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("ssss", $generatedUsername, $username, $hash, $email);
                $stmt->execute();

                $userId = $conn->insert_id;

                $accessToken = TokenService::generateAccessToken($userId, "registration");
                [$refreshToken, $refreshHash] = TokenService::generateRefreshToken();

                setcookie("refresh_token", $refreshToken, [
                    "expires" => time() + 60 * 60 * 24 * 30,
                    "path" => "/auth/refresh",
                    "httponly" => true,
                    "samesite" => "Strict"
                ]);

                Response::json([
                    "status" => true,
                    "data" => [
                        "generated_username" => $generatedUsername,
                        "access_token" => $accessToken
                    ]
                ]);
                break;
            case 2:
                $userId = (int) trim(Request::input("userId") ?? 0);
                $bodyUsername = trim(Request::input("username") ?? "");
                $dateOfBirth = trim(Request::input("dateOfBirth") ?? "");
                $gender = trim(Request::input("gender") ?? "");
                $phoneNumber = trim(Request::input("phoneNumber") ?? "");
                $email = trim(Request::input("email") ?? "");
                $profileImage = Request::file("profileImage");

                //
                $header = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
                if (!preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                    Response::json([
                        "status" => false,
                        "message" => "Unauthorized"
                    ], 401);
                }
                $token = $matches[1];
                $payload = JWT::decode($token, $_ENV["JWT_SECRET"]);
                if ($payload["user_id"] != $userId) {
                    Response::json([
                        "status" => false,
                        "message" => "Token mismatch"
                    ], 401);
                }

                if ($profileImage != null && $profileImage["error"] !== UPLOAD_ERR_OK) {
                    Response::json([
                        "status" => false,
                        "message" => "Upload Failed"
                    ], 400);
                }

                // Required fields
                if ($bodyUsername === "") {
                    Response::json(["status" => false, "message" => "Username Required"], 400);
                }

                if ($dateOfBirth === "") {
                    Response::json(["status" => false, "message" => "Date of birth Required"], 400);
                }

                if ($gender === "") {
                    Response::json(["status" => false, "message" => "Gender Required"], 400);
                }

                // Validate date format
                $birthday = DateTime::createFromFormat("Y-m-d", $dateOfBirth);
                $errors = DateTime::getLastErrors();

                if (!$birthday) {
                    Response::json(["status" => false, "message" => "Invalid date format"], 400);
                }

                // Prevent future dates
                $today = new DateTime("today");
                if ($birthday > $today) {
                    Response::json(["status" => false, "message" => "Date of birth cannot be in the future"], 400);
                }

                // Age check (13+)
                $age = $birthday->diff($today)->y;
                if ($age < 13) {
                    Response::json(["status" => false, "message" => "You must be at least 13 years old"], 400);
                }

                // Username availability (only if changed)
                if ($username !== $bodyUsername) {
                    $checkSql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("s", $bodyUsername);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows > 0) {
                        Response::json([
                            "status" => false,
                            "message" => "$bodyUsername is not available"
                        ], 409);
                    }
                }

                if ($profileImage != null) {
                    $uploadImageResult = ImageService::uploadImage($profileImage);
                }
                $imageUrl = $uploadImageResult["secure_url"] ?? "";

                // Update user
                $updateSql = "
                        UPDATE users 
                        SET 
                            username = ?, 
                            birthday = ?, 
                            gender = ?, 
                            phone_number = ?, 
                            profile_image = ?,
                            completed_step = 2
                        WHERE username = ? AND email = ?
                    ";

                $birthdaySql = $birthday->format("Y-m-d");

                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param(
                    "sssssss",
                    $bodyUsername,
                    $birthdaySql,
                    $gender,
                    $phoneNumber,
                    $imageUrl,
                    $username,
                    $email
                );

                $updateStmt->execute();

                if ($updateStmt->affected_rows === 0) {
                    Response::json([
                        "status" => false,
                        "message" => "Registration failed - step 2"
                    ], 500);
                }

                Response::json([
                    "status" => true,
                    "message" => "Step 2 completed successfully"
                ]);
                break;
            default:
                Response::json([
                    "status" => false,
                    "message" => "Invalid Registratino Step"
                ]);
                break;
        }
    }

    public static function refresh()
    {
        $conn = Database::connect();

        // Read refresh token from cookie
        $refreshToken = $_COOKIE["refresh_token"] ?? null;

        if (!$refreshToken) {
            Response::json([
                "status" => false,
                "message" => "Refresh token missing"
            ], 401);
        }

        // Hash received token
        $refreshHash = hash("sha256", $refreshToken);

        // Find matching user
        $stmt = $conn->prepare("
        SELECT user_id, refresh_token, refresh_token_expire_time
        FROM users
        WHERE refresh_token = ?
        LIMIT 1
    ");
        $stmt->bind_param("s", $refreshHash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "Invalid refresh token"
            ], 401);
        }

        $user = $result->fetch_assoc();

        // Check expiration
        if (strtotime($user["refresh_token_expire_time"]) < time()) {
            Response::json([
                "status" => false,
                "message" => "Refresh token expired"
            ], 401);
        }

        // Rotate refresh token
        [$newRefreshToken, $newRefreshHash] = TokenService::generateRefreshToken();

        $newExpire = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 30);

        $update = $conn->prepare("
        UPDATE users 
        SET refresh_token = ?, refresh_token_expire_time = ?
        WHERE user_id = ?
    ");
        $update->bind_param("ssi", $newRefreshHash, $newExpire, $user["user_id"]);
        $update->execute();

        // Set new refresh token cookie
        $isSecure = getenv("IS_SECURE") === "true";

        setcookie("refresh_token", $newRefreshToken, [
            "expires" => time() + 60 * 60 * 24 * 30,
            "path" => "/auth/refresh",
            "secure" => $isSecure,
            "httponly" => true,
            "samesite" => "Strict"
        ]);

        // Issue new access token
        $accessToken = TokenService::generateAccessToken($user["user_id"]);

        Response::json([
            "status" => true,
            "message" => "Token refreshed",
            "data" => [
                "access_token" => $accessToken
            ]
        ]);
    }


    public static function profile()
    {
        echo json_encode(["message" => "profile"]);
    }
}