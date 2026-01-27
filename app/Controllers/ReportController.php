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
        

        $reportPostInsertSql="INSERT INTO reported_posts (reporter_user_id, post_id,reported_user_id,type, description)
                                VALUES (?,?,?,?,?)";
        $reportPostInsert=$conn->prepare($reportPostInsertSql);
        $reportPostInsert->bind_param("iiiss",$reporter_user_id,$post_id,$creator_user_id,$type,$description);
        $reportPostInsert->execute();
        Response::json([
            "status"=>true,
            "message"=>"Post reported successfully"
        ]);


       
    }
    public static function reported_acc(){
        $conn=Database::connect();
        $input=Request::json();
        $reporter_user_id=(int)($input['reporter_user']?? 0);
        $reported_user_id=(int)($input['reported_user']?? 0);
        $type=trim($input['type']?? '');
        $description=trim($input['description']?? '');
        // $status = (string) ($input['status'] ?? '');
        $allowedType=[
            'fake_account',
            'harassment',
            'spam',
            'impersonation',
            'other'];

        if(!in_array($type,$allowedType)){
            Response::json([
                "status"=>false,
                "message"=>"Invalid report type"
            ]);
        }
         if($reporter_user_id<=0 || $reported_user_id<=0){
            Response::json([
                "status"=>false,
                "message"=>"Invalid input"
            ]);
            return;
        }
        //cannot report yourself
        if($reporter_user_id===$reported_user_id){
            Response::json([
                "status"=>false,
                "message"=>"You cannot report yourself"
            ]);
        }
    
        //check users exist
        $checkUsers=$conn->prepare("SELECT user_id from users WHERE user_id in(? ,?)");
        $checkUsers->bind_param("ii",$reporter_user_id,$reported_user_id);
        $checkUsers->execute();
        $checkUsers->store_result();
        if($checkUsers->num_rows!==2){
            Response::json([
                "status"=>false,
                "message"=>"Invalid input:users do not exists"
            ]);
            return;
        }
        
        $insertReported_accSql="INSERT INTO reported_acc (reporter_user_id, reported_user_id,type,description) VALUES(?,?,?,?)";
        $insertReported=$conn->prepare($insertReported_accSql);
        $insertReported->bind_param("iiss",$reporter_user_id,$reported_user_id,$type,$description);
        $insertReported->execute();
        Response::json([
            "status"=>true,
            "message"=>"acc reported successfully"
        ]);

    }
    
}