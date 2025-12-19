<?php
namespace App\Controllers;

class AuthController{
    public static function index(){
        echo json_encode(["message" => "authController"]);
    }

    public static function login(){
        echo json_encode(["message" => "login"]);
    }

    public static function register(){
        echo json_encode(["message" => "register"]);
    }

    public static function profile(){
        echo json_encode(["message" => "profile"]);
    }
}