<?php
/*
 * Modal de edição reutilizado — admin e gestor.
 * Incluído em home.php (admin) e colaboradores.php (gestor).
 * O bloqueio de campos por role é feito via JS em editarUsuario().
 */
?>
<div id="modalEdicao" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalEdicaoTitulo">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h2 id="modalEdicaoTitulo">Editar Usuário</h2>
            <button type="button" class="close-btn" onclick="dismissModalEdicao()" aria-label="Fechar">&times;</button>
        </div>
        <div class="perfil-identity-card" id="editIdentityCard">
            <div class="perfil-avatar-wrap">
                <div class="perfil-avatar" id="editAvatar"></div>
            </div>
            <div class="perfil-identity-info">
                <p class="perfil-identity-ra"    id="editRA">—</p>
                <p class="perfil-identity-name"  id="editNome">—</p>
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
                <button type="button" class="btn-cancel" id="btnRejeitarChamado"
                    style="display:none;background:#ef4444;color:#fff;border:none">
                    <i class="fas fa-times"></i> Rejeitar Chamado
                </button>
                <button type="submit" class="btn-save" id="btnSalvarEdicao">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>