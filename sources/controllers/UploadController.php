<?php

class UploadController
{
    public static function post(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION["user_id"])) {
            $_SESSION["upload_error"] = "Vous devez être connecté pour uploader une photo.";
            header("Location: /home");
            exit();
        }

        // Vérifier si un fichier a été envoyé
        if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
            $_SESSION["upload_error"] = "Erreur lors de l'upload du fichier.";
            header("Location: /home");
            exit();
        }

        // Vérifier si un group_id est bien fourni
        if (!isset($_POST["group_id"]) || empty($_POST["group_id"])) {
            $_SESSION["upload_error"] = "Aucun groupe sélectionné.";
            header("Location: /home");
            exit();
        }

        $group_id = $_POST["group_id"];
        $user_id = $_SESSION["user_id"];

        $databaseConnection = new PDO(
            "mysql:host=mariadb;dbname=database",
            "user",
            "password",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // 🔹 Vérifier les droits de l'utilisateur dans le groupe
        $query = $databaseConnection->prepare("
            SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id
        ");
        $query->execute([
            "group_id" => $group_id,
            "user_id" => $user_id
        ]);

        $member = $query->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            $_SESSION["upload_error"] = "Vous ne faites pas partie de ce groupe.";
            header("Location: /home");
            exit();
        }

        // 🔹 Vérifier si l'utilisateur a seulement un accès en lecture (`read`)
        if ($member["role"] === "read") {
            $_SESSION["upload_error"] = "Vous n'avez pas la permission d'uploader des photos dans ce groupe.";
            header("Location: /home");
            exit();
        }

        // 🔹 Vérifier le fichier (taille, type)
        $file = $_FILES["photo"];
        $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
        $maxSize = 5 * 1024 * 1024; // 5 Mo

        if (!in_array($file["type"], $allowedTypes)) {
            $_SESSION["upload_error"] = "Seuls les fichiers JPG, PNG et GIF sont autorisés.";
            header("Location: /home");
            exit();
        }

        if ($file["size"] > $maxSize) {
            $_SESSION["upload_error"] = "Le fichier est trop volumineux (max 5 Mo).";
            header("Location: /home");
            exit();
        }

        // 🔹 Sauvegarde du fichier
        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . "-" . basename($file["name"]);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
            $_SESSION["upload_error"] = "Erreur lors de l'enregistrement du fichier.";
            header("Location: /home");
            exit();
        }

        // 🔹 Insérer l'image dans la base de données
        $query = $databaseConnection->prepare("
            INSERT INTO uploads (user_id, file_name, group_id, uploaded_at) 
            VALUES (:user_id, :file_name, :group_id, NOW())
        ");
        $query->execute([
            "user_id" => $user_id,
            "file_name" => $fileName,
            "group_id" => $group_id
        ]);

        $_SESSION["upload_success"] = "Photo uploadée avec succès !";
        header("Location: /home");
        exit();
    }

    public static function delete(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    error_log("🛠 DEBUG - Contenu de \$_POST : " . print_r($_POST, true));

    if (!isset($_SESSION["user_id"])) {
        $_SESSION["upload_error"] = "Vous devez être connecté pour supprimer une photo.";
        header("Location: /home");
        exit();
    }

    if (!isset($_POST["photo_id"])) {
        $_SESSION["upload_error"] = "Aucune photo spécifiée.";
        header("Location: /home");
        exit();
    }

    $photo_id = $_POST["photo_id"];
    $user_id = $_SESSION["user_id"];

    $databaseConnection = new PDO(
        "mysql:host=mariadb;dbname=database",
        "user",
        "password",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Vérifier si la photo existe et à qui elle appartient
    $query = $databaseConnection->prepare("
        SELECT uploads.user_id, uploads.file_name, uploads.group_id, groups.owner_id 
        FROM uploads 
        JOIN groups ON uploads.group_id = groups.id
        WHERE uploads.id = :photo_id
    ");
    $query->execute(["photo_id" => $photo_id]);
    $photo = $query->fetch(PDO::FETCH_ASSOC);

    error_log("🔍 DEBUG - Résultat de la requête photo : " . print_r($photo, true));


    if (!$photo) {
        $_SESSION["upload_error"] = "Photo introuvable.";
        header("Location: /home");
        exit();
    }

    // Vérifier si l'utilisateur a le droit de supprimer
    if ((int)$photo["user_id"] !== (int)$user_id && (int)$photo["owner_id"] !== (int)$user_id) {
        $_SESSION["upload_error"] = "Vous n'avez pas la permission de supprimer cette photo.";
        header("Location: /home");
        exit();
    }

    // Supprimer le fichier du dossier
    $filePath = __DIR__ . "/../uploads/" . $photo["file_name"];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Supprimer l'entrée de la base de données
    $query = $databaseConnection->prepare("DELETE FROM uploads WHERE id = :photo_id");
    $query->execute(["photo_id" => $photo_id]);

    $_SESSION["upload_success"] = "Photo supprimée avec succès.";
    header("Location: /home");
    exit();
}

public static function toggleVisibility(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION["user_id"])) {
        $_SESSION["upload_error"] = "Vous devez être connecté pour modifier la visibilité.";
        header("Location: /home");
        exit();
    }

    if (!isset($_POST["photo_id"])) {
        $_SESSION["upload_error"] = "Aucune photo spécifiée.";
        header("Location: /home");
        exit();
    }

    $photo_id = $_POST["photo_id"];
    $user_id = $_SESSION["user_id"];

    $databaseConnection = new PDO(
        "mysql:host=mariadb;dbname=database",
        "user",
        "password",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Vérifier si l'utilisateur est propriétaire de la photo
    $query = $databaseConnection->prepare("
        SELECT user_id, public FROM uploads WHERE id = :photo_id
    ");
    $query->execute(["photo_id" => $photo_id]);
    $photo = $query->fetch(PDO::FETCH_ASSOC);

    if (!$photo || ((int)$photo["user_id"] !== (int)$user_id && (int)$photo["owner_id"] !== (int)$user_id)) {
        $_SESSION["upload_error"] = "Vous ne pouvez pas modifier la visibilité de cette photo.";
        header("Location: /home");
        exit();
    }

    // Basculer la visibilité
    $newVisibility = $photo["public"] ? 0 : 1;
    $query = $databaseConnection->prepare("
        UPDATE uploads SET public = :public WHERE id = :photo_id
    ");
    $query->execute([
        "public" => $newVisibility,
        "photo_id" => $photo_id
    ]);

    $_SESSION["upload_success"] = "Visibilité modifiée.";
    header("Location: /home");
    exit();
}
}