<?php

namespace MinhaAgenda\Model\Repository;

use MinhaAgenda\Model\Entity\Model;

interface Repository {
    public function salvar(Model $objeto, int|null $idRecursoPai = null): void;
    public function obterComId(int $id): Model|null;
    public function obterComRestricoes(array $restricoes): array;
    public function existe(string $campo, string $valor): bool;
    public function desativarComId(int $id): int;
    public function excluirComId(int $id): int;
}
