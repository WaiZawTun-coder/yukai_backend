<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
use App\Core\Request;

class ChattingController{
    public static function privateChat(){
        $conn=Database::connect();
        $input=Request::json();
        $sender=(int)($input['login_user']?? 0);//login user
        $receiver=(int)($input['target_user']?? 0);//target_user
        $chatType=(int)($input['chat_type']?? 0);
        $sql="SELECT c.chat_id from chats c 
             JOIN chat_participants cp1 ON cp1.chat_id=c.chat_id 
             JOIN chat_participants cp2 ON cp2.chat_id=c.chat_id 
             where c.type=? OR cp1.user_id=? AND cp2.user_id=?";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("iii",$chatType,$sender,$receiver);
        $stmt->execute();
        $result=$stmt->get_result();
       
       if($result->num_rows===0){
            Response::json([
                "status"=>false,
                "message"=>"create new chat"
            ]);
       } 
       $chat=$result->fetch_assoc();
       $chatId=(int)($chat['chat_id']);
       //get Chat type
       $chatTypeSql="SELECT type from chats where chat_id=?";
       $chatType   =$conn->prepare($chatTypeSql);
       $chatType   ->bind_param("i",$chatId);
       $chatType   ->execute();
       $chatTypeResult=$chatType->get_result()->fetch_assoc();
        // $participants=[];
            if($chatTypeResult['type']==='private'){
                    $privateSql="SELECT u.user_id,
                                 u.username,
                                 u.display_name,
                                 u.profile_image
                                 FROM chat_participants cp
                                 JOIN users u ON u.user_id=cp.user_id
                                 WHERE cp.chat_id=? AND
                                 cp.user_id!=?";//login user
                    $privateChat=$conn->prepare($privateSql);
                    $privateChat->bind_param("ii",$chatId,$sender);
                    $privateChat->execute();
                    $privateChatParticipants=$privateChat->get_result()->fetch_assoc();
                    Response::json([
                        "status"=>true,
                        "chat_type"=>"private",
                        "users"=>$privateChatParticipants
                    ]);

            }
            else{
                $groupSql="SELECT u.user_id,
                                  u.username,
                                  u.display_name,
                                  u.profile_image
                                  FROM chat_participants cp
                                  JOIN users u ON u.user_id= cp.user_id
                                  WHERE cp.chat_id=?";
                $group=$conn->prepare($groupSql);
                $group->bind_param("i",$chatId);
                $group->execute();
                $groupChatParticipants=[];
                $result= $group->get_result();
                while ($row = $result->fetch_assoc()) {
                       $groupChatParticipants[] = $row;
                }

                Response::json([
                      "status" => true,
                      "chat_type" => "group",
                      "users" => $groupChatParticipants
                ]);
                
            }
        }
       

    }
