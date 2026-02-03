<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Generator;
use App\Service\TokenService;
use App\Service\PasswordService;
use App\Service\ImageService;
use DateTime;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/Exception.php';
require_once __DIR__ . '/../../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/SMTP.php';

class AuthController
{
    public static function index()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = $user["user_id"];

        if ($user_id == null)
            Response::json([
                "status" => false,
                "message" => "Invalid user id"
            ], 404);

        $userSql = "
            SELECT user_id, username, display_name, gender, email,
                   phone_number, profile_image, cover_image, birthday,
                   location, is_active, last_seen, default_audience
            FROM users
            WHERE user_id = ?
        ";

        $stmt = $conn->prepare($userSql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);

        }

        $user = $result->fetch_assoc();

        Response::json([
            "status" => true,
            "data" => $user
        ]);
    }

    public static function login()
    {
        $conn = Database::connect();
        $input = Request::json();

        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $device_id=(int)(Request::input("device_id")?? 0);

        if ($username === '' || $password === '') {
            Response::json([
                "status" => false,
                "message" => "Username and Password required"
            ], 400);
            return;
        }

        $sql = "SELECT * FROM users 
                WHERE email = ? OR username = ? 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "Account not found"
            ], 404);
            return;
        }

        $user = $result->fetch_assoc();

        if ((int) $user['is_active'] === 0 ) {
            Response::json([
                "status" => false,
                "message" => "Account is not active" 
            ], 403);
            return;
        }
        
        if (trim($user['status']) === "suspend_user") {
            Response::json([
                "status" => false,
                "message" => "The user account is suspended"
            ], 403);
            return;
        }

        if (trim($user['status']) === "ban_user") {
            Response::json([
                "status" => false,
                "message" => "The user account is banned"
            ], 403);
            return;
        }


        if (!PasswordService::verify($password, $user['password'])) {
            Response::json([
                "status" => false,
                "message" => "Incorrect password"
            ], 401);
            return;
        }
