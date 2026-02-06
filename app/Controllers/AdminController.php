<?php
<<<<<<< HEAD
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Service\TokenService;
use App\Core\Generator;
use App\Service\PasswordService;
class AdminController{
    public static function AdminRegister(){
        $conn=Database::connect();
        $input=Request::json();
        // $username=trim($input['userName']??'');
        $displayUsername=trim($input['displayUsername']?? '');
        $password=trim($input['password']?? '');
        $email=trim($input['email']?? '');
        $admin_id=(int)($input['admin_id']?? 0);
    //     $creator=Auth::getUser();
    //     //  if (!$creator) {
    //     // return Response::json([
    //     //     "status" => false,
    //     //     "message" => "Unauthorized"
    //     // ], status: 401);
    // // }

    //     $creator_id=$creator['admin_id']?? null;
    //     $creator_role=$creator['role']?? null;
        // $generateUsername=Generator::generateUsername($username);
    //     if($creator_role!=='super_admin'){
    //         Response::json([
    //             "status"=>false,
    //             "message"=>"Only superAdmin can create admin accounts"
    //         ]);
    //     }
    if (!$admin_id) {
        return Response::json([
            "status" => false,
            "message" => "admin_id required for testing"
        ], 401);
    }
    
    // Check role from DB
    $stmt = $conn->prepare(query: "SELECT role FROM admin WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return Response::json([
            "status" => false,
            "message" => "Admin not found"
        ], 404);
    }

    $admin = $result->fetch_assoc();

    if ($admin['role'] !== 'super_admin') {
        return Response::json([
            "status" => false,
            "message" => "Only superAdmin can create admin accounts"
        ], 403);
    }
     
    
    // Check duplicate username
    $stmtCheck = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? ");
    $stmtCheck->bind_param("s", $displayUsername);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    if ($resultCheck->num_rows > 0) {
        return Response::json([
            "status" => false,
            "message" => "Username already exists"
        ], 409);
    }
    //to test field requirements
     if ($displayUsername === "" || $password === "" || $email === "") {
                    Response::json(["status" => false, "message" => "All fields required"], 400);
                }
                
    //to test email already exists or not??
     $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
     $stmt->bind_param("s", $email);
                $stmt->execute();

                  if ($stmt->get_result()->num_rows > 0) {
                    Response::json(["status" => false, "message" => "Email already registered"], 409);
                }
    $generatedUsername = Generator::generateUsername($displayUsername);
    $hashpwd = PasswordService::hash($password);
    $sql = "INSERT INTO admin (username,display_name, email, password)
            VALUES (?, ?,?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss",$generatedUsername,$displayUsername, $email, $hashpwd);
    $stmt->execute();

    return Response::json([
        "status" => true,
        "message" => "Create Admin Successfully"
    ]);
    }
    
    public static function AdminLogin(){
        $conn = Database::connect();
        $input = Request::json();
        $admin_id  =(int)($input['user_id']?? ''); 
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $role      = trim($input['role']?? '');

        if ($username === '' || $password === '') {
            Response::json([
                "status" => false,
                "message" => "Username and Password required"
=======

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Generator;
use App\Service\TokenService;
use App\Service\PasswordService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/Exception.php';
require_once __DIR__ . '/../../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/SMTP.php';

class AdminController
{

    /* ====== Account Status ====== */
    public static function accountStatus()
    {
        $admin_id = (int) (Request::input("admin_id") ?? 0);
        $conn = Database::connect();
        $user_id = (int) (Request::input("user_id") ?? 0);
        $status = trim(Request::input("status") ?? "");

        /* ===== check admin exist ====*/
        $sql = "SELECT * FROM admin WHERE admin_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "Admin not found"
            ], 404);
            return;
        }


        /* ===== check user exist ====*/
        $sql = "SELECT * FROM users WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);
            return;
        }

        $update = $conn->prepare("UPDATE users SET status= ? WHERE user_id=?");
        $update->bind_param("si", $status, $user_id);

        if ($update->execute()) {
            Response::json([
                "status" => true,
                "message" => "Status changed successfully"
            ], 200);
        } else {
            Response::json([
                "status" => false,
                "message" => "Failed to update password"
            ], 500);
        }


    }


    /* ================ Get All Admin List ================ */
    public static function getAdminLists()
    {
        $conn = Database::connect();
        // Current page
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 5;
        $offset = ($page - 1) * $limit;

        /* ---------- COUNT TOTAL ROWS ---------- */
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM admin ");
        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_assoc();

        $totalRecords = (int) $countResult['total'];
        $totalPages = ceil($totalRecords / $limit);

        if ($totalRecords === 0) {
            Response::json([
                "status" => false,
                "message" => "Admin Account is not found"
            ]);
            return;
        }

        /* ---------- FETCH DATA ---------- */
        $stmt = $conn->prepare(
            "SELECT ad.username,
                ad.display_name,
                ad.email,
                ad.profile_image,
                ad.role

                FROM admin ad
                ORDER BY ad.created_at DESC
                LIMIT ? OFFSET ?"
        );

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $adminAccounts = [];
        while ($row = $result->fetch_assoc()) {
            $adminAccounts[] = $row;
        }

        /* ---------- RESPONSE ---------- */
        Response::json([
            "status" => true,
            "current_page" => $page,
            "limit" => $limit,
            "total_pages" => $totalPages,
            "total_records" => $totalRecords,
            "data" => $adminAccounts
        ]);

    }

    // =====================================
    // Ban Morderator from admin
    // =====================================

    public static function banAdmin()
    {
        $conn = Database::connect();
        $super_admin_id = (int) (Request::input("super_admin_id") ?? 0); // login super admin 
        $banned_admin_id = (int) (Request::input("banned_admin_id") ?? 0);

        if ($super_admin_id <= 0 || $banned_admin_id <= 0) {
            Response::json([
                "status" => false,
                "message" => "Invalid ID"
            ]);
            return;
        }
        // super admin cannot ban himself
        if ($super_admin_id === $banned_admin_id) {
            Response::json([
                "status" => false,
                "message" => "Super admin cannot be banned himself"
            ]);
            return;
        }

        $stmt = $conn->prepare("SELECT * FROM admin WHERE admin_id=? and role='super_admin' and is_active=1");
        $stmt->bind_param("i", $super_admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "You are not Super Admin "
            ]);
            return;
        }

        // ban admin 

        $update = $conn->prepare("UPDATE admin SET is_active = 0 WHERE admin_id=? ");
        $update->bind_param("i", $banned_admin_id);

        if ($update->execute()) {
            Response::json([
                "status" => true,
                "message" => "Banned Successfully"
            ]);
            return;
        } else {
            Response::json([
                "status" => false,
                "message" => " Admin Account cannot be banned "
            ]);
        }


    }
    public static function AdminRegister()
    {
        $conn = Database::connect();
        $input = Request::json();
        $displayUsername = trim($input['username'] ?? '');

        // $password=trim($input['password']?? '');
        $email = trim($input['email'] ?? '');

        $creator = AdminAuth::admin();

        if (!$creator) {
            return Response::json([
                "status" => false,
                "message" => "Unauthorized"
            ], status: 401);
        }

        $creator_role = $creator['role'] ?? null;
        if (!$creator || $creator_role !== 'super_admin') {
            Response::json([
                "status" => false,
                "message" => "Only superAdmin can create admin accounts"
            ]);
        }

        //to test field requirements
        if ($displayUsername === "" || $email === "") {
            Response::json(["status" => false, "message" => "All fields required"], 400);
        }

        //to test email already exists or not??
        $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            Response::json(["status" => false, "message" => "Email already registered"], 409);
        }
        $generatedUsername = Generator::generateUsername($displayUsername);
        // $hashpwd = null;
        $super_admin_id = (int) $creator['admin_id'];

        $sql = "INSERT INTO admin (username, display_name, email, created_by)
            VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssi",
            $generatedUsername,
            $displayUsername,
            $email,
            $super_admin_id
        );


        $stmt->execute();
        Response::json([
            "status" => true,
            "message" => "Admin created successfully.Password has not set yet",
            "created by" => $creator_role,
            "data" => [
                "username" => $generatedUsername,
                "display_name" => $displayUsername,
                "email" => $email
            ]
        ], 201);

    }

    public static function AdminLogin()
    {
        $conn = Database::connect();
        $input = Request::json();

        $username = trim($input['username'] ?? '');//login
        $password = trim($input['password'] ?? null);


        if ($username === '') {
            Response::json([
                "status" => false,
                "message" => "Username required"
>>>>>>> 0e5264aa2004c902e27170ba437dff471b88db5c
            ], 400);
            return;
        }

        $sql = "SELECT * FROM admin
<<<<<<< HEAD
                WHERE email = ? OR username = ? OR display_name = ? OR role=?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $username, $username,$role);
