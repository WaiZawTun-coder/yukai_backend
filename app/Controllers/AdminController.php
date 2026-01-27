<?php
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
            ], 400);
            return;
        }

        $sql = "SELECT * FROM admin
                WHERE email = ? OR username = ? OR display_name = ? OR role=?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $username, $username,$role);
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

        if ((int) $user['is_active'] === 0) {
            Response::json([
                "status" => false,
                "message" => "Inactive admin account"
            ], 403);
            return;
        }
         $accessToken = TokenService::generateAccessToken([
        "admin_id" => $user['admin_id'],
        "username" => $user['username'],
        "role"     =>$user['role'],
        
    ]);

        Response::json([
            "status" => true,
            "message" => "Login successful",
            "data" => [
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
}
