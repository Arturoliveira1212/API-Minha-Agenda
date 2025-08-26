<?php

require_once '../bootstrap.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use MinhaAgenda\Middleware\CorpoRequisicaoMiddleware;
use MinhaAgenda\Middleware\SanitizacaoDadosMiddleware;
use MinhaAgenda\Middleware\ErrorHandlerMiddleware;

$app = AppFactory::create();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(new ErrorHandlerMiddleware());

$apiV1 = $app->group('/api', function ($group) {
    $rotas = glob(ROOT_PATH . '/routes/*.php');
    foreach ($rotas as $rota) {
        require_once $rota;
    }
});
$apiV1->add(new SanitizacaoDadosMiddleware());
$apiV1->add(new CorpoRequisicaoMiddleware());
$apiV1->add(new BodyParsingMiddleware());

$app->run();