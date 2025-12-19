<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../routes/api.php";
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . "/../app/Core/Request.php";
require_once __DIR__ . "/../app/Core/Response.php";

require_once __DIR__ . "/../app/Controllers/AuthController.php";

Router::dispatch();