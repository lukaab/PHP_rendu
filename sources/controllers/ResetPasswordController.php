<?php

if (session_status() === PHP_SESSION_NONE) session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

class ResetPasswordController
{
    public static function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // echo "Données reçuees via GET : <pre>";
        // print_r($_GET);
        // echo "</pre>";
        // echo "Page de réinitialisation atteinte.<br>";

        // Récupérer le token depuis l'URL (paramètre GET)
        $token = $_GET['token'] ?? null;
        // echo "Token reçu depuis l'URL : " . htmlspecialchars($token) . "<br>";

        // Vérifier si un token est présent
        if (!$token) {
            $_SESSION["error"] = "Lien de réinitialisation invalide.";
            header("Location: /forgot-password");
            exit();
        }

        require_once __DIR__ . "/../views/reset-password/index.php";
    }

    public static function post(): void
    {
        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $token = $_POST["token"];
        $newPassword = password_hash($_POST["password"], PASSWORD_DEFAULT);

        // Vérifier si le token est valide et non expiré
        $query = $databaseConnection->prepare(
            "SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()"
        );
        $query->execute(["token" => $token]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION["error"] = "Le lien de réinitialisation est invalide ou expiré.";
            header("Location: /forgot-password");
            exit();
        }

        // Mettre à jour le mot de passe de l'utilisateur
        $updateQuery = $databaseConnection->prepare(
            "UPDATE users SET password = :password WHERE email = :email"
        );
        $updateQuery->execute(["password" => $newPassword, "email" => $row["email"]]);

        // Supprimer le token après utilisation
        $deleteQuery = $databaseConnection->prepare("DELETE FROM password_resets WHERE email = :email");
        $deleteQuery->execute(["email" => $row["email"]]);

        $_SESSION["success"] = "Votre mot de passe a été mis à jour.";
        header("Location: /login");
        exit();
    }
}