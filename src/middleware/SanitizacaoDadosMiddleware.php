<?php

namespace MinhaAgenda\Middleware;

use MinhaAgenda\Util\Sanitizador;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SanitizacaoDadosMiddleware implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $corpoRequisicao = $request->getParsedBody();
        if (is_array($corpoRequisicao)) {
            $corpoRequisicaoLimpo = $this->sanitizarDados($corpoRequisicao);
            $request = $request->withParsedBody($corpoRequisicaoLimpo);
        }

        $parametros = $request->getQueryParams();
        if (is_array($parametros)) {
            $parametrosLimpos = $this->sanitizarDados($parametros);
            $request = $request->withQueryParams($parametrosLimpos);
        }

        $cookies = $request->getCookieParams();
        if (is_array($cookies)) {
            $cookiesLimpos = $this->sanitizarDados($cookies);
            $request = $request->withCookieParams($cookiesLimpos);
        }

        return $handler->handle($request);
    }

    private function sanitizarDados(array $dados): array {
        $arrayLimpo = [];

        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $arrayLimpo[$chave] = $this->sanitizarDados($valor);
            } else if (is_string($valor)) {
                $arrayLimpo[$chave] = Sanitizador::sanitizarString($valor);
            } else {
                $arrayLimpo[$chave] = $valor;
            }
        }

        return $arrayLimpo;
    }
}
