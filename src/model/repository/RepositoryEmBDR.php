<?php

namespace MinhaAgenda\Model\Repository;

use MinhaAgenda\Database\BancoDadosRelacional;
use MinhaAgenda\Model\Entity\Model;

abstract class RepositoryEmBDR implements Repository {
    protected readonly BancoDadosRelacional $bancoDados;

    public function __construct(BancoDadosRelacional $bancoDados) {
        $this->bancoDados = $bancoDados;
    }

    abstract protected function nomeTabela(): string;

    public function salvar(Model $objeto, int|null $idRecursoPai = null): void {
        match ($objeto->temIdInexistente()) {
            true => $this->adicionarNovo($objeto, $idRecursoPai),
            false => $this->atualizar($objeto)
        };

        $idSalvo = $this->bancoDados->ultimoIdInserido();
        $objeto->setId($idSalvo);
    }

    abstract protected function adicionarNovo(Model $objeto, int|null $idRecursoPai = null): void;
    abstract protected function atualizar(Model $objeto): void;

    public function obterComId(int $id): Model|null {
        $comando = 'SELECT * FROM {$this->nomeTabela()} WHERE id = :id AND ativo = :ativo';
        $parametros = ['id' => $id, 'ativo' => true];
        $objetos = $this->obterObjetos($comando, [$this, 'transformarEmObjeto'], $parametros);

        return $objetos[0] ?? null;
    }

    public function obterComRestricoes(array $restricoes): array {
        $parametros = [];
        $query = $this->gerarQuery($restricoes, $parametros);
        $this->preencherLimitEOffset($query, $restricoes);
        $objetos = $this->obterObjetos($query, [$this, 'transformarEmObjeto'], $parametros);

        return $objetos;
    }

    private function preencherLimitEOffset(string &$query, array $restricoes): void {
        $limit = '';
        $offset = '';

        if (isset($restricoes['limit']) && is_numeric($restricoes['limit'])) {
            $limit = " LIMIT {$restricoes['limit']} ";

            if (isset($restricoes['offset']) && is_numeric($restricoes['offset'])) {
                $offset = " OFFSET {$restricoes['offset']} ";
            }
        }

        $query = "{$query} {$limit} {$offset}";
    }

    abstract protected function gerarQuery(array $restricoes = [], array &$parametros = []): string;
    abstract protected function transformarEmObjeto(array $dados): Model;

    public function obterObjetos(string $comando, array $callback, array $parametros = []): array {
        $objetos = [];

        $resultados = $this->bancoDados->consultar($comando, $parametros);

        if (!empty($resultados)) {
            foreach ($resultados as $resultado) {
                $objeto = call_user_func_array($callback, [$resultado]);
                $objetos[] = $objeto;
            }
        }

        return $objetos;
    }

    public function existe(string $campo, string $valor): bool {
        return $this->bancoDados->existe($this->nomeTabela(), $campo, $valor);
    }

    public function desativarComId(int $id): int {
        return $this->bancoDados->desativar($this->nomeTabela(), $id);
    }

    public function excluirComId(int $id): int {
        return $this->bancoDados->excluir($this->nomeTabela(), $id);
    }
}
