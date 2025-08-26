<?php

namespace MinhaAgenda\Middleware;

use MinhaAgenda\Enum\TipoUsuario;
use MinhaAgenda\Model\Entity\Usuario;
use MinhaAgenda\Trait\RespostaAPI;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use MinhaAgenda\Auth\PayloadJWT;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Auth\AutenticacaoJWT;
use MinhaAgenda\Service\ClienteService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MinhaAgenda\Service\AdministradorService;

class AutorizacaoMiddleware implements MiddlewareInterface {
    use RespostaAPI;
    private AdministradorService $administradorService;
    private ClienteService $clienteService;

    public function __construct(AdministradorService $administradorService, ClienteService $clienteService) {
        $this->administradorService = $administradorService;
        $this->clienteService = $clienteService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $autorization = $request->getHeaderLine('Authorization');
        if (!$autorization || !preg_match('/^Bearer\s(\S+)/', $autorization, $matches)) {
            return $this->enviarResposta(new Response(), StatusHttp::UNAUTHORIZED, 'Token de autenticação não foi enviado.');
        }

        $token = $matches[1];

        $payloadJWT = AutenticacaoJWT::decodificarToken($token);
        if (!$payloadJWT instanceof PayloadJWT) {
            return $this->enviarResposta(new Response(), StatusHttp::UNAUTHORIZED, 'Token de autenticação inválido.');
        }

        $tipoUsuario = TipoUsuario::tryFrom($payloadJWT->role());
        if (!$tipoUsuario) {
            return $this->enviarResposta(new Response(), StatusHttp::UNAUTHORIZED, 'Token de autenticação inválido.');
        }

        $usuario = $this->obterUsuario($payloadJWT->sub(), $tipoUsuario);
        $request = $request->withAttribute('usuario', $usuario);

        return $handler->handle($request);
    }

    private function obterUsuario(int $idUsuario, TipoUsuario $tipoUsuario): Usuario {
        return TipoUsuario::temPrivilegiosAdministrativos($tipoUsuario)
            ? $this->administradorService->obterComId($idUsuario)
            : $this->clienteService->obterComId($idUsuario);
    }
}
