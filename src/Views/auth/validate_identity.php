<?php
/*
 * Views/auth/validate_identity.php
 * Tela de validação de identidade — fluxo de primeiro acesso do ORBE.
 * Exibida quando o usuário loga pela primeira vez com a senha padrão.
 */
$pageTitle = 'Redefinir Senha';
$bodyClass = 'auth-page';
require __DIR__ . '/../layout/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <div class="logo-container">
            <img src="/assets/images/orbe_logo.png" alt="ORBE Logo" class="logo">
        </div>

        <h2>Validação de Identidade</h2>
        <p class="auth-subtitle">
            É o seu primeiro acesso. Confirme seus dados para continuar.
        </p>
        <br>

        <form id="form-validar" novalidate>
            <div class="form-group">
                <label for="email">E-mail Corporativo</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seu.nome@prf.govmg.com.br"
                    required
                >
            </div>

            <div class="form-group">
                <label for="cpf">CPF</label>
                <input
                    type="text"
                    id="cpf"
                    name="cpf"
                    placeholder="000.000.000-00"
                    maxlength="14"
                    required
                >
            </div>

            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento</label>
                <input
                    type="date"
                    id="data_nascimento"
                    name="data_nascimento"
                    required
                >
            </div>

            <button type="submit" class="btn-primary" id="btn-validar">
                Confirmar
            </button>
        </form>

        <p class="register-link">
            <a href="/login">← Voltar ao login</a>
        </p>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

<script>
// DEPOIS — o script completo corrigido:
document.getElementById('cpf').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').substring(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    this.value = v;
});

document.getElementById('form-validar').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-validar');
    btn.disabled = true;
    btn.textContent = 'Verificando...';
    const body = new FormData(this);
    try {
        const response = await fetch('/validar-identidade', {
            method: 'POST',
            body: body,
        });
        const data = await response.json();
        if (!response.ok || data.error) {
            Toast.error('Falha na validação', data.error || 'Verifique os dados e tente novamente.');
            return;
        }
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (err) {
        Toast.error('Erro de conexão', 'Não foi possível contactar o servidor. Tente novamente.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirmar';
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>