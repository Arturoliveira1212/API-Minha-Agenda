<?php

namespace MinhaAgenda\Database;

interface BancoDados {
    public function executar(string $comando, array $parametros = []): int;
    public function consultar(string $comando, array $parametros = []): array;
    public function excluir(string $tabela, int $id): int;
    public function desativar(string $tabela, int $id): int;
    public function existe(string $tabela, string $campo, string $valor): bool;
    public function ultimoIdInserido(): int;
    public function iniciarTransacao(): void;
    public function finalizarTransacao(): void;
    public function desfazerTransacao(): void;
    public function emTransacao(): bool;
}
