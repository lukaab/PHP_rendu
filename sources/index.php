<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// echo "Requête reçue : " . $_SERVER['REQUEST_URI'] . "<br>";

// echo "TEST - `\$_GET` au tout début de `index.php` : <pre>";
// print_r($_GET);
// echo "</pre>";

// session_start();
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

require_once __DIR__ . "/core/Router.php";

require_once __DIR__ . "/controllers/LoginController.php";
require_once __DIR__ . "/controllers/RegisterController.php";
require_once __DIR__ . "/controllers/HomeController.php";
require_once __DIR__ . "/controllers/LogoutController.php";
require_once __DIR__ . "/controllers/ResetPasswordController.php";
require_once __DIR__ . "/controllers/ForgotPasswordController.php";
require_once __DIR__ . "/controllers/UploadController.php";
require_once __DIR__ . "/controllers/GroupController.php";
require_once __DIR__ . "/controllers/AddMemberController.php";
require_once __DIR__ . "/controllers/RemoveMemberController.php";


$router = new Router();

$router->get("/login", LoginController::class, "index");
$router->post("/login", LoginController::class, "post");

$router->get("/articles/{slug}", ArticleController::class, "index");

$router->get("/register", RegisterController::class, "index");

$router->post("/register", RegisterController::class, "post");
$router->get("/home", HomeController::class, "index");
$router->get("/logout", LogoutController::class, "index");

$router->get("/forgot-password", ForgotPasswordController::class, "index");
$router->post("/forgot-password", ForgotPasswordController::class, "post");

$router->get("/reset-password", ResetPasswordController::class, "index");
$router->post("/reset-password", ResetPasswordController::class, "post");

$router->post("/upload", UploadController::class, "post");

$router->post("/create-group", GroupController::class, "create");

$router->get("/group/{id}", GroupController::class, "view");

$router->post("/add-member", AddMemberController::class, "post");

$router->post("/remove-member", RemoveMemberController::class, "post");

$router->post("/delete-photo", UploadController::class, "delete");

$router->post("/toggle-visibility", UploadController::class, "toggleVisibility");


if (empty($_GET) && !empty($_SERVER["REQUEST_URI"])) {
    $query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
    if (!empty($query)) {
        parse_str($query, $_GET);
    }
}


$router->start();
