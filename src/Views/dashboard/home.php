<?php
/*
 * Views/dashboard/home.php
 * Dashboard de gestão de usuários.
 * Todo o CSS vem de /assets/css/style.css via header.php.
 */
$pageTitle = 'Gestão de Usuários · ORBE';
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
                    <option value="ativo">Ativo</option>
                    <option value="afastado">Afastado</option>
                    <option value="aposentado">Aposentado</option>
                    <option value="licenca_maternidade">Licença Maternidade</option>
                    <option value="desligado">Desligado</option>
                    <option value="outros">Outros</option>
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
                            <select id="m-status" name="situacao" required>
                                <option value="ativo">Ativo</option>
                                <option value="afastado">Afastado</option>
                                <option value="aposentado">Aposentado</option>
                                <option value="licenca_maternidade">Licença Maternidade</option>
                                <option value="desligado">Desligado</option>
                                <option value="outros">Outros</option>
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
                            <label for="m-previsao-aposentadoria">Previsão de Aposentadoria</label>
                            <input type="date" id="m-previsao-aposentadoria" name="previsao_aposentadoria" readonly>
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

        <div id="modalEdicao" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalEdicaoTitulo">
            <div class="modal-content modal-content--large">

                <div class="modal-header">
                    <h2 id="modalEdicaoTitulo">Editar Usuário</h2>
                    <button type="button" class="close-btn" onclick="closeModalEdicao()" aria-label="Fechar">&times;</button>
                </div>

                <div class="perfil-identity-card" id="editIdentityCard">
                    <div class="perfil-avatar-wrap">
                        <div class="perfil-avatar" id="editAvatar"></div>
                    </div>
                    <div class="perfil-identity-info">
                        <p class="perfil-identity-ra"   id="editRA">—</p>
                        <p class="perfil-identity-name" id="editNome">—</p>
                        <p class="perfil-identity-cargo" id="editCargo">—</p>
                    </div>
                </div>

                <form id="formEdicao" novalidate>
                    <input type="hidden" id="edit-id">
                    <input type="hidden" id="edit-servidor-id">

                    <div class="perfil-info-grid">

                        <div class="perfil-info-card">
                            <div class="perfil-info-card-header">
                                <span class="perfil-info-icon perfil-info-icon--blue">
                                    <i class="fas fa-id-badge"></i>
                                </span>
                                <h3>Dados Funcionais</h3>
                            </div>
                            <div class="perfil-fields">
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-cargo">Cargo *</label>
                                    <select id="edit-cargo" class="perfil-field-input" required>
                                        <option value="3a_classe">3ª Classe</option>
                                        <option value="2a_classe">2ª Classe</option>
                                        <option value="1a_classe">1ª Classe</option>
                                        <option value="classe_especial">Classe Especial</option>
                                        <option value="chefe_divisao">Chefe de Divisão</option>
                                        <option value="diretor_geral">Diretor-Geral</option>
                                    </select>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-data-ingresso">Data de Ingresso</label>
                                    <input type="date" id="edit-data-ingresso" class="perfil-field-input">
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-previsao-aposentadoria">Previsão de Aposentadoria</label>
                                    <input type="date" id="edit-previsao-aposentadoria" class="perfil-field-input">
                                </div>
                            </div>
                        </div>

                        <div class="perfil-info-card">
                            <div class="perfil-info-card-header">
                                <span class="perfil-info-icon perfil-info-icon--green">
                                    <i class="fas fa-building"></i>
                                </span>
                                <h3>Lotação</h3>
                            </div>
                            <div class="perfil-fields">
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-unidade">Unidade *</label>
                                    <select id="edit-unidade" class="perfil-field-input" required>
                                        <option value="">Selecione...</option>
                                    </select>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-situacao">Situação *</label>
                                    <select id="edit-situacao" class="perfil-field-input" required>
                                        <option value="ativo">Ativo</option>
                                        <option value="afastado">Afastado</option>
                                        <option value="aposentado">Aposentado</option>
                                        <option value="licenca_maternidade">Licença Maternidade</option>
                                        <option value="desligado">Desligado</option>
                                        <option value="outros">Outros</option>
                                    </select>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-role">Perfil de Acesso *</label>
                                    <select id="edit-role" class="perfil-field-input" required>
                                        <option value="user">Usuário</option>
                                        <option value="gestor">Gestor</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="perfil-info-card">
                            <div class="perfil-info-card-header">
                                <span class="perfil-info-icon perfil-info-icon--yellow">
                                    <i class="fas fa-user"></i>
                                </span>
                                <h3>Dados Pessoais</h3>
                            </div>
                            <div class="perfil-fields">
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-nome">Nome Completo *</label>
                                    <input type="text" id="edit-nome" class="perfil-field-input" required>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-email">E-mail *</label>
                                    <input type="email" id="edit-email" class="perfil-field-input" required>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-cpf">CPF *</label>
                                    <input type="text" id="edit-cpf" class="perfil-field-input" maxlength="14" required>
                                </div>
                                <div class="perfil-field">
                                    <label class="perfil-field-label" for="edit-data-nascimento">Data de Nascimento</label>
                                    <input type="date" id="edit-data-nascimento" class="perfil-field-input">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div id="feedbackEdicao" class="feedback-msg"></div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeModalEdicao()">Cancelar</button>
                        <button type="submit" class="btn-save" id="btnSalvarEdicao">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>     
<?php require __DIR__ . '/../layout/partials/modal-perfil-servidor.php'; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>