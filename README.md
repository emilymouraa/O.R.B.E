O.R.B.E/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── organograma.css
│   │   │   └── style.css
│   │   │
│   │   ├── images/
│   │   │   ├── fundo_prf.png
│   │   │   ├── fundo_prf2.png
│   │   │   ├── orbe_logo.png
│   │   │   ├── orbe_logo3.png
│   │   │   └── orbe_logo4.png
│   │   │
│   │   └── js/
│   │       ├── app.js
│   │       ├── organograma-gestor.js
│   │       ├── organograma-user.js
│   │       ├── organograma.js
│   │       └── relatorio.js
│   │
│   ├── .htaccess
│   ├── favicon.ico
│   └── index.php
│
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── BancoTalentosController.php
│   │   ├── ChatController.php
│   │   ├── CompetenciaController.php
│   │   ├── NotificacaoController.php
│   │   ├── OrganogramaController.php
│   │   ├── PainelController.php
│   │   ├── RelatorioController.php
│   │   ├── ServidorController.php
│   │   ├── UnidadeController.php
│   │   └── UserController.php
│   │
│   ├── Core/
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Env.php
│   │   ├── Model.php
│   │   └── Router.php
│   │
│   ├── Helpers/
│   │   ├── Response.php
│   │   └── Toast.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── RoleMiddleware.php
│   │
│   ├── Models/
│   │   ├── BancoTalentosModel.php
│   │   ├── ChatModel.php
│   │   ├── CompetenciaModel.php
│   │   ├── LogModel.php
│   │   ├── NotificacaoModel.php
│   │   ├── OrganogramaModel.php
│   │   ├── RelatorioModel.php
│   │   ├── ServidorModel.php
│   │   ├── ServidorPerfilModel.php
│   │   └── UserModel.php
│   │
│   ├── Routes/
│   │   └── web.php
│   │
│   ├── Services/
│   │   └── AuthService.php
│   │
│   └── Views/
│       ├── auth/
│       │   ├── login.php
│       │   ├── register.php
│       │   ├── reset_password.php
│       │   └── validate_identity.php
│       │
│       ├── dashboard/
│       │   ├── banco-talentos.php
│       │   ├── colaboradores.php
│       │   ├── competencias.php
│       │   ├── home.php
│       │   ├── organograma.php
│       │   ├── painel.php
│       │   └── perfil.php
│       │
│       ├── layout/
│       │   ├── partials/
│       │   │   ├── modal-edicao-colaborador.php
│       │   │   ├── modal-perfil-servidor.php
│       │   │   ├── footer.php
│       │   │   ├── header.php
│       │   │   └── sidebar.php
│       │
│       └── relatorios/
│       │   ├── espelho-pdf.php
│       │   ├── holerite-pdf.php
│       │   └── relatorio-pdf.php
│
├── vendor/
│
├── .env
├── .gitignore
├── composer.json
├── composer.lock
├── README.md
├── schema.sql
└── test.php