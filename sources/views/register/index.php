<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <style>
        .error-message {
            background-color: #ffdddd;
            color: #d8000c;
            padding: 10px;
            border: 1px solid #d8000c;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Inscription</h1>

    <!-- Affichage du message d'erreur en rouge -->
    <?php if (isset($_SESSION["error"])): ?>
        <div class="error-message">
            <?= $_SESSION["error"]; ?>
        </div>
        <?php unset($_SESSION["error"]); ?> <!-- Supprime l'erreur après affichage -->
    <?php endif; ?>

    <form method="POST" action="/register">
        <input type="text" name="first_name" placeholder="Prénom" required value="<?= $_SESSION["old_first_name"] ?? '' ?>">
        <input type="text" name="last_name" placeholder="Nom de famille" required value="<?= $_SESSION["old_last_name"] ?? '' ?>">
        <input type="email" name="email" placeholder="Email" required value="<?= $_SESSION["old_email"] ?? '' ?>">
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">S'inscrire</button>
    </form>
    <div class="register-link">
        <a href="/login">Déjà un compte ? Veuillez vous connecter</a>
    </div>

    <?php 
    // Supprimer les anciennes valeurs après affichage
    unset($_SESSION["old_first_name"], $_SESSION["old_last_name"], $_SESSION["old_email"]); 
    ?>
</body>
</html>