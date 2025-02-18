<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
</head>
<body>
    <h1>Réinitialisation du mot de passe</h1>

    <?php if (isset($_SESSION["error"])): ?>
        <div class="error-message">
            <?= $_SESSION["error"]; ?>
        </div>
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>

    <!-- Vérification de la récupération du token -->
    <!-- <p>Token reçu : <?= htmlspecialchars($_GET['token'] ?? 'Aucun token') ?></p> -->

    <form method="POST" action="/reset-password">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
        <input type="password" name="password" placeholder="Nouveau mot de passe" required>
        <button type="submit">Réinitialiser</button>
    </form>
</body>
</html>