=======
                    WHERE email = ? OR username = ? OR display_name = ?
                    LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $username, $username);
>>>>>>> 0e5264aa2004c902e27170ba437dff471b88db5c
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
<<<<<<< HEAD
=======
        if ($user['password'] === null) {

            $otp = self::generateOTP($user['admin_id']);
            self::sendEmail($user['email'], $otp);

            Response::json([
                "status" => false,
                "message" => "Password was not set. OTP sent to email.",
                "action" => "SET_PASSWORD"
            ]);

        }
        // $hashpwd = PasswordService::hash($password);
        // $updateSql=""

        // password verify
        if (!PasswordService::verify($password, $user['password'])) {
            Response::json([
                "status" => false,
                "message" => "Invalid password"
            ], 401);
        }
>>>>>>> 0e5264aa2004c902e27170ba437dff471b88db5c

        if ((int) $user['is_active'] === 0) {
            Response::json([
                "status" => false,
                "message" => "Inactive admin account"
            ], 403);
            return;
        }
<<<<<<< HEAD
         $accessToken = TokenService::generateAccessToken([
        "admin_id" => $user['admin_id'],
        "username" => $user['username'],
        "role"     =>$user['role'],
        
    ]);
=======

        $accessToken = TokenService::generateAccessToken([
            "admin_id" => $user['admin_id'],
            "username" => $user['username'],
            "role" => $user['role'],

        ]);
