<?php

namespace MinhaAgenda\Model\Repository;

use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Usuario;
use MinhaAgenda\Model\Repository\RepositoryEmBDR;

class UsuarioRepository extends RepositoryEmBDR {
    protected function nomeTabela(): string {
        return 'usuario';
    }

    protected function adicionarNovo(Model $usuario, int|null $idRecursoPai = null): void {
        $comando = "INSERT INTO {$this->nomeTabela()} (
                nome,
                email,
                senha
            ) VALUES (
                :nome,
                :email,
                :senha
            )";
        $parametros = $this->parametrosAdicionarNovo($usuario);

        $this->bancoDados->executar($comando, $parametros);
    }

    private function parametrosAdicionarNovo(Usuario $usuario): array {
        return [
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail()
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

    private function parametrosAtualizar(Usuario $usuario): array {
        return [
            'id' => $usuario->getId(),
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail()
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

    protected function transformarEmObjeto(array $dados): Usuario {
        return new Usuario(intval($dados['id']))
            ->setNome($dados['nome'])
            ->setEmail($dados['email']);
    }
}
