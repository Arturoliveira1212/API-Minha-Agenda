<?php

namespace MinhaAgenda\Service;

use InvalidArgumentException;
use MinhaAgenda\Service\Service;
use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Administrador;

class AdministradorService extends Service {

    protected function criarObjeto(array $dados, int|null $id = null): Administrador {
        $administrador = new Administrador($id ?? 0)
            ->setNome($dados['nome'])
            ->setEmail($dados['email']);

        if (isset($dados['senha'])) {
            $administrador->setSenha($dados['senha']);
        }

        return $administrador;
    }

    protected function validar(Model $administrador, array &$erros = [], int|null $idRecursoPai = null): void {
        if (!$administrador instanceof Administrador) {
            throw new InvalidArgumentException('Objeto com instância incorreta.');
        }

        parent::validar($administrador, $erros, $idRecursoPai);
    }

    public function obterComEmail(string $email): Administrador|null {
        $restricoes = ['email' => $email];
        $administradores = $this->obterComRestricoes($restricoes);

        return $administradores[0] ?? null;
    }
}
