<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
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
    <h1>Connexion</h1>

    <!-- Affichage du message d'erreur en rouge -->
    <?php if (isset($_SESSION["error"])): ?>
        <div class="error-message">
            <?= $_SESSION["error"]; ?>
        </div>
        <?php unset($_SESSION["error"]); ?> <!-- Supprime l'erreur après affichage -->
    <?php endif; ?>

    <form method="POST" action="/login">
        <input type="email" name="email" placeholder="Email" required value="<?= $_SESSION["old_email"] ?? '' ?>">
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>
    
    <div class="register-link">
        <a href="/register">Pas de compte ? Inscrivez-vous</a>
    </div>

    <?php 
    // Supprimer l'ancienne valeur de l'email après affichage
    unset($_SESSION["old_email"]); 
    ?>
</body>
</html>