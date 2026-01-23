<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use LDAP\Result;

class ReportController{
    public static function reportPost(){
        $conn=Database::connect();
        $input=Request::json();
        $reporter_user_id=(int)($input['user_id'] ?? 0);//login user
        $post_id=(int)($input['post_id']?? 0);
        $type=trim($input['type']?? '');
        $description=trim($input['description']?? '');
        $allowedTypes = [
           'improper_word',
           'harassment',
           'spam',
           'other'
        ];
        if( $post_id===0){
            Response::json([
                "status"=>false,
                "message"=>"Invalid input"
            ]);
        }
        if(!in_array($type,$allowedTypes)){
            Response::json([
                "status"=>false,
                "message"=>"Invalid report type"
            ]);
        }
        //check post creator exists
        $postSql="SELECT creator_user_id from posts WHERE post_id=?";    
        $postStmt=$conn->prepare($postSql);
        $postStmt->bind_param("i",$post_id);
        $postStmt->execute();  
        $postResult=$postStmt->get_result();
        if($postResult->num_rows===0){
            Response::json([
                "status"=>false,
                "message"=>"post not found"
            ]);
        }
        $post=$postResult->fetch_assoc();
        $creator_user_id=(int)$post['creator_user_id'];//reported_user_id
        
        if($reporter_user_id===$creator_user_id){
            Response::json([
                "status"=>false,
                "message"=>"cannot report yourself"
            ]);
        }
        

        $reportPostInsertSql="INSERT INTO reported_post (reporter_user_id, post_id,reported_user_id,type, description)
                                VALUES (?,?,?,?,?)";
        $reportPostInsert=$conn->prepare($reportPostInsertSql);
        $reportPostInsert->bind_param("iiiss",$reporter_user_id,$post_id,$creator_user_id,$type,$description);
        $reportPostInsert->execute();
        Response::json([
            "status"=>true,
            "message"=>"Post reported successfully"
        ]);


       
    }
    
}