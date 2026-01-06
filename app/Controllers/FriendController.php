<?php 
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Auth;

class FriendController{
    public static function sendFriendRequest(){
        
        $conn=Database::connect();
        $input=Request::json();
        $user_1_id=(int)($input['user_1_id'] ?? 0);//sender
        $user_2_id=(int)($input['user_2_id'] ?? 0);//receiver
        if($user_1_id==$user_2_id||$user_2_id===0||$user_1_id===0){
            Response::json([
                "status" =>false,
                "message"=>"Invalid user"
            ]);
    
        }
        $checkSql="SELECT friend_id from friends where (user_1_id=? AND user_2_id=?) or (user_1_id=? AND user_2_id=?)";
        $check=$conn->prepare($checkSql);
        $check->bind_param("iiii",$user_1_id,$user_2_id,$user_2_id,$user_1_id);
        $check->execute();
        $checkresult=$check->get_result();

        if($checkresult->num_rows>0){
            Response::json([
                "stauts"=>false,
                "message"=>"Friend Request already exists"
            ]);
        
        }
        
        $friendSql="INSERT INTO friends(user_1_id,user_2_id,status) values(?,?,'pending')";
        $stmt=$conn->prepare($friendSql);
        $stmt->bind_param("ii",$user_1_id,$user_2_id);
        $stmt->execute();
        Response::json([
            "status"=>true,
            "message"=>"Friend requet sent"
        ]);           
    }

    public static function acceptFriendRequest(){
            $conn=Database::connect();
            $input=Request::json();
            $sender_id=(int)($input['user_1_id']?? 0);
            $receiver_id=(int)($input['user_2_id']?? 0);
            $type=(string)($input['status']?? '');
            if(!in_array($type,['accepted','rejected','canceled'])){
                Response::json([
                    "status"=>false,
                    "message"=>"invalid input"
                ]);
            }
            $acceptFri="Update friends set status=? where user_1_id=? and user_2_id=? and status ='pending'";
            $updateFriList=$conn->prepare($acceptFri);
            $updateFriList->bind_param("sii",$type,$sender_id,$receiver_id);
            $updateFriList->execute();
            if($type==='canceled'){
                $message="Friend requent canceled";
            }
            else if($type==='accepted'){
                $message="Friend request accepted";
            }
            else if($type === "rejected"){
                $message="Friend request rejected ";
            }

            Response::json([
                "status"=>true,
                "message"=>$message
            ]);

    }

 }