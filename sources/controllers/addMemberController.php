<?php

class AddMemberController
{
    public static function post(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION["user_id"])) {
            header("Location: /login");
            exit();
        }

        if (!isset($_POST["group_id"], $_POST["user_email"], $_POST["role"])) {
            $_SESSION["group_error"] = "Données invalides.";
            header("Location: /home");
            exit();
        }

        $group_id = $_POST["group_id"];
        $user_email = trim($_POST["user_email"]);
        $role = ($_POST["role"] === "write") ? "write" : "read";

        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Vérifier si l'utilisateur existe
        $query = $databaseConnection->prepare("SELECT id FROM users WHERE email = :email");
        $query->execute(["email" => $user_email]);
        $user = $query->fetch();

        if (!$user) {
            $_SESSION["group_error"] = "L'utilisateur n'existe pas.";
            header("Location: /home");
            exit();
        }

        $user_id = $user["id"];

        // Vérifier si l'utilisateur est déjà dans le groupe
        $query = $databaseConnection->prepare("
            SELECT id FROM group_members WHERE group_id = :group_id AND user_id = :user_id
        ");
        $query->execute(["group_id" => $group_id, "user_id" => $user_id]);
        $existingMember = $query->fetch();

        if ($existingMember) {
            $_SESSION["group_error"] = "L'utilisateur est déjà membre de ce groupe.";
            header("Location: /home");
            exit();
        }

        // Ajouter l'utilisateur au groupe avec le rôle sélectionné
        $query = $databaseConnection->prepare("
            INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, :role)
        ");
        $query->execute(["group_id" => $group_id, "user_id" => $user_id, "role" => $role]);

        $_SESSION["group_success"] = "Utilisateur ajouté avec succès !";
        header("Location: /home");
        exit();
    }
}