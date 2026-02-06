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
    /* ---------- Get All Reported Posts (status is pending) ---------- */
    public static function getReporPosts() {
    $conn = Database::connect();

    // Current page
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;

    /* ---------- COUNT TOTAL ROWS ---------- */
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reported_posts rp where rp.status='pending' ");
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();

    $totalRecords = (int)$countResult['total'];
    $totalPages = ceil($totalRecords / $limit);

    if ($totalRecords === 0) {
        Response::json([
            "status" => false,
            "message" => "Reported post is not found"
        ]);
        return;
    }

    /* ---------- FETCH DATA ---------- */
    $stmt = $conn->prepare(
        "SELECT *
         FROM reported_posts rp
         WHERE rp.status='pending'

         ORDER BY rp.reported_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportedPosts = [];
    while ($row = $result->fetch_assoc()) {
        $reportedPosts[] = $row;
    }

    /* ---------- RESPONSE ---------- */
    Response::json([
        "status" => true,
        "current_page" => $page,
        "limit" => $limit,
        "total_pages" => $totalPages,
        "total_records" => $totalRecords,
        "data" => $reportedPosts
    ]);
}

/* ---------- Get All Reported Accounts (status is pending) ---------- */
    public static function getReportedAccounts() {
    $conn = Database::connect();

    // Current page
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;

    /* ---------- COUNT TOTAL ROWS ---------- */
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reported_accounts ra where rp.status='pending'");
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();

    $totalRecords = (int)$countResult['total'];
    $totalPages = ceil($totalRecords / $limit);

    if ($totalRecords === 0) {
        Response::json([
            "status" => false,
            "message" => "Reported Account is not found"
        ]);
        return;
    }

    /* ---------- FETCH DATA ---------- */
    $stmt = $conn->prepare(
        "SELECT *
         FROM reported_accounts ra
         WHERE ra.status='pending'

         ORDER BY ra.created_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportedAccounts = [];
    while ($row = $result->fetch_assoc()) {
        $reportedAccounts[] = $row;
    }

    /* ---------- RESPONSE ---------- */
    Response::json([
        "status" => true,
        "current_page" => $page,
        "limit" => $limit,
        "total_pages" => $totalPages,
        "total_records" => $totalRecords,
        "data" => $reportedAccounts
    ]);
}
    
}