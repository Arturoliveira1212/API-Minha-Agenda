<?php

namespace MinhaAgenda\Enum;

enum TipoUsuario: string {
    case CLIENTE = 'cliente';
    case FUNCIONARIO = 'funcionario';
    case GERENTE = 'gerente';
    case ADMINISTRADOR = 'administrador';

    public static function tiposAdministradores(): array {
        return [
            self::FUNCIONARIO,
            self::GERENTE,
            self::ADMINISTRADOR
        ];
    }

    public static function temPrivilegiosAdministrativos(TipoUsuario $tipoUsuario): bool {
        return in_array($tipoUsuario, self::tiposAdministradores());
    }
}
