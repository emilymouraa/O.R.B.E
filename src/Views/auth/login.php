<h2>Login</h2>

<?php if (!empty($error)): ?>
<p style="color:red"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/login">

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Senha</label>
    <input type="password" name="password" required>

    <button type="submit">Entrar</button>

</form>