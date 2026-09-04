<?php
/** Gestione utenti admin/redattori – solo amministratori */
if ($utente['ruolo'] !== 'admin') {
    echo '<div class="alert alert-err">Solo gli amministratori possono gestire gli utenti.</div>';
    return;
}
$utenti = db()->query('SELECT id, email, nome, ruolo, creato_il, ultimo_accesso FROM utenti ORDER BY creato_il')->fetchAll();
?>

<div class="page-title-row">
    <h1>Utenti di gestione</h1>
</div>
<p class="muted">Chi può accedere a questo pannello. <strong>Amministratore</strong>: tutto. <strong>Redattore</strong>: solo articoli del blog.</p>

<div class="panel">
    <table class="tabella">
        <tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Ultimo accesso</th><th></th></tr>
        <?php foreach ($utenti as $u): ?>
            <tr>
                <td><strong><?= e($u['nome'] ?: '–') ?></strong><?= $u['id'] === (int)$utente['id'] ? ' <span class="muted">(tu)</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="stato stato-<?= $u['ruolo'] === 'admin' ? 'admin' : 'redattore' ?>"><?= e($u['ruolo']) ?></span></td>
                <td><?= $u['ultimo_accesso'] ? e(date('d.m.Y H:i', strtotime($u['ultimo_accesso']))) : 'mai' ?></td>
                <td class="azioni">
                    <?php if ($u['id'] !== (int)$utente['id']): ?>
                        <form method="post" action="<?= e(admin_url('elimina-utente')) ?>" onsubmit="return confirm('Eliminare l\'utente <?= e(addslashes($u['email'])) ?>?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<h2>Nuovo utente</h2>
<form method="post" action="<?= e(admin_url('salva-utente')) ?>" class="form panel-form">
    <?= csrf_campo() ?>
    <label>Nome *
        <input type="text" name="nome" required maxlength="120">
    </label>
    <label>Email *
        <input type="email" name="email" required maxlength="160">
    </label>
    <label>Ruolo
        <select name="ruolo">
            <option value="redattore">Redattore (solo articoli)</option>
            <option value="admin">Amministratore (tutto)</option>
        </select>
    </label>
    <label>Password iniziale * (min. 8 caratteri)
        <input type="password" name="password" required minlength="8">
    </label>
    <p class="hint">Comunica tu la password alla persona: potrà cambiarla al primo utilizzo in una prossima versione.</p>
    <button type="submit" class="btn btn-primary">Crea utente</button>
</form>
