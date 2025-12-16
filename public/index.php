<?php
    $server = $_SERVER['SERVER_NAME'] ?? "localhost";
    if($server == "localhost"){
        $host = "localhost";
        $port = "3306";
        $username = "root";
        $password = "W@i1Z@w4Tun2002";
    }else{
        $host = "database-yukai.j.aivencloud.com";
        $port = "17662";
        $username = "avnadmin";
        $password = "AVNS_pVnMw8HCSmWQ-wNYKNe";
    }

    $databaseName = "yukai";

    $conn = new mysqli($host, $username, $password, $databaseName, $port);
    if($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
    }else{
        echo "Connection successfully to $server";
    }