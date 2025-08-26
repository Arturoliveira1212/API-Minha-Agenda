<?php

namespace MinhaAgenda\Model\Repository;

use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Administrador;
use MinhaAgenda\Model\Repository\RepositoryEmBDR;

class AdministradorRepository extends RepositoryEmBDR {
    protected function nomeTabela(): string {
        return 'administrador';
    }

    protected function adicionarNovo(Model $administrador, int|null $idRecursoPai = null): void {
        $comando = "INSERT INTO {$this->nomeTabela()} (
                nome,
                email,
                senha
            ) VALUES (
                :nome,
                :email,
                :senha
            )";
        $parametros = $this->parametrosAdicionarNovo($administrador);

        $this->bancoDados->executar($comando, $parametros);
    }

    private function parametrosAdicionarNovo(Administrador $administrador): array {
        return [
            'nome' => $administrador->getNome(),
            'email' => $administrador->getEmail(),
            'senha' => $administrador->getSenha()
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

    private function parametrosAtualizar(Administrador $administrador): array {
        return [
            'id' => $administrador->getId(),
            'nome' => $administrador->getNome(),
            'email' => $administrador->getEmail(),
            'senha' => $administrador->getSenha()
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

    protected function transformarEmObjeto(array $dados): Administrador {
        return new Administrador(intval($dados['id']))
            ->setNome($dados['nome'])
            ->setEmail($dados['email'])
            ->setSenha($dados['senha']);
    }
}
