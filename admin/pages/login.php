<?php
/** Login del pannello */
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accesso – ASODOMI Gestione</title>
<link rel="icon" type="image/png" href="<?= e(asset_v('/assets/img/favicon.png')) ?>">
<link rel="stylesheet" href="<?= e(asset_v('/assets/css/styles.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_v('/admin/admin.css')) ?>">
</head>
<body class="admin-body login-body">
<div class="login-box">
    <p class="brand-name center">ASO<span>DOMI</span></p>
    <h1>Area di gestione</h1>

    <?php if ($errore): ?><div class="alert alert-err">⚠️ <?= e($errore) ?></div><?php endif; ?>

    <form method="post" class="form">
        <?= csrf_campo() ?>
        <label>Email
            <input type="email" name="email" required autofocus autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Accedi</button>
    </form>
    <p class="muted small center"><a href="<?= e(url('it', 'inicio')) ?>">← Torna al sito</a></p>
</div>
</body>
</html>
