<?php 
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Auth;

class FriendController{
    public static function sendFriendRequest(){
        
        $conn=Database::connect();
        // $user=Auth::getUser();
        // if(!$user){
        //     Response::json([
        //         "status"=>false,
        //         "message"=>"unauthorized"
        //     ]);
        // }
        $input=Request::json();
        $user_1_id=(int)($input("user_1_id") ?? 0);//sender
        $user_2_id=(int)($input("user_2_id")?? 0);//receiver
        if($user_1_id==$user_2_id||$user_2_id===0||$user_1_id===0){
            Response::json([
                "status" =>false,
                "message"=>"Invalid user"
            ]);
        }
        $userSql="INSERT INTO friends(friend_id,user_1_id,user_2_id,status) values(?,?,?,pending)";
        $stmt=$conn->prepare($userSql);
        $stmt->bind_param();


       

    }
}


?>