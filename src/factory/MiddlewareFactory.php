<?php

namespace MinhaAgenda\Factory;

use MinhaAgenda\Middleware\AutorizacaoMiddleware;
use MinhaAgenda\Middleware\TipoUsuarioMiddleware;
use MinhaAgenda\Model\Entity\Administrador;
use MinhaAgenda\Model\Entity\Cliente;

class MiddlewareFactory {
    public static function autorizacao(): AutorizacaoMiddleware {
        return new AutorizacaoMiddleware(
            ClassFactory::makeService(Administrador::class),
            ClassFactory::makeService(Cliente::class)
        );
    }

    public static function tipoUsuario(array $tiposUsuario): TipoUsuarioMiddleware {
        return new TipoUsuarioMiddleware($tiposUsuario);
    }
}
