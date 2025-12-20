<?php

include "../../utilities/dbconfig.php";
require_once __DIR__.'/../../services/passwordService.php';


$input=json_decode(file_get_contents("php://input"),true);
$display_username= $input["display_username"];
$password= $input["password"];
$email= $input["email"];
  $response=[];
  $generatedUsername=usernameGenerator($conn,$display_username);
  $sql="SELECT email FROM users where email='$email'";
  $result=mysqli_query($conn,$sql);
  if(mysqli_num_rows($result)> 0){
    $response["status"] = false;
    $response["message"] = "Email already exists";
  }else{
    $generatedUsername = usernameGenerator($conn,$display_username);
    $insertUserSql="INSERT INTO users (username,display_username, password, email) VALUES(?,?,?,?)";
    $hashPwd=passwordService::hash($password);
    $insertUserStmt=$conn->prepare($insertUserSql);
    $insertUserStmt->bind_param(  "ssss",$generatedUsername,$display_username, $hashPwd, $email);
    $insertUserStmt->execute();
    $response["status"] = true;
    $response["message"] = "Regirstration is successful";
    $response["Data"] = ["display_username" => $display_username, "generatedUsername" =>$generatedUsername];
    
    
  }
  
//   $response["data"] = ["username" => $username, "password" => $password, "email" =>$email];

    echo json_encode($response);
    exit();

 function usernameGenerator($conn, $display_username){
        do {
        $newUsername = strtolower($display_username) . rand(1000,9999);

        //  $stmt= mysqli_stmt->prepare($conn,"SELECT id FROM users WHERE username=?");
        //  mysqli_stmt_bind_param($stmt, "s", $username);
        //  mysqli_stmt_execute($stmt);
        //  mysqli_stmt_store_result($stmt);
        $insertUsernameSql="SELECT user_id FROM users WHERE username=?";
        $insertUsernameStmt=$conn->prepare($insertUsernameSql);
        $insertUsernameStmt->bind_param("s",$newUsername);
        $insertUsernameStmt->execute();
        $insertUsernameStmt->bind_result($newUsername);
    } while (mysqli_stmt_num_rows($insertUsernameStmt) > 0);

    return $newUsername;
 }