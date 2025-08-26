<?php

namespace MinhaAgenda\Service;

use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Repository\Repository;
use MinhaAgenda\Exception\ValidacaoException;
use MinhaAgenda\Exception\NaoEncontradoException;

abstract class Service {
    protected readonly Repository $repository;

    public function __construct(Repository $repository) {
        $this->repository = $repository;
    }

    public function salvar(array $dados, int|null $id = null, int|null $idRecursoPai = null): int {
        $objeto = $this->preSalvar($dados, $id, $idRecursoPai);
        $this->repository->salvar($objeto, $idRecursoPai);
        $this->posSalvar($objeto, $idRecursoPai);

        return $objeto->getId();
    }

    protected function preSalvar(array $dados, int|null $id = null, int|null $idRecursoPai = null): Model {
        $objeto = $this->criarObjeto($dados, $id);

        $ehAtualizacao = !$objeto->temIdInexistente();
        if ($ehAtualizacao && !$this->repository->existe('id', $objeto->getId())) {
            throw new NaoEncontradoException('Recurso não encontrado.');
        }

        $erros = [];
        $this->validar($objeto, $erros, $idRecursoPai);

        return $objeto;
    }

    abstract protected function criarObjeto(array $dados, int|null $id = null, int|null $idRecursoPai = null): Model;

    protected function validar(Model $objeto, array &$erros = [], int|null $idRecursoPai = null): void {
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }
    }

    protected function posSalvar(Model $objeto, int|null $idRecursoPai = null): void {

    }

    public function obterComId(int $id): ?Model {
        $objeto = $this->repository->obterComId($id);
        if (!$objeto instanceof Model) {
            throw new NaoEncontradoException('Recurso não encontrado.');
        }

        return $objeto;
    }

    public function obterComRestricoes(array $restricoes): array {
        $objetos = $this->repository->obterComRestricoes($restricoes);

        return $objetos;
    }

    public function desativarComId(int $id): int {
        $existe = $this->repository->existe('id', $id);
        if (!$existe) {
            throw new NaoEncontradoException('Recurso não encontrado.');
        }

        return $this->repository->desativarComId($id);
    }

    public function excluirComId(int $id): int {
        $existe = $this->repository->existe('id', $id);
        if (!$existe) {
            throw new NaoEncontradoException('Recurso não encontrado.');
        }

        return $this->repository->excluirComId($id);
    }
}