>>>>>>> 0e5264aa2004c902e27170ba437dff471b88db5c

        Response::json([
            "status" => true,
            "message" => "Login successful",
            "data" => [
<<<<<<< HEAD
                "admin_user_id"      => $user["user_id"],
                "username"     => $user["username"],
                "email"        => $user["email"],
                "role"         =>$user['role'],
                "display_name" => $user["display_name"],
                "is_active"    => $user["is_active"],
                "last_seen"    => $user["last_seen"],
                "access_token" => $accessToken,
               
            ]
        ]);
    }
=======
                "admin_user_id" => $user["admin_id"],
                "username" => $user["username"],
                "email" => $user["email"],
                "role" => $user['role'],
                "display_name" => $user["display_name"],
                "is_active" => $user["is_active"],
                "last_seen" => $user["last_seen"],
                "access_token" => $accessToken,
                "profile_image" => $user["profile_image"]
            ]
        ]);
    }

    public static function generateOTP($admin_id)
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
                DELETE FROM admin_otp 
                WHERE admin_id = ?
            ");
        $cleanupStmt->bind_param("i", $admin_id);
        $cleanupStmt->execute();

        // Insert new OTP record (NOT USED YET)
        $stmt = $conn->prepare("
                INSERT INTO admin_otp (admin_id, otp_code, expiration_time)
                VALUES (?, ?, NOW() + INTERVAL 5 MINUTE);
            ");
        $stmt->bind_param("is", $admin_id, $hashedOtp);

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
    public static function verifyOTP($admin_id, $otpcode)
    {
        $conn = Database::connect();


        // Get valid OTPs for this user
        $stmt = $conn->prepare("
                SELECT otp_id, otp_code, expiration_time
                FROM admin_otp
                WHERE admin_id = ? 
                AND expiration_time > NOW()
                AND is_used = FALSE
                ORDER BY created_at DESC
                LIMIT 1
            ");
        $stmt->bind_param("i", $admin_id);
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
                UPDATE admin_otp
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

        $stmt = $conn->prepare("SELECT admin_id, role FROM admin WHERE email = ? AND is_active=1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        }

        if ($user['role'] === 'super_admin') {
            Response::json([
                "status" => false,
                "message" => "superAdmin cannot reset his own password"
            ]);
        }

        $otpcode = self::generateOTP($user['admin_id']);

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
    public static function resetPassword()
    {
        $conn = Database::connect();

        $admin_id = (int) (Request::input("admin_id") ?? 0);
        $otpcode = trim(Request::input("otp_code") ?? "");
        $newPassword = Request::input("new_password") ?? "";

        if (!$admin_id || $otpcode === "" || $newPassword === "") {
            Response::json([
                "status" => false,
                "message" => "All fields are required"
            ], 400);
        }

        if (!self::verifyOTP($admin_id, $otpcode)) {
            Response::json([
                "status" => false,
                "message" => "Invalid or expired OTP"
            ], 401);
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $hashedPassword, $admin_id);
        $stmt->execute();

        Response::json([
            "status" => true,
            "message" => "Password reset successfully"
        ]);
    }
>>>>>>> 0e5264aa2004c902e27170ba437dff471b88db5c
}
