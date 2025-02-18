<?php

session_start(); // Démarrer la session

class RegisterController
{
    public static function index(): void
    {
      require_once __DIR__ . "/../views/register/index.php";
    }

    public static function post(): void
{
    try {
        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        if (empty($_POST["first_name"]) || empty($_POST["last_name"]) || empty($_POST["email"]) || empty($_POST["password"])) {
          echo "Tous les champs sont requis.";
          exit();
      }
            // Vérification du format de l'email
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $_SESSION["error"] = "L'adresse email est invalide.";
            $_SESSION["old_first_name"] = $_POST["first_name"];
            $_SESSION["old_last_name"] = $_POST["last_name"];
            $_SESSION["old_email"] = $_POST["email"];
            header("Location: /register");
            exit();
        }
        $domain = substr(strrchr($_POST["email"], "@"), 1);
        if (!checkdnsrr($domain, "MX")) {
            $_SESSION["error"] = "Le domaine de l'adresse email n'existe pas.";
            $_SESSION["old_first_name"] = $_POST["first_name"];
            $_SESSION["old_last_name"] = $_POST["last_name"];
            $_SESSION["old_email"] = $_POST["email"];
            header("Location: /register");
            exit();
        }

        // Récupérer et sécuriser les données du formulaire
        $firstName = trim(htmlspecialchars($_POST["first_name"]));
        $lastName = trim(htmlspecialchars($_POST["last_name"]));
        $email = strtolower(trim(htmlspecialchars($_POST["email"])));
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        // Vérifier si l'utilisateur existe déjà
        $checkUserQuery = $databaseConnection->prepare(
            "SELECT id FROM users WHERE email = :email"
        );
        $checkUserQuery->execute(["email" => $email]);

        // Vérifier si l'utilisateur existe déjà
        if ($checkUserQuery->fetch()) {
            $_SESSION["error"] = "Cet email est déjà utilisé.";
            $_SESSION["old_first_name"] = $_POST["first_name"];
            $_SESSION["old_last_name"] = $_POST["last_name"];
            $_SESSION["old_email"] = $_POST["email"];
            header("Location: /register");
            exit();
        }

        // Insérer l'utilisateur en base avec prénom et nom
        $insertUserQuery = $databaseConnection->prepare(
            "INSERT INTO users (first_name, last_name, email, password) 
             VALUES (:first_name, :last_name, :email, :password)"
        );

        $insertUserQuery->execute([
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email" => $email,
            "password" => $password
        ]);
        header("Location: /login");
        exit();
    } catch (PDOException $e) {
        echo "Erreur lors de l'inscription : " . $e->getMessage();
    }
}

}
