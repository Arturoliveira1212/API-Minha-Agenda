<?php

use MinhaAgenda\Controller\SessaoController;

$possibilidadesUsuarios = 'clientes|funcionarios|gerentes|administradores';

$group->post("/{tipoUsuario:($possibilidadesUsuarios)}/login", SessaoController::class . ':login');
$group->post("/{tipoUsuario:($possibilidadesUsuarios)}/refresh", SessaoController::class . ':refresh');
$group->post("/{tipoUsuario:($possibilidadesUsuarios)}/logout", SessaoController::class . ':logout');