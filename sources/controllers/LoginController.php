<?php

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../requests/LoginRequest.php";

class LoginController
{
  public static function index(): void
  {
    require_once __DIR__ . "/../views/login/index.php";
  }

    public static function post(): void
    {
      if (session_status() === PHP_SESSION_NONE) session_start(); // Démarrer la session

      $request = new LoginRequest();
      $user = User::findOneByEmail($request->email);

      if (!$user || !$user->isValidPassword($request->password)) {
        $_SESSION["error"] = "L'adresse email ou le mot de passe sont incorrects.";
        header("Location: /login");
        exit();
    }

      // Stocker l'utilisateur dans la session
      $_SESSION["user_id"] = $user->id;
      $_SESSION["user_first_name"] = $user->first_name;
      $_SESSION["user_last_name"] = $user->last_name;
      $_SESSION["user_email"] = $user->email;

      // Rediriger après connexion
      header("Location: /home");
      exit();
    }
}
