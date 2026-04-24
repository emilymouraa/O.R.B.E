<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - ORBE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --text: #1e293b;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding: 2rem; display: flex; flex-direction: column; min-height: 100vh; }

        /* Componente de Boas-vindas (ORBE) */
        .welcome-card { background: var(--white); padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .welcome-card h2 { font-size: 1.2rem; color: var(--primary); }
        .welcome-card p { font-size: 0.9rem; color: #64748b; }
        .btn-logout { background: var(--danger); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: opacity 0.2s; }
        .btn-logout:hover { opacity: 0.9; }

        /* Header & Info */
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .title-group h1 { font-size: 1.5rem; color: var(--text); }
        .title-group p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        .counter { font-weight: 600; color: var(--primary); margin-top: 8px; display: block; }

        .btn-new { background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
        .btn-new:hover { opacity: 0.9; }

        /* Filtros */
        .filters-container { background: var(--white); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .filter-group input, .filter-group select { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 4px; outline: none; }

        /* Tabela Responsiva */
        .table-wrapper { background: var(--white); border-radius: 8px; border: 1px solid var(--border); overflow-x: auto; margin-bottom: 2rem; flex-grow: 1; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th { background: #f1f5f9; padding: 1rem; font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }

        /* Badges e Pills */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin { background: #dbeafe; color: #1e40af; }
        .badge-gestor { background: #fef3c7; color: #92400e; }
        .badge-user { background: #f1f5f9; color: #475569; }

        .pill { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
        .pill-active { background: #dcfce7; color: #166534; }
        .pill-inactive { background: #fee2e2; color: #991b1b; }

        /* Ações */
        .actions { display: flex; gap: 12px; }
        .btn-icon { border: none; background: none; cursor: pointer; font-size: 1.1rem; transition: transform 0.1s; }
        .btn-edit { color: var(--primary); }
        .btn-delete { color: var(--danger); }
        .btn-icon:hover { transform: scale(1.1); }

        /* Paginação */
        .pagination { display: flex; justify-content: flex-end; padding: 1rem; gap: 5px; }
        .page-link { padding: 6px 12px; border: 1px solid var(--border); border-radius: 4px; background: white; cursor: pointer; text-decoration: none; color: var(--text); font-size: 0.85rem; }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* --- Estilos do Modal de Cadastro --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-content { background: var(--white); padding: 2rem; border-radius: 12px; width: 90%; max-width: 700px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: modalFadeIn 0.3s ease; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
        .modal-header h2 { font-size: 1.25rem; color: var(--text); }
        .close-btn { background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; transition: color 0.2s; }
        .close-btn:hover { color: var(--danger); }

        /* Grid do Formulário */
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #475569; }
        .form-group input, .form-group select { padding: 0.75rem; border: 1px solid var(--border); border-radius: 6px; outline: none; transition: border-color 0.2s; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); }
        .form-group input[readonly] { background: #f1f5f9; cursor: not-allowed; color: #64748b; }

        /* Feedback Visual */
        .feedback-msg { margin-top: 1.5rem; padding: 12px; border-radius: 6px; display: none; font-size: 0.9rem; text-align: center; font-weight: 500; }
        .msg-success { display: block; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .msg-error { display: block; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Ações do Formulário */
        .form-actions { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border); padding-top: 1.5rem; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 0.75rem 2rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel { background: white; color: #475569; border: 1px solid var(--border); padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s; }
        .btn-cancel:hover { background: #f1f5f9; }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; }
            .btn-new { width: 100%; justify-content: center; }
            .welcome-card { flex-direction: column; gap: 1rem; text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="welcome-card">
        <div>
            <h2>Bem-vindo ao ORBE</h2>
            <p>Login realizado com sucesso.</p>
        </div>
        <form method="POST" action="/logout">
            <button type="submit" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair do Sistema</button>
        </form>
    </div>

    <header class="header-section">
        <div class="title-group">
            <h1>Gestão de Usuários</h1>
            <p>Cadastre e gerencie os usuários do sistema</p>
            <span class="counter">Total de 42 usuários no sistema</span>
        </div>
        <button class="btn-new" onclick="openModal()">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
    </header>

    <section class="filters-container">
        <div class="filter-group">
            <input type="text" placeholder="Buscar por nome ou e-mail...">
        </div>
        <div class="filter-group">
            <select>
                <option value="">Todos os Perfis</option>
                <option>Administrador</option>
                <option>Gestor</option>
                <option>Usuário</option>
            </select>
        </div>
        <div class="filter-group">
            <select>
                <option value="">Status</option>
                <option>Ativo</option>
                <option>Inativo</option>
            </select>
        </div>
        <div class="filter-group">
            <select>
                <option value="">Todas as Unidades</option>
                <option>Matriz</option>
                <option>Filial SP</option>
                <option>Filial RJ</option>
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
            <tbody>
                <tr>
                    <td><strong>Ana Silva</strong></td>
                    <td>ana.silva@empresa.com</td>
                    <td><span class="badge badge-admin">Administrador</span></td>
                    <td>Matriz</td>
                    <td><span class="pill pill-active">Ativo</span></td>
                    <td>10/02/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Carlos Souza</strong></td>
                    <td>carlos.s@empresa.com</td>
                    <td><span class="badge badge-gestor">Gestor</span></td>
                    <td>Filial SP</td>
                    <td><span class="pill pill-active">Ativo</span></td>
                    <td>15/01/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Mariana Luz</strong></td>
                    <td>mariana.luz@empresa.com</td>
                    <td><span class="badge badge-user">Usuário</span></td>
                    <td>Filial RJ</td>
                    <td><span class="pill pill-inactive">Inativo</span></td>
                    <td>05/03/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="pagination">
            <button class="page-link"><i class="fas fa-chevron-left"></i></button>
            <button class="page-link active">1</button>
            <button class="page-link">2</button>
            <button class="page-link">3</button>
            <button class="page-link"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <div id="modalCadastro" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cadastrar Novo Usuário</h2>
                <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="formUsuario" novalidate>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" placeholder="Digite o nome completo" required>
                    </div>
                    <div class="form-group">
                        <label for="cpf">CPF *</label>
                        <input type="text" id="cpf" placeholder="000.000.000-00" maxlength="14" required>
                    </div>
                    <div class="form-group">
                        <label for="unidade">Unidade *</label>
                        <select id="unidade" required>
                            <option value="">Selecione a unidade...</option>
                            <option value="Matriz">Matriz</option>
                            <option value="Filial SP">Filial SP</option>
                            <option value="Filial RJ">Filial RJ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cargo">Cargo *</label>
                        <input type="text" id="cargo" placeholder="Ex: Analista de Sistemas" required>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dataNascimento">Data de Nascimento</label>
                        <input type="date" id="dataNascimento">
                    </div>
                    <div class="form-group">
                        <label for="dataAdmissao">Data de Admissão</label>
                        <input type="date" id="dataAdmissao">
                    </div>
                    <div class="form-group">
                        <label for="dataCadastro">Data de Cadastro</label>
                        <input type="text" id="dataCadastro" readonly>
                    </div>
                </div>

                <div id="feedback" class="feedback-msg"></div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalCadastro');
        const form = document.getElementById('formUsuario');
        const feedback = document.getElementById('feedback');
        const inputCpf = document.getElementById('cpf');
        const inputDataCadastro = document.getElementById('dataCadastro');

        // Função para abrir o modal e preencher a data de cadastro atual
        function openModal() {
            modal.style.display = 'flex';
            
            // Preenche a data de cadastro automaticamente (DD/MM/AAAA)
            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();
            inputDataCadastro.value = `${dia}/${mes}/${ano}`;
        }

        // Função para fechar o modal e limpar o formulário
        function closeModal() {
            modal.style.display = 'none';
            form.reset();
            feedback.className = 'feedback-msg'; // Reseta o estado do feedback visual
        }

        // Máscara de CPF em tempo real (Regex)
        inputCpf.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            
            if (valor.length > 11) valor = valor.slice(0, 11);
            
            // Aplica a formatação 000.000.000-00
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            
            e.target.value = valor;
        });

        // Validação do Formulário no momento do envio (Submit)
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Impede o recarregamento da página
            
            const camposObrigatorios = ['nome', 'cpf', 'unidade', 'cargo', 'status'];
            let valido = true;

            // 1. Valida se os campos obrigatórios estão preenchidos
            camposObrigatorios.forEach(id => {
                const campo = document.getElementById(id);
                if (!campo.value || campo.value.trim() === "") {
                    valido = false;
                }
            });

            // 2. Valida se o CPF está completo (14 caracteres considerando a máscara)
            if (inputCpf.value.length < 14) {
                valido = false;
            }

            // 3. Exibe o feedback visual genérico
            if (valido) {
                feedback.textContent = "Cadastro realizado com sucesso!";
                feedback.className = "feedback-msg msg-success";
                
                // Fecha o modal automaticamente após 2 segundos
                setTimeout(() => {
                    closeModal();
                }, 2000);
            } else {
                feedback.textContent = "Erro no preenchimento do formulário. Verifique os dados fornecidos.";
                feedback.className = "feedback-msg msg-error";
            }
        });

        // Permite fechar o modal clicando na área escura fora da caixa
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>