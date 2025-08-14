<?php

namespace MinhaAgenda\Middleware;

use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Exception\ValidacaoException;
use MinhaAgenda\Trait\RespostaAPI;
use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Psr7\Response;
use Throwable;

class ErrorHandlerMiddleware implements ErrorHandlerInterface {
    use RespostaAPI;

    public function __invoke(
        ServerRequestInterface $request, Throwable $e, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails
    ): ResponseInterface {
        [$mensagem, $status, $dados] = $this->processarException($e);

        return $this->enviarResposta(new Response(), $status, $mensagem, $dados);
    }

    private function processarException(Throwable $e): array {
        $message = 'Houve um erro interno no servidor';
        $status = StatusHttp::INTERNAL_SERVER_ERROR;
        $dados = [];

        if ($e instanceof HttpNotFoundException) {
            $message = 'Rota não encontrada.';
            $status = StatusHttp::NOT_FOUND;
        } else if ($e instanceof HttpMethodNotAllowedException) {
            $message = 'Método HTTP não permitido para esta rota.';
            $status = StatusHttp::METHOD_NOT_ALLOWED;
        } else if ($e instanceof HttpUnauthorizedException) {
            $message = 'Acesso não autorizado.';
            $status = StatusHttp::UNAUTHORIZED;
        } else if ($e instanceof HttpForbiddenException) {
            $message = 'Acesso proibido.';
            $status = StatusHttp::FORBIDDEN;
        } else if ($e instanceof HttpBadRequestException) {
            $message = 'Requisição inválida.';
            $status = StatusHttp::BAD_REQUEST;
        } else if ($e instanceof ValidacaoException) {
            $message = 'Dados de entrada inválidos.';
            $status = StatusHttp::UNPROCESSABLE_ENTITY;
            $dados = $e->obterErros();
        }

        return [$message, $status, $dados];
    }
}
