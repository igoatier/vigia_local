<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/alertas.php';

$page = $_GET['page'] ?? 'ronda';

if ($page !== 'login') {
    exigir_login();
}

switch ($page) {
    case 'login':
        if (esta_logado()) {
            redirecionar('index.php');
        }
        require __DIR__ . '/views/login.php';
        break;

    case 'sair':
        sair();
        redirecionar('index.php?page=login');
        break;

    case 'historico':
        require __DIR__ . '/views/historico.php';
        break;

    case 'postos':
        require __DIR__ . '/views/postos.php';
        break;

    case 'equipe':
        exigir_gestor_equipe();
        require __DIR__ . '/views/equipe.php';
        break;

    case 'alertas':
        require __DIR__ . '/views/alertas.php';
        break;

    case 'registrar':
        require __DIR__ . '/views/registrar.php';
        break;

    case 'foto':
        require __DIR__ . '/lib/foto.php';
        break;

    case 'ronda':
    default:
        require __DIR__ . '/views/ronda.php';
        break;
}
