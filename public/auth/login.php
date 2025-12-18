<?php
    // check if route is accessible
    require_once __DIR__ . '/../../middleware/route_guard.php';
    require_once __DIR__ . '/../../services/tokenService.php';
    require_once __DIR__ . '/../../services/passwordService.php';
    include "../../utilities/dbconfig.php";

    // get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input["username"];
    $password = $input["password"];

    // response array
    // {status: boolean, message: string}
    $response = [];

    //check username and email exit
    $checkUsername="select * from users where email=? or username=?";
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
    else{
        $user = $result->fetch_assoc();
        if($user['is_active'] == 0){
            $response["status"] = false;
            $response["message"] = "Account is inactive";
            echo json_encode($response);
            exit(); 
        }

    }
    // check password correct
    // TODO: need to change to hashed password check
    // $user = $result->fetch_assoc();
    echo json_encode($user['password']);
    if(!PasswordService::verify($password,$user['password'])){ //password hash function
    // $checkCorrect = "select * from users where (username=? or email=?) and password=? ";
    // $checkCorrectstmt = $conn->prepare($checkCorrect);
    // $checkCorrectstmt->bind_param("sss", $username, $username, $password);
    // $checkCorrectstmt->execute();
    // $result= $checkCorrectstmt->get_result();

        
    
    // incorrect password
    // if($result->num_rows==0){
        $response["status"] = false;
        $response["message"] = "You should check your password!";
        echo json_encode($response);
        exit();
    }    

    // correct user and password
    // access granted
    // generate JWT access Token
    $user = $result->fetch_assoc();
    $accessToken = TokenService::generateAccessToken($user["user_id"]);

    // generate refresh Token
    [$refreshToken, $refreshHash] = TokenService::generateRefreshToken();
    $expireAt = date("Y-m-d H:i:s", time() + 604800); // 7 days

    // store refresh token hash in database
    $storeSql = "update users set refresh_token=?, refresh_token_expire_time=? where user_id=?";
    $storeStmt = $conn->prepare($storeSql);
    $storeStmt->bind_param("ssi", $refreshHash, $expireAt, $user["user_id"]);
    $storeStmt->execute();

    $_SESSION['user_id'] = $user["user_id"];
    $response["status"] = true;
    $response["message"] = "User and Password are correct";
    $response["accessToken"] = $accessToken;
    $response["refreshToken"] = $refreshToken;
    echo json_encode($response);
    exit();
    
?>                                               