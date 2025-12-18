<?php
    $server = $_SERVER['SERVER_NAME'] ?? "localhost";
    if($server == "localhost"){
        $host = "localhost";
        $port = "3306";
        $username = "root";
        $password = "W@i1Z@w4Tun2002";
    }else{
        $host = getenv("DB_HOST");
        $port = getenv("DB_PORT");
        $username = getenv("DB_USER");
        $password = getenv("DB_PASSWORD");
    }

    $databaseName = "yukai";

    $conn = new mysqli($host, $username, $password, $databaseName, $port);
    if($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
    }