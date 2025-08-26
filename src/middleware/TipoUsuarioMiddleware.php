<?php

namespace MinhaAgenda\Middleware;

use Slim\Psr7\Response;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Trait\RespostaAPI;
use MinhaAgenda\Model\Entity\Usuario;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TipoUsuarioMiddleware implements MiddlewareInterface {
    use RespostaAPI;
    private array $tiposUsuario;

    public function __construct(array $tiposUsuario) {
        $this->tiposUsuario = $tiposUsuario;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        /** @var Usuario */
        $usuario = $request->getAttribute('usuario');

        if (!$usuario instanceof Usuario || !in_array($usuario->getTipo()->value, $this->tiposUsuario)) {
            return $this->enviarResposta(new Response(), StatusHttp::FORBIDDEN, 'Você não possui permissão para realizar essa ação.');
        }

        return $handler->handle($request);
    }
}
