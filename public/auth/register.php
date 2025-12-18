<?php

include "../../utilities/dbconfig.php";
require_once __DIR__.'/../../services/passwordService.php';


$input=json_decode(file_get_contents("php://input"),true);
$username= $input["username"];
$password= $input["password"];
$email= $input["email"];
  $response=[];
  $sql="SELECT email FROM users where email='$email'";
  $result=mysqli_query($conn,$sql);
  if(mysqli_num_rows($result)> 0){
    $response["status"] = false;
    $response["message"] = "Email already exists";
  }else{
    $insertUserSql="INSERT INTO users (username, password, email) VALUES(?,?,?)";
    $hashPwd=passwordService::hash($password);
    $insertUserStmt=$conn->prepare($insertUserSql);
    $insertUserStmt->bind_param(  "sss",$username,$hashPwd, $email);
    $insertUserStmt->execute();
    $response["status"] = true;
    $response["message"] = "Regirstration is successful";
  }
  
//   $response["data"] = ["username" => $username, "password" => $password, "email" =>$email];

    echo json_encode($response);
    exit();



?>