<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Service\ImageService;

class SaveController{
    public static function savePost(){
        $conn=Database::connect();
        $input=Request::json();
        $post_id=(int)(Request::input("post_id")?? 0);
        
    }
    
}