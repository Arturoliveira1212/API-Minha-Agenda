<?php

use MinhaAgenda\Controller\ClienteController;
use MinhaAgenda\Enum\TipoUsuario;
use MinhaAgenda\Factory\MiddlewareFactory;

$group->post('/clientes', ClienteController::class . ':novo');

$group->put('/clientes/{id}', ClienteController::class . ':atualizar')
    ->add(MiddlewareFactory::tipoUsuario([TipoUsuario::CLIENTE]))
    ->add(MiddlewareFactory::autorizacao());

$group->delete('/clientes/{id}', ClienteController::class . ':excluirComId')
    ->add(MiddlewareFactory::tipoUsuario([TipoUsuario::CLIENTE]))
    ->add(MiddlewareFactory::autorizacao());

$group->get('/clientes/{id}', ClienteController::class . ':obterComId')
    ->add(MiddlewareFactory::tipoUsuario([TipoUsuario::CLIENTE]))
    ->add(MiddlewareFactory::autorizacao());