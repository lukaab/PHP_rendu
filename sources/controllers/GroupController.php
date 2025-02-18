<?php

class GroupController
{
    public static function view(int $group_id): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    
        error_log("🔍 DEBUG - Accès à GroupController::view() avec group_id = " . $group_id);
        error_log("🔍 DEBUG - Utilisateur connecté avec user_id = " . $_SESSION["user_id"]);
    
        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    
        // Vérification de l'appartenance au groupe
        $query = $databaseConnection->prepare("
            SELECT user_id FROM group_members WHERE group_id = :group_id AND user_id = :user_id
        ");
        $query->execute([
            "group_id" => $group_id,
            "user_id" => $_SESSION["user_id"]
        ]);
    
        $result = $query->fetch(PDO::FETCH_ASSOC);
    
        error_log("🔍 DEBUG - Résultat de la requête : " . print_r($result, true));
    
        if (!$result) {
            error_log("❌ DEBUG - Utilisateur non membre du groupe !");
            $_SESSION["group_error"] = "Vous n'avez pas accès à ce groupe.";
            header("Location: /home");
            exit();
        }
    
        error_log("✅ DEBUG - Accès autorisé au groupe !");
        error_log("📄 DEBUG - Chargement de group.php");
    
        // 🔹 Vider le buffer de sortie avant d'inclure la vue (évite les erreurs)
        if (ob_get_level()) {
            ob_end_clean();
        }
        $_GET["id"] = $group_id;
        // 🔹 Inclure la vue correctement
        require_once __DIR__ . "/../views/group.php";
        error_log("🚀 DEBUG - Après l'exécution de group.php, fin du GroupController.");
        exit();
    
        // ✅ Stopper l'exécution après affichage de la page
        // exit();
    }

    public static function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION["user_id"])) {
            header("Location: /login");
            exit();
        }

        if (empty($_POST["group_name"])) {
            $_SESSION["group_error"] = "Le nom du groupe est requis.";
            header("Location: /home");
            exit();
        }

        try {
            $databaseConnection = new PDO(
                "mysql:host=mariadb;dbname=database",
                "user",
                "password",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 🔹 Début de la transaction
            $databaseConnection->beginTransaction();

            // 🔹 Insérer le groupe
            $query = $databaseConnection->prepare(
                "INSERT INTO groups (name, owner_id) VALUES (:name, :owner_id)"
            );
            $query->execute([
                "name" => trim(htmlspecialchars($_POST["group_name"])),
                "owner_id" => $_SESSION["user_id"]
            ]);

            // 🔹 Récupérer l'ID du groupe nouvellement inséré
            $groupId = $databaseConnection->lastInsertId();

            // 🔹 Ajouter l'utilisateur au groupe en tant que propriétaire
            $query = $databaseConnection->prepare(
                "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'owner')"
            );
            $query->execute([
                "group_id" => $groupId,
                "user_id" => $_SESSION["user_id"]
            ]);

            // 🔹 Valider la transaction
            $databaseConnection->commit();

            $_SESSION["group_success"] = "Groupe créé avec succès.";
            header("Location: /home");
            exit();
    
        } catch (PDOException $e) {
            // 🔹 Annuler la transaction en cas d'erreur
            $databaseConnection->rollBack();
            $_SESSION["group_error"] = "Erreur lors de la création du groupe : " . $e->getMessage();
            header("Location: /home");
            exit();
        }
    }
}