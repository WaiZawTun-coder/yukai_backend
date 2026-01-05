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
        $user_2_id=(int)($input['user_2_id']?? 0);//receiver
        if($user_1_id==$user_2_id||$user_2_id===0||$user_1_id===0){
            Response::json([
                "status" =>false,
                "message"=>"Invalid user"
            ]);
            return;
        }
        $checkSql="SELECT friend_id from friends where (user_1_id=? AND user_2_id=?) or (user_1_id=? AND user_2_id=?)";
        $check=$conn->prepare($checkSql);
        $check->bind_param("iiii",$user_1_id,$user_2_id,$user_2_id,$user_1_id);
        $check->execute();
        $checkresult=$check->get_result();

        if($checkresult->num_rows>0){
            Response::json([
                "stauts"=>False,
                "message"=>"Friend Request already exists"
            ]);
            return;
        }
        
        $friendSql="INSERT INTO friends(user_1_id,user_2_id,status) values(?,?,'pending')";
        $stmt=$conn->prepare($friendSql);
        $stmt->bind_param("ii",$user_1_id,$user_2_id);
        $stmt->execute();
        Response::json([
            "status"=>True,
            "message"=>"Friend requet sent"

        ]);
        


       

    }
}


?>