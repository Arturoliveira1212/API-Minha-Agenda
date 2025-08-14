<?php

use MinhaAgenda\Controller\UsuarioController;

$app->post('/usuarios', UsuarioController::class . ':novo');
$app->put('/usuarios/{id}', UsuarioController::class . ':atualizar');
$app->get('/usuarios', UsuarioController::class . ':obterTodos');
$app->get('/usuarios/{id}', UsuarioController::class . ':obterComId');
$app->delete('/usuarios/{id}', UsuarioController::class . ':excluirComId');
