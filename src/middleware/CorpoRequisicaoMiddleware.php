<?php

namespace MinhaAgenda\Middleware;

use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Trait\RespostaAPI;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class CorpoRequisicaoMiddleware implements MiddlewareInterface {
    use RespostaAPI;
    private string $formato;

    public function __construct(string $formato = 'application/json') {
        $this->formato = $formato;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $contentType = $request->getHeaderLine('Content-Type');
        $corpoRequisicao = $request->getParsedBody();

        if (empty($corpoRequisicao) || !$this->validarFormato($contentType)) {
            return $this->enviarResposta(new Response(), StatusHttp::BAD_REQUEST,
                'O corpo da requisição tem formato inválido.'
            );
        }

        return $handler->handle($request);
    }

    private function validarFormato(string $contentType): bool {
        return strpos($contentType, $this->formato) !== false;
    }
}
