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

class AdminController{

    /* ====== Account Status ====== */
    public static function accountStatus(){
        $admin_id=(int)(Request::input("admin_id")?? 0);
        $conn=Database::connect();
        $user_id=(int)(Request::input("user_id") ?? 0);
        $status=trim(Request::input("status") ?? "");

         /* ===== check admin exist ====*/
        $sql="SELECT * FROM admin WHERE admin_id=?";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result=$stmt->get_result();

        if($result->num_rows === 0){
            Response::json([
                "status"=>false,
                "message"=>"Admin not found"
            ],404);
            return;
        }
        

        /* ===== check user exist ====*/
        $sql="SELECT * FROM users WHERE user_id=?";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result=$stmt->get_result();

        if($result->num_rows === 0){
            Response::json([
                "status"=>false,
                "message"=>"User not found"
            ],404);
            return;
        }
        
        $update=$conn->prepare("UPDATE users SET status= ? WHERE user_id=?");
        $update->bind_param("si",$status,$user_id);

        if($update->execute()){
            Response::json([
                "status" =>true,
                "message" =>"Status changed successfully"
            ],200);
        }
        else{
            Response::json([
                "status"=>false,
                "message"=>"Failed to update password"
            ],500);
        }


    }
}