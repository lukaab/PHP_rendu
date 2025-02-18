<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../models/User.php";

class ForgotPasswordController
{
    public static function index(): void
    {
        require_once __DIR__ . "/../views/forgot-password/index.php";
    }

    public static function post(): void
    {
        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $email = strtolower(trim($_POST["email"]));

        // Vérifier si l'email existe
        $query = $databaseConnection->prepare("SELECT id FROM users WHERE email = :email");
        $query->execute(["email" => $email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION["error"] = "Aucun compte trouvé avec cet email.";
            header("Location: /forgot-password");
            exit();
        }

        // Générer un token unique et l'expiration
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Sauvegarder le token en base
        $insertQuery = $databaseConnection->prepare(
            "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)"
        );
        $insertQuery->execute([
            "email" => $email,
            "token" => $token,
            "expires" => $expires
        ]);

        // Envoyer l'email avec Resend
        $baseURL = "http://localhost:8000";
        $resetLink = "$baseURL/reset-password?token=" . $token;
        $subject = "Réinitialisation de votre mot de passe";
        $body = "Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href='$resetLink'>$resetLink</a>";

        self::sendEmail($email, $subject, $body);

        $_SESSION["success"] = "Un email de réinitialisation a été envoyé.";
        header("Location: /forgot-password");
        exit();
    }

    private static function sendEmail($to, $subject, $body)
    {
        $apiKey = "re_i7foAXBE_GkKPF1gpxrBjxYKCcDNGvhG7";

        $ch = curl_init("https://api.resend.com/emails");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "from" => "onboarding@resend.dev",
            "to" => $to,
            "subject" => $subject,
            "html" => $body
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}