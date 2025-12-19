<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../routes/api.php";
require_once __DIR__ . "/../app/Controllers/AuthController.php";

Router::dispatch();