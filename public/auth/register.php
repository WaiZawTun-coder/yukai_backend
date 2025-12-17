<?php

include "../utilities/dbconfig.php";
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
    $sql1="INSERT INTO users (username, password, email) VALUES(?,?,?)";
    $result1=$conn->prepare($sql1);
    $result1->bind_param(  "sss",$username,$password, $email);
    $result1->execute();
    $response["status"] = true;
    $response["message"] = "Regirstration is successful";
  }
  
//   $response["data"] = ["username" => $username, "password" => $password, "email" =>$email];

    echo json_encode($response);
    exit();



?>