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

    public static function responseFriendRequest(){
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
    public static function getFriendRequest(){
        $conn=Database::connect();
        $input=Request::json();
        $sender_id=(int)($input['user_1_id']?? 0);
        $friendRequentList=
        "SELECT f.user_2_id as requsted_user_id,
                u.display_name,
                u.profile_image,
                u.cover_image,
                f.created_at
                FROM friends f
                JOIN users u ON f.user_2_id = u.user_id
                WHERE f.user_1_id =? 
                AND f.status='pending'
                ORDER BY f.created_at DESC";
        $getRequest=$conn->prepare($friendRequentList);
        $getRequest->bind_param("i",$sender_id);
        $getRequest->execute();
        $getResultList=$getRequest->get_result();
        $posts=[];
        while($row["creator"]= $getResultList->fetch_assoc()){
            // $row["creator"]=[
            //     "id"=>$row["user_id"],
            //     "display_name"=>$row["display_name"],
            //     "profile_image"=>$row["profile_image"]
            // ];
            $posts[]=$row;
        }
       
        Response::json([
            "status"=>true,
            "message"=>"Get Friend Request List",
            "data"=>array_values($posts)
        ]);

    }
    public static function getReceivedRequests(){
        $conn=Database::connect();
        $input=Request::json();
        $receiver_id=(int)($input['user_2_id']?? 0);
        $friendRequentList=
        "SELECT f.user_1_id as sender_id,
                u.display_name,
                u.profile_image,
                u.cover_image,
                f.created_at
                FROM friends f
                JOIN users u ON f.user_1_id = u.user_id
                WHERE f.user_2_id =? 
                AND f.status='pending'
                ORDER BY f.created_at DESC";
        $getRequest=$conn->prepare($friendRequentList);
        $getRequest->bind_param("i",$receiver_id);
        $getRequest->execute();
        $getResultList=$getRequest->get_result();
        $posts=[];
        while($row["receiver"]= $getResultList->fetch_assoc()){
           
            $posts[]=$row;
        }
       
        Response::json([
            "status"=>true,
            "message"=>"Get Friend Request List",
            "data"=>array_values($posts)
        ]);
    }
    public static function peopleYouMayKnow(){
        $conn=Database::connect();
        $input=Request::json();
        $user_id=(int)($input['user_id']?? 0);
        $getMyfriend="SELECT 
            u.user_id,
            u.display_name
            from users u 
            JOIN friends f
            ON (u.user_id=f.user_1_id or u.user_id=f.user_2_id)
            WHERE (f.user_1_id=? or f.user_2_id=?)
            AND u.user_id!=?";
        $getMyFriendList=$conn->prepare($getMyfriend);
        $getMyFriendList->bind_param("iii",$user_id,$user_id,$user_id);
        $getMyFriendList->execute();
        $getResult=$getMyFriendList->get_result();
        $myfriend=[];
        while($row=$getResult->fetch_assoc()){
            $myfriend[]=$row["user_id"];
        }
        // Response::json([
        //     "status"=>true,
        //     "data"=>$myfriend
        // ]);
        $friendList = implode(',', $myfriend);
        $getFriendOfFriendSql="SELECT 
               DISTINCT friend_id,
               u.display_name,
               u.profile_image,
               u.cover_image
               from friends f
               LEFT JOIN users u
               ON (u.user_id=f.user_1_id or u.user_id=f.user_2_id)
               WHERE (f.user_1_id IN ($friendList) OR f.user_2_id IN ($friendList))
               AND f.user_1_id!=? AND f.user_2_id!=?
               AND u.user_id not in ($friendList)
             
               ORDER BY u.display_name
               ";
        $getmutualFriend=$conn->prepare($getFriendOfFriendSql);
        $getmutualFriend->bind_param("ii",$user_id,$user_id);
        $getmutualFriend->execute();
        $getResultList=$getmutualFriend->get_result();
        $mutualFriend=[];
        while($row=$getResultList->fetch_assoc()){
            $mutualFriend[]=$row;
        }
        Response::json([
            "status"=>true,
            "data"=>$mutualFriend
        ]);


    }

 }