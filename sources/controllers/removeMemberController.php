<?php

class RemoveMemberController
{
    public static function post(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION["user_id"], $_POST["group_id"], $_POST["user_id"])) {
            $_SESSION["group_error"] = "Données invalides.";
            header("Location: /home");
            exit();
        }

        $group_id = $_POST["group_id"];
        $user_id = $_POST["user_id"];

        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Vérifier si l'utilisateur connecté est bien le propriétaire du groupe
        $query = $databaseConnection->prepare("SELECT owner_id FROM groups WHERE id = :group_id");
        $query->execute(["group_id" => $group_id]);
        $groupOwner = $query->fetch();

        if ($_SESSION["user_id"] != $groupOwner["owner_id"]) {
            $_SESSION["group_error"] = "Vous n'avez pas le droit de supprimer un membre.";
            header("Location: /home");
            exit();
        }

        // Supprimer l'utilisateur du groupe
        $query = $databaseConnection->prepare("
            DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id
        ");
        $query->execute(["group_id" => $group_id, "user_id" => $user_id]);

        $_SESSION["group_success"] = "Utilisateur supprimé avec succès.";
        header("Location: /home");
        exit();
    }
}