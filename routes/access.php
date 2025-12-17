<?php
    $server = $_SERVER['SERVER_NAME'] ?? "localhost";
    if($server == "localhost"){
        return [
            'public' => [
                '/yukai_backend/public/login.php',
                '/yukai_backend/public/register.php',
            ],
        
            'admin' => [
                '/yukai_backend/public/admin.php',
            ],
        ];
    }else{
        return [
            'public' => [
                "/public/login.php",
                "/public/register.php"
            ],
            'admin' => [
                "/public/admin.php"
            ]
        ];
    }