<?php

require_once '../bootstrap.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use MinhaAgenda\Middleware\SanitizacaoDadosMiddleware;
use MinhaAgenda\Middleware\ErrorHandlerMiddleware;

$app = AppFactory::create();
$app->add(new SanitizacaoDadosMiddleware());
$app->add(new BodyParsingMiddleware());

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(new ErrorHandlerMiddleware());

$rotas = glob(ROOT_PATH . '/rotas/*.php');
foreach ($rotas as $rota) {
    require_once $rota;
}

$app->run();