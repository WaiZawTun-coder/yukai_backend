<?php
require_once __DIR__ . '/../middleware/route_guard.php';

require "../utilities/dbconfig.php";

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "User ID: " . $row['user_id'] . " - Name: " . $row["username"] . "<br>";
    }
}else{
    echo "0 results found.";
}