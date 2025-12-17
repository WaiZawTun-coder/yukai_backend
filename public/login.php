<?php
    // check if route is accessible
    require_once __DIR__ . '/../middleware/route_guard.php';
    include "../utilities/dbconfig.php";

    // get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input["username"];
    $password = $input["password"];

    // response array
    // {status: boolean, message: string}
    $response = [];

    //check username and email exit
    $checkUsername="select * from users where email=? or username=? ";
    $checkUserstmt = $conn->prepare($checkUsername); 
    $checkUserstmt->bind_param("ss", $username, $username);
    $checkUserstmt->execute();
    $result = $checkUserstmt->get_result();  
    if($result->num_rows == 0){
        $response["status"] = false;
        $response["message"] = "Username or email are not found";
        echo json_encode($response);
        exit(); 
    }
    
    // check password correct
    $user = $result->fetch_assoc();
    $checkCorrect = "select * from users where (username=? or email=?) and password=? ";
    $checkCorrectstmt = $conn->prepare($checkCorrect);
    $checkCorrectstmt->bind_param("sss", $username, $username, $password);
    $checkCorrectstmt->execute();
    $result= $checkCorrectstmt->get_result();

    // incorrect password
    if($result->num_rows==0){
        $response["status"] = false;
        $response["message"] = "You should check your password!";
        echo json_encode($response);
        exit();
    }    

    // correct user and password
    // access granted
    $_SESSION['user_id'] = $user["user_id"];
    $response["status"] = true;
    $response["message"] = "User and Password are correct";
    echo json_encode($response);
    exit();
    
?>                                               