<<<<<<< HEAD
        //deactivate to activate account
        if((int)$user['deactivate'] === 1){
            $update = $conn->prepare("UPDATE users SET deactivate = 0 WHERE username = ?");
            $update->bind_param("s", $user['username']);
            $update->execute();

            
            $user['deactivate'] = 0;
        }

        if((int)$user['is_2fa'] === 1){
            $otpCode=self::generateOTP($user['user_id']);
            if(!$otpCode){
               Response::json([
                  "status"=>false,
                  "message"=>"Failed to generate OTP"
        ]);
        return;
       }
        
       
       //send otp via email
       self::sendEmail($user['email'],$otpCode);
       // 🔑 STEP 2: Generate PARTIAL access token
    $accessToken = TokenService::generateAccessToken([
        "user_id" => $user['user_id'],
        "username" => $user['username'],
        "two_factor_verified" => false
    ]);
=======
        if ((int) $user['is_2fa'] === 1) {
            $otpCode = self::generateOTP($user['user_id']);
            if (!$otpCode) {
                Response::json([
                    "status" => false,
                    "message" => "Failed to generate OTP"
                ]);
                return;
            }
>>>>>>> a5507eab38b2cb95d031a52d33cf850cd2911066


            //send otp via email
            self::sendEmail($user['email'], $otpCode);
            // 🔑 STEP 2: Generate PARTIAL access token
            $accessToken = TokenService::generateAccessToken([
                "user_id" => $user['user_id'],
                "username" => $user['username'],
                "two_factor_verified" => false
            ], 300);

            //  Do NOT issue refresh token yet

            Response::json([
                "status" => true,
                "two_factor_required" => true,
                "message" => "OTP sent to your email",
                "data" => [
                    "user_id" => $user['user_id'],
                    "access_token" => $accessToken
                ]
            ]);
            return;
        }



        // Generate tokens
        $accessToken = TokenService::generateAccessToken(["user_id" => $user['user_id'], "username" => $user["username"], "two_factor_verified" => true]);
        $refreshPayload = TokenService::generateRefreshToken();
        $refreshToken = $refreshPayload["token"];
        $refreshHash = $refreshPayload["hash"];

        $expireAt = date("Y-m-d H:i:s", time() + 60 * 60 * 24 * 7);

        $update = $conn->prepare("
            UPDATE users 
            SET refresh_token = ?, refresh_token_expire_time = ?
            WHERE user_id = ?
        ");
        $update->bind_param("ssi", $refreshHash, $expireAt, $user['user_id']);
        $update->execute();

        $isSecure =
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';


        setcookie("refresh_token", $refreshToken, [
            "expires" => time() + 60 * 60 * 24 * 7,
            "path" => "/",
            "secure" => $isSecure,
            "httponly" => true,
            "samesite" => $isSecure ? "None" : "Lax"
        ]);

        // BLOCK INCOMPLETE REGISTRATION
        if ((int) $user['completed_step'] < 2) {
            Response::json([
                "status" => true,
                "incomplete" => true,
                "message" => "Registration not completed",
                "data" => [
                    "user_id" => $user["user_id"],
                    "username" => $user["username"],
                    "email" => $user["email"],
                    "completed_step" => $user["completed_step"],
                    "access_token" => $accessToken
                ]
            ]);
        }

                // check device id exists
                $stmt=$conn->prepare("Select * from devices where id=?");
                $stmt->bind_param("i",$device_id);
                $stmt->execute();
                $result=$stmt->get_result();
                if($result->num_rows === 0){
                    Response::json([
                        "status"=>false,
                        "message"=>"Device not found"
                    ]);
                    return;
                }
          // INSERT LOGIN HISTORY ✅
            // ======================
            $stmt = $conn->prepare("
                INSERT INTO login_histories (user_id, device_id, logged_in_time)
                VALUES (?, ?, NOW())
            ");
            $stmt->bind_param("ii", $user['user_id'], $device_id);
            $stmt->execute();
        Response::json([
            "status" => true,
            "message" => "Login successful",
            "data" => [
                "user_id" => $user["user_id"],
                "username" => $user["username"],
                "email" => $user["email"],
                "display_name" => $user["display_name"],
                "gender" => $user["gender"],
                "phone_number" => $user["phone_number"],
                "profile_image" => $user["profile_image"],
                "cover_image" => $user["cover_image"],
                "birthday" => $user["birthday"],
                "location" => $user["location"],
                "is_active" => $user["is_active"],
                "last_seen" => $user["last_seen"],
                "access_token" => $accessToken,
                "completed_step" => $user["completed_step"]
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
                $stmt = $conn->prepare("INSERT INTO users (username, display_name, password, email, completed_step) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("ssss", $generatedUsername, $username, $hash, $email);
                $stmt->execute();

                $userId = $conn->insert_id;

                $accessToken = TokenService::generateAccessToken(
                    [
                        "user_id" => $userId,
                        "username" => $generatedUsername,
                        "scope" => "registration"
                    ],
                    600
                );

                Response::json([
                    "status" => true,
                    "step" => 2,
                    "data" => [
                        "userId" => $userId,
                        "email" => $email,
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
                $headers = getallheaders();
                $authHeader = $headers["Authorization"] ?? $headers["authorization"] ?? null;
                if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    Response::json([
                        "status" => false,
                        "message" => "Unauthorized"
                    ], 401);
                }
                $token = $matches[1];
                try {
                    $payload = JWT::decode($token, $_ENV["JWT_SECRET"]);
                } catch (\Exception $e) {
                    Response::json([
                        "status" => false,
                        "message" => "Invalid or expired token"
                    ], 401);
                }

                if (($payload["scope"] ?? null) !== "registration") {
                    Response::json([
                        "status" => false,
                        "message" => "Invalid token scope"
                    ], 403);
                }

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

                if ($updateStmt->affected_rows == 0) {
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

        $isSecure =
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        $refreshToken = $_COOKIE["refresh_token"] ?? null;

        if (!$refreshToken) {
            Response::json([
                "status" => false,
                "message" => "Refresh token missing"
            ], 401);
            return;
        }

        $refreshHash = hash("sha256", $refreshToken);

        $stmt = $conn->prepare("
        SELECT user_id, username, refresh_token_expire_time
        FROM users
        WHERE refresh_token = ?
        LIMIT 1
    ");
        $stmt->bind_param("s", $refreshHash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            setcookie("refresh_token", "", [
                "expires" => time() - 3600,
                "path" => "/",
                "secure" => $isSecure,
                "httponly" => true,
                "samesite" => $isSecure ? "None" : "Lax"
            ]);

            Response::json([
                "status" => false,
                "message" => "Invalid refresh token"
            ], 401);
            return;
        }

        $user = $result->fetch_assoc();

        if (strtotime($user["refresh_token_expire_time"]) < time()) {

            $delete = $conn->prepare(
                "UPDATE users SET refresh_token = NULL WHERE user_id = ?"
            );
            $delete->bind_param("i", $user["user_id"]);
            $delete->execute();

            setcookie("refresh_token", "", [
                "expires" => time() - 3600,
                "path" => "/",
                "secure" => $isSecure,
                "httponly" => true,
                "samesite" => $isSecure ? "None" : "Lax"
            ]);

            Response::json([
                "status" => false,
                "message" => "Refresh token expired"
            ], 401);
            return;
        }

        // Rotate refresh token
        $refreshPayload = TokenService::generateRefreshToken();
        $newRefreshToken = $refreshPayload["token"];
        $newRefreshHash = $refreshPayload["hash"];

        $newExpire = date("Y-m-d H:i:s", time() + 604800);

        $update = $conn->prepare("
        UPDATE users
        SET refresh_token = ?, refresh_token_expire_time = ?
        WHERE user_id = ?
    ");
        $update->bind_param("ssi", $newRefreshHash, $newExpire, $user["user_id"]);
        $update->execute();

        setcookie("refresh_token", $newRefreshToken, [
            "expires" => time() + 604800,
            "path" => "/",
            "secure" => $isSecure,
            "httponly" => true,
            "samesite" => $isSecure ? "None" : "Lax"
        ]);

        $accessToken = TokenService::generateAccessToken(["user_id" => $user["user_id"], "username" => $user["username"]]);

        Response::json([
            "status" => true,
            "message" => "Token refreshed",
            "data" => [
                "access_token" => $accessToken
            ]
        ]);
    }
    // generate OTP 
    public static function generateOTP($user_id)
    {
        $conn = Database::connect();


        $otpcode = '';
        for ($i = 0; $i < 8; $i++) {
            $otpcode .= random_int(0, 9);
        }
        $expiryMinutes = 5;

        $hashedOtp = password_hash($otpcode, PASSWORD_DEFAULT);

        // Clean up existing OTPs for this user
        $cleanupStmt = $conn->prepare("
            DELETE FROM otp 
            WHERE user_id = ?
        ");
        $cleanupStmt->bind_param("i", $user_id);
        $cleanupStmt->execute();

        // Insert new OTP record (NOT USED YET)
        $stmt = $conn->prepare("
            INSERT INTO otp (user_id, otp_code, expiration_time)
            VALUES (?, ?, NOW() + INTERVAL 5 MINUTE);
        ");
        $stmt->bind_param("is", $user_id, $hashedOtp);

        if (!$stmt->execute()) {
            return false;
        }

        // Response::json([
        //     "status" => true,
        //     "message" => "Added Successfully",
        //     "data" => [
        //         // "otp code"=>$otpcode,
        //         "otp_id" => $conn->insert_id,
        //         "expires_in_minutes" => $expiryMinutes,
        //         "otp-code"=>$otpcode
        //     ]
        // ]);
        return $otpcode;
    }


    // Verify OTP
    public static function verifyOTP($user_id, $otpcode)
    {
        $conn = Database::connect();


        // Get valid OTPs for this user
        $stmt = $conn->prepare("
            SELECT otp_id, otp_code, expiration_time
            FROM otp
            WHERE user_id = ? 
            AND expiration_time > NOW()
            AND is_used = FALSE
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                'status' => false,
                'message' => 'No valid OTP found or OTP has expired'
            ], 400);
        }

        $otpRecord = $result->fetch_assoc();
        if (!password_verify($otpcode, $otpRecord['otp_code'])) {
            Response::json([
                'status' => false,
                'message' => 'Invalid OTP code'
            ], 401);
        }

        // Mark OTP as used
        $updateStmt = $conn->prepare("
            UPDATE otp 
            SET is_used = TRUE
            WHERE otp_id = ?
        ");
        $updateStmt->bind_param("i", $otpRecord['otp_id']);
        $updateStmt->execute();

        // Response::json([
        //     'status' => true,
        //     'message' => 'OTP verified successfully'
        // ]);
        return true;

    }

    public static function sendEmail($email, $otpcode)
    {
        $subject = "Password Rest OTP";
        $body = "Hello,

        Dear User,
        \n\nYour One-Time Password (OTP) for account verification is:\n\n  $otpcode\n\n This OTP is valid for 2 minutes.PLease Do not share this code with anyone.\n\n
        If you didn't request this code,please ignore this email.\n\n
        Thank you for using our service!\n\n

        Best regards,
        Yukai Support Team";

        if ($email === "") {
            Response::json([
                "status" => false,
                "message" => "Email address is required"
            ], 400);
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['GMAIL_USERNAME'];
            $mail->Password = $_ENV['GMAIL_APP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';

            $mail->setFrom($_ENV['GMAIL_USERNAME'], 'May Thingyan');
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
            // Response::json([
            //     "status" => true,
            //     "message" => "Email sent successfully"
            // ]);

        } catch (Exception $e) {
            Response::json([
                "status" => false,
                "message" => "Mailer error",
                "error" => $mail->ErrorInfo
            ], 500);
        }

    }

    //forget password function

    public static function forgetPassword()
    {
        $conn = Database::connect();
        $email = trim(Request::input("email") ?? "");

        if ($email === "") {
            Response::json([
                "status" => false,
                "message" => "Email is required"
            ], 400);
        }

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }

        $otpcode = self::generateOTP($user['user_id']);

        if (!$otpcode) {
            Response::json([
                "status" => false,
                "message" => "Failed to generate OTP"
            ], 500);
        }

        self::sendEmail($email, $otpcode);

        Response::json([
            "status" => true,
            "message" => "OTP sent to your email"
        ]);
    }

    //reset password
    public static function resetPassword()
    {
        $conn = Database::connect();

        $user_id = (int) (Request::input("user_id") ?? 0);
        $otpcode = trim(Request::input("otp_code") ?? "");
        $newPassword = Request::input("new_password") ?? "";

        if (!$user_id || $otpcode === "" || $newPassword === "") {
            Response::json([
                "status" => false,
                "message" => "All fields are required"
            ], 400);
        }

        if (!self::verifyOTP($user_id, $otpcode)) {
            Response::json([
                "status" => false,
                "message" => "Invalid or expired OTP"
            ], 401);
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashedPassword, $user_id);
        $stmt->execute();

        Response::json([
            "status" => true,
            "message" => "Password reset successfully"
        ]);
    }


    public static function profile()
    {
        echo json_encode(["message" => "profile"]);
    }

    public static function twoFactorAuthentication()
    {
        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int) ($input['user_id'] ?? 0);
        $otpcode = trim($input['otp_code'] ?? "");
        if (!$user_id || $otpcode === "") {
            Response::json([
                "status" => false,
                "message" => "user_id and otp code is required"
            ]);
        }
        if (!self::verifyOTP($user_id, $otpcode)) {
            Response::json([
                "status" => false,
                "message" => "Invalid input"
            ]);
        }
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }

        $accessToken = TokenService::generateAccessToken(
            [
                "user_id" => $user["user_id"],
                "username" => $user["username"],
                "two_factor_verified" => true
            ],
            1800
        );

        $refreshPayload = TokenService::generateRefreshToken();
        $refreshToken = $refreshPayload["token"];
        $refreshHash = $refreshPayload["hash"];

        $expireAt = date("Y-m-d H:i:s", time() + 604800);

        $update = $conn->prepare("
            UPDATE users 
            SET refresh_token = ?, refresh_token_expire_time = ?
            WHERE user_id = ?
        ");
        $update->bind_param("ssi", $refreshHash, $expireAt, $user["user_id"]);
        $update->execute();

        setcookie("refresh_token", $refreshToken, [
            "expires" => time() + 604800,
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "None"
        ]);

        Response::json([
            "status" => true,
            "message" => "2FA verified",
            "data" => [
                "access_token" => $accessToken
            ]
        ]);
    }
}