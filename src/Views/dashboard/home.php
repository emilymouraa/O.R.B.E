<?php
/*
 * Views/dashboard/home.php
 * Dashboard de gestão de usuários.
 * Todo o CSS vem de /assets/css/style.css via header.php.
 */
$pageTitle = 'Gestão de Usuários';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php'; 
?>

    <div class="main-content">
        <header class="header-section">
            <div class="title-group">
                <h1>Gestão de Usuários</h1>
                <p>Cadastre e gerencie os usuários do sistema</p>
                <span class="counter" id="contador">Carregando...</span>
            </div>
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <button class="btn-new" onclick="openModal()">
                <i class="fas fa-plus"></i> Novo Usuário
            </button>
            <?php endif; ?>
        </header>

        <section class="filters-container">
            <div class="filter-group">
                <input type="text" id="filtro-search" placeholder="Buscar por nome ou e-mail...">
            </div>
            <div class="filter-group">
                <select id="filtro-role">
                    <option value="">Todos os Perfis</option>
                    <option value="admin">Administrador</option>
                    <option value="gestor">Gestor</option>
                    <option value="user">Usuário</option>
                </select>
            </div>
            <div class="filter-group">
                <select id="filtro-ativo">
                    <option value="">Todos os Status</option>
                    <option value="true">Ativo</option>
                    <option value="false">Inativo</option>
                </select>
            </div>
            <div class="filter-group">
                <select id="filtro-unidade">
                    <option value="">Todas as Unidades</option>
                </select>
            </div>
        </section>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Unidade</th>
                        <th>Status</th>
                        <th>Data Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-body">
                    <tr>
                        <td colspan="7" class="table-feedback">
                            <i class="fas fa-spinner fa-spin"></i>
                            Carregando usuários...
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="pagination" id="paginacao"></div>
        </div>

        <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

        <div id="modalCadastro" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo">Cadastrar Novo Usuário</h2>
                    <button type="button" class="close-btn" onclick="dismissModal()" aria-label="Fechar">&times;</button>
                </div>
                <form id="formUsuario" novalidate>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="m-nome">Nome Completo *</label>
                            <input type="text" id="m-nome" name="name" placeholder="Digite o nome completo" required>
                        </div>
                        <div class="form-group">
                            <label for="m-email">E-mail *</label>
                            <input type="email" id="m-email" name="email" placeholder="email@exemplo.com" required>
                        </div>
                        <div class="form-group">
                            <label for="m-cpf">CPF *</label>
                            <input type="text" id="m-cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
                        </div>
                        <div class="form-group">
                            <label for="m-cargo">Cargo *</label>
                            <select id="m-cargo" name="cargo" required>
                                <option value="">Selecione a classe...</option>
                                <option value="3a_classe">3ª Classe</option>
                                <option value="2a_classe">2ª Classe</option>
                                <option value="1a_classe">1ª Classe</option>
                                <option value="classe_especial">Classe Especial</option>
                                <option value="chefe_divisao">Chefe de Divisão</option>
                                <option value="diretor_geral">Diretor-Geral</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="m-role">Perfil *</label>
                            <select id="m-role" name="role" required disabled>
                                <option value="user">Usuário</option>
                                <option value="gestor">Gestor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="m-unidade">Unidade *</label>
                            <select id="m-unidade" name="unidade_id" required>
                                <option value="">Selecione a unidade...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="m-status">Status *</label>
                            <select id="m-status" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="m-data-nascimento">Data de Nascimento</label>
                            <input type="date" id="m-data-nascimento" name="data_nascimento">
                        </div>
                        <div class="form-group">
                            <label for="m-data-admissao">Data de Admissão</label>
                            <input type="date" id="m-data-admissao" name="data_admissao">
                        </div>
                        <div class="form-group">
                            <label for="m-data-cadastro">Data de Cadastro</label>
                            <input type="text" id="m-data-cadastro" name="data_cadastro" readonly>
                        </div>
                    </div>
                    <div id="feedback" class="feedback-msg"></div>
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn-save" id="btnSalvar">
                            <i class="fas fa-save"></i> Salvar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>     

<?php require __DIR__ . '/../layout/footer.php'; ?>