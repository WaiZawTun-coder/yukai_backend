<?php
    include "../utilities/dbconfig.php";

    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input["username"];
    $password = $input["password"];
    $response = [];
    // check if valid credentianal
    // true
    $checkUsername="select * from users where email=? or username=? ";
    $checkUserstmt = $conn->prepare($checkUsername);//check username and email
    $checkUserstmt->bind_param("ss", $username, $username);
    $checkUserstmt->execute();
    $result = $checkUserstmt->get_result();  
    if($result->num_rows == 0){
        $response["status"] = false;
        $response["message"] = "Username or email are not found";
        echo json_encode($response);
        exit(); 
    }
    else{
        $user = $result->fetch_assoc();
        $checkCorrect = "select * from users where (username=? or email=?) and password=? ";
        $checkCorrectstmt = $conn->prepare($checkCorrect);
        $checkCorrectstmt->bind_param("sss", $username, $username, $password);
        $checkCorrectstmt->execute();
        $result= $checkCorrectstmt->get_result();
        if($result->num_rows==0){
            $response["status"] = false;
            $response["message"] = "You should check your password!";
            echo json_encode($response);
            exit();

        }
        else{
            $response["status"] = true;
            $response["message"] = "User and Password are correct";
            echo json_encode($response);
            exit();

        }


    }

    
    

?>                                               