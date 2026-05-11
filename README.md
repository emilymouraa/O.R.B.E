    O.R.B.E/
    ├── public/
    │   ├── assets/
    │   │   ├── css/
    │   │   │   └── style.css
    │   │   ├── images/
    │   │   └── js/
    │   │       └── app.js
    │   ├── .htaccess
    │   ├── favicon.ico
    │   └── index.php
    │
    ├── src/
    │   ├── Controllers/
    │   │   ├── AuthController.php
    │   │   ├── BancoTalentosController.php
    │   │   ├── CompetenciasController.php
    │   │   ├── OrganogramaController.php
    │   │   ├── PainelController.php
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
    │   │   └── Response.php
    │   │   ├── Toast.php
    │   │
    │   ├── Middleware/
    │   │   ├── AuthMiddleware.php
    │   │   └── RoleMiddleware.php
    │   │
    │   ├── Models/
    │   │   ├── LogModel.php
    │   │   ├── BancoTalentosModel.php
    │   │   ├── CompetenciaModel.php
    │   │   ├── OrganogramaModel.php
    │   │   ├── ServidorModel.php
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
    │       │   ├── competencias.php
    │       │   ├── home.php
    │       │   ├── organograma.php
    │       │   ├── painel.php
    │       │   └── perfil.php
    │       │
    │       └── layout/
    │       │   ├──partials/
    │       │   └── modal-perfil-servidor.php
    │       │   ├── footer.php
    │       │   ├── header.php
    │       │   └── sidebar.php
    │
    ├── vendor/
    │   ├── composer/
    │   └── autoload.php
    │
    ├── .env
    ├── .gitignore
    ├── composer.json
    ├── composer.lock
    ├── README.md
    ├── schema.sql
    └── test.php