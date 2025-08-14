<?php

namespace MinhaAgenda\Middleware;

use MinhaAgenda\Util\Sanitizador;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SanitizacaoDadosMiddleware implements MiddlewareInterface {
    private bool $limparCorpoRequisicao;
    private bool $limparParametros;
    private bool $limparCookies;
    private array $camposExcluidos;

    public function __construct(
        bool $limparCorpoRequisicao = true,
        bool $limparParametros = true,
        bool $limparCookies = true,
        array $camposExcluidos = []
    ) {
        $this->limparCorpoRequisicao = $limparCorpoRequisicao;
        $this->limparParametros = $limparParametros;
        $this->limparCookies = $limparCookies;
        $this->camposExcluidos = $camposExcluidos;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if ($this->limparCorpoRequisicao) {
            $corpoRequisicao = $request->getParsedBody();
            if (is_array($corpoRequisicao)) {
                $corpoRequisicaoLimpo = $this->sanitizarDados($corpoRequisicao, $this->camposExcluidos);
                $request = $request->withParsedBody($corpoRequisicaoLimpo);
            }
        }

        if ($this->limparParametros) {
            $parametros = $request->getQueryParams();
            if (is_array($parametros)) {
                $parametrosLimpos = $this->sanitizarDados($parametros, $this->camposExcluidos);
                $request = $request->withQueryParams($parametrosLimpos);
            }
        }

        if ($this->limparCookies) {
            $cookies = $request->getCookieParams();
            if (is_array($cookies)) {
                $cookiesLimpos = $this->sanitizarDados($cookies, $this->camposExcluidos);
                $request = $request->withCookieParams($cookiesLimpos);
            }
        }

        return $handler->handle($request);
    }

    private function sanitizarDados(array $dados, array $camposExcluidos = []): array {
        $arrayLimpo = [];

        foreach ($dados as $chave => $valor) {
            if (in_array($chave, $camposExcluidos)) {
                $arrayLimpo[$chave] = $valor;
                continue;
            }

            if (is_array($valor)) {
                $arrayLimpo[$chave] = $this->sanitizarDados($valor, []);
            } else if (is_string($valor)) {
                $arrayLimpo[$chave] = Sanitizador::sanitizarString($valor);
            } else {
                $arrayLimpo[$chave] = $valor;
            }
        }

        return $arrayLimpo;
    }
}
