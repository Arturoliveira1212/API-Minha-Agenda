<?php

namespace MinhaAgenda\Service;

use InvalidArgumentException;
use MinhaAgenda\Service\Service;
use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Usuario;

class UsuarioService extends Service {

    protected function criarObjeto(array $dados, int|null $id = null): Usuario {
        $usuario = new Usuario($id ?? 0)
            ->setNome($dados['nome'])
            ->setEmail($dados['email']);

        return $usuario;
    }

    protected function validar(Model $usuario, array &$erros = [], int|null $idRecursoPai = null): void {
        if (!$usuario instanceof Usuario) {
            throw new InvalidArgumentException('Objeto com instância incorreta.');
        }

        parent::validar($usuario, $erros, $idRecursoPai);
    }
}