<?php

namespace MinhaAgenda\Service;

use InvalidArgumentException;
use MinhaAgenda\Auth\AutenticacaoJWT;
use MinhaAgenda\Auth\TokenJWT;
use MinhaAgenda\Service\Service;
use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Cliente;
use MinhaAgenda\Trait\Criptografavel;

class ClienteService extends Service {
    use Criptografavel;

    protected function criarObjeto(array $dados, int|null $id = null, int|null $idRecursoPai = null): Cliente {
        $cliente = new Cliente($id ?? 0)
            ->setNome($dados['nome'])
            ->setEmail($dados['email']);

        return $cliente;
    }

    protected function validar(Model $cliente, array &$erros = [], int|null $idRecursoPai = null): void {
        if (!$cliente instanceof Cliente) {
            throw new InvalidArgumentException('Objeto com instância incorreta.');
        }

        parent::validar($cliente, $erros, $idRecursoPai);
    }

    public function obterComEmail(string $email): Cliente|null {
        $restricoes = ['email' => $email];
        $clientes = $this->obterComRestricoes($restricoes);

        return $clientes[0] ?? null;
    }

}