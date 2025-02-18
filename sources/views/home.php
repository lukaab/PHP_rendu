<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Vérifier si l'utilisateur est bien connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: /login");
    exit();
}

// Message d'erreur ou de succès
$error = $_SESSION["upload_error"] ?? null;
$success = $_SESSION["upload_success"] ?? null;
unset($_SESSION["upload_error"], $_SESSION["upload_success"]);

// Connexion à la base de données
$databaseConnection = new PDO(
    "mysql:host=mariadb;dbname=database",
    "user",
    "password",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 🔹 Récupérer le premier groupe de l'utilisateur pour l'upload
$query = $databaseConnection->prepare("
    SELECT group_id FROM group_members WHERE user_id = :user_id LIMIT 1
");
$query->execute(["user_id" => $_SESSION["user_id"]]);
$group = $query->fetch(PDO::FETCH_ASSOC);
$group_id = $group["group_id"] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil</title>
    <style>
        .message { padding: 10px; margin-bottom: 10px; text-align: center; }
        .error { background-color: #ffdddd; color: #d8000c; border: 1px solid #d8000c; }
        .success { background-color: #ddffdd; color: #008000; border: 1px solid #008000; }
    </style>
</head>
<body>
    <h1>Bienvenue, <?= htmlspecialchars($_SESSION["user_first_name"]) ?> !</h1>
    
    <!-- Affichage des messages -->
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- 🔹 Formulaire de création de groupe -->
    <h2>Créer un groupe</h2>
    <form action="/create-group" method="POST">
        <input type="text" name="group_name" placeholder="Nom du groupe" required>
        <button type="submit">Créer</button>
    </form>

    <!-- 🔹 Formulaire d'upload -->
    <h2>Uploader une photo</h2>
    <form action="/upload" method="POST" enctype="multipart/form-data">
    <label for="group">Sélectionnez un groupe :</label>
    <select name="group_id" id="group" required>
        <?php
        $query = $databaseConnection->prepare("
            SELECT groups.id, groups.name 
            FROM groups 
            JOIN group_members ON groups.id = group_members.group_id
            WHERE group_members.user_id = :user_id
        ");
        $query->execute(["user_id" => $_SESSION["user_id"]]);
        $groups = $query->fetchAll();

        foreach ($groups as $group): ?>
            <option value="<?= $group["id"] ?>"><?= htmlspecialchars($group["name"]) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="file" name="photo" accept="image/jpeg, image/png, image/gif" required>
    <button type="submit">Uploader</button>
</form>

    <!-- 🔹 Liste des groupes -->
    <h2>Mes groupes</h2>
<ul>
<?php
$databaseConnection = new PDO(
    "mysql:host=mariadb;dbname=database",
    "user",
    "password",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$query = $databaseConnection->prepare("
    SELECT groups.id, groups.name, groups.owner_id
    FROM groups 
    JOIN group_members ON groups.id = group_members.group_id
    WHERE group_members.user_id = :user_id
");
$query->execute(["user_id" => $_SESSION["user_id"]]);
$groups = $query->fetchAll();

foreach ($groups as $group): ?>
    <li>
        <strong><?= htmlspecialchars($group["name"]) ?></strong>
        <a href="/group/<?= $group["id"] ?>">Voir</a>

        <!-- Ajout d'un membre -->
        <?php if ($_SESSION["user_id"] == $group["owner_id"]): ?>
            <form action="/add-member" method="POST" style="display:inline;">
                <input type="hidden" name="group_id" value="<?= $group["id"] ?>">
                <input type="email" name="user_email" placeholder="Email de l'utilisateur" required>
                <select name="role">
                    <option value="read">Lecture seule</option>
                    <option value="write">Écriture</option>
                </select>
                <button type="submit">Ajouter</button>
            </form>
        <?php endif; ?>

        <!-- Liste des membres -->
        <ul>
            <?php
            $query = $databaseConnection->prepare("
                SELECT users.id, users.first_name, users.email, group_members.role 
                FROM users 
                JOIN group_members ON users.id = group_members.user_id 
                WHERE group_members.group_id = :group_id
            ");
            $query->execute(["group_id" => $group["id"]]);
            $members = $query->fetchAll();

            foreach ($members as $member): ?>
                <li>
                    <?= htmlspecialchars($member["first_name"]) ?> (<?= htmlspecialchars($member["email"]) ?>) - <?= htmlspecialchars($member["role"]) ?>
                    
                    <!-- Suppression du membre -->
                    <?php if ($_SESSION["user_id"] == $group["owner_id"] && $member["id"] != $_SESSION["user_id"]): ?>
                        <form action="/remove-member" method="POST" style="display:inline;">
                            <input type="hidden" name="group_id" value="<?= $group["id"] ?>">
                            <input type="hidden" name="user_id" value="<?= $member["id"] ?>">
                            <button type="submit">❌ Supprimer</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </li>
<?php endforeach; ?>
</ul>

    <div class="logout">
        <a href="/logout">Déconnexion</a>
    </div>
</body>
</html>