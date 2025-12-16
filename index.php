<?php
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    $host = "localhost:3306";
    $port = 3306;
    $username = "cooking_username";
    $password = "cooking_password";
    $dbname = "cooking";
}else {
    $host = "sql102.infinityfree.com";
    $port = 3306;
    $username = "if0_40685393";
    $password = "25ghMfSmRWpiZg";
    $dbname = "if0_40685393_testing";
}

$conn = new mysqli(
  $host,
  $username,
  $password,
  $dbname
);

if($conn->connect_error){
    echo ("Connection failed: " . $conn->connect_error);
}else
	echo "Successfully connected";