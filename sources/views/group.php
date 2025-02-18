<?php

error_log("🛠 DEBUG - Valeur de \$_GET : " . print_r($_GET, true));

error_log("📄 DEBUG - group.php correctement chargé");
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

error_log("👤 DEBUG - Utilisateur connecté avec ID : " . ($_SESSION["user_id"] ?? "Aucun"));

foreach (headers_list() as $header) {
    error_log("🔍 DEBUG - Headers envoyés : " . $header);
}

// Récupérer l'ID du groupe
error_log("🛠 DEBUG - Valeur de group_id: " . print_r($_GET, true));
$group_id = $_GET["id"] ?? null;
if (!$group_id) {
    error_log("🚨 DEBUG - Redirection car ID de groupe absent !");
    header("Location: /home");
    exit();
}

$databaseConnection = new PDO(
    "mysql:host=mariadb;dbname=database",
    "user",
    "password",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Récupérer le nom du groupe
$query = $databaseConnection->prepare("SELECT name FROM groups WHERE id = :group_id");
$query->execute(["group_id" => $group_id]);
$group = $query->fetch();

if (!$group) {
    $_SESSION["group_error"] = "Ce groupe n'existe pas.";
    print("2eme COND HOME");
    header("Location: /home");
    exit();
}

// Récupérer les photos du groupe
$query = $databaseConnection->prepare("
    SELECT id, file_name, public FROM uploads WHERE group_id = :group_id
");
$query->execute(["group_id" => $group_id]);
$photos = $query->fetchAll(PDO::FETCH_ASSOC);

foreach (headers_list() as $header) {
    error_log("🔍 DEBUG - Headers envoyés après group.php : " . $header);
}

// exit("✅ DEBUG - FIN FINALE de group.php");
error_log("📌 DEBUG - Si ce message s'affiche, le script continue après cette ligne !");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($group["name"]) ?></title>
</head>
<body>
    <h1>Groupe : <?= htmlspecialchars($group["name"]) ?></h1>

    <!-- Afficher les photos -->
    <?php if (empty($photos)): ?>
        <p>Aucune photo dans ce groupe.</p>
    <?php else: ?>
        <?php foreach ($photos as $photo): ?>
    <div>
        <img src="/uploads/<?= htmlspecialchars($photo["file_name"]) ?>" width="200">
        
        <!-- Bouton de suppression -->
        <form action="/delete-photo" method="POST" style="display: inline;">
            <input type="hidden" name="photo_id" value="<?= $photo["id"] ?>">
            <button type="submit">Supprimer</button>
        </form>

        <!-- Bouton de visibilité publique -->
        <form action="/toggle-visibility" method="POST" style="display: inline;">
            <input type="hidden" name="photo_id" value="<?= $photo["id"] ?>">
            <button type="submit">
                <?= $photo["public"] ? "Rendre privé" : "Partager publiquement" ?>
            </button>
        </form>

        <!-- Lien public si la photo est partagée -->
        <?php if ($photo["public"]): ?>
            <p>🔗 Lien public : <a href="/uploads/<?= htmlspecialchars($photo["file_name"]) ?>" target="_blank">Voir</a></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?> 
    <?php endif; ?>

    <br><br>
    <a href="/home">Retour à l'accueil</a>
</body>
</html>