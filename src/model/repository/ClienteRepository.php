<?php

namespace MinhaAgenda\Model\Repository;

use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Cliente;
use MinhaAgenda\Model\Repository\RepositoryEmBDR;

class ClienteRepository extends RepositoryEmBDR {
    protected function nomeTabela(): string {
        return 'cliente';
    }

    protected function adicionarNovo(Model $cliente, int|null $idRecursoPai = null): void {
        $comando = "INSERT INTO {$this->nomeTabela()} (
                nome,
                email,
                senha
            ) VALUES (
                :nome,
                :email,
                :senha
            )";
        $parametros = $this->parametrosAdicionarNovo($cliente);

        $this->bancoDados->executar($comando, $parametros);
    }

    private function parametrosAdicionarNovo(Cliente $cliente): array {
        return [
            'nome' => $cliente->getNome(),
            'email' => $cliente->getEmail()
        ];
    }

    protected function atualizar(Model $objeto): void {
        $comando = "UPDATE {$this->nomeTabela()} SET
            nome = :nome,
            email = :email,
            senha = :senha
                WHERE id = :id";
        $parametros = $this->parametrosAtualizar($objeto);

        $this->bancoDados->executar($comando, $parametros);
    }

    private function parametrosAtualizar(Cliente $cliente): array {
        return [
            'id' => $cliente->getId(),
            'nome' => $cliente->getNome(),
            'email' => $cliente->getEmail()
        ];
    }

    protected function gerarQuery(array $restricoes = [], array &$parametros = []): string {
        $nomeTabela = $this->nomeTabela();

        $select = "SELECT * FROM {$nomeTabela}";
        $where = ' WHERE ativo = 1 ';
        $join = '';
        $orderBy = '';

        if (isset($restricoes['email']) && !empty($restricoes['email'])) {
            $where .= " AND {$nomeTabela}.email = :email ";
            $parametros['email'] = $restricoes['email'];
        }

        $comando = "{$select} {$join} {$where} {$orderBy}";

        return $comando;
    }

    protected function transformarEmObjeto(array $dados): Cliente {
        return new Cliente(intval($dados['id']))
            ->setNome($dados['nome'])
            ->setEmail($dados['email']);
    }
}
