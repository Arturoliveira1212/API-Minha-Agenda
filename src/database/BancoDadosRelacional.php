<?php

namespace MinhaAgenda\Database;

use MinhaAgenda\Database\PDOSingleton;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class BancoDadosRelacional implements BancoDados {
    private readonly PDO $pdo;

    public function __construct() {
        $this->pdo = PDOSingleton::get();
    }

    private function rodar(string $comando, array $parametros = []): bool|PDOStatement {
        try {
            $stmt = $this->pdo->prepare($comando);
            $stmt->execute($parametros);

            return $stmt;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function executar(string $comando, array $parametros = []): int {
        $stmt = $this->rodar($comando, $parametros);

        return $stmt->rowCount();
    }

    public function consultar(string $comando, array $parametros = []): array {
        $stmt = $this->rodar($comando, $parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function excluir(string $tabela, int $id): int {
        $comando = "DELETE FROM {$tabela} WHERE id = :id";
        $parametros = ['id' => $id];

        return $this->executar($comando, $parametros);
    }

    public function desativar(string $tabela, int $id): int {
        $comando = "UPDATE {$tabela} SET ativo = :ativo WHERE id = :id";
        $parametros = ['ativo' => 0, 'id' => $id];

        return $this->executar($comando, $parametros);
    }

    public function existe(string $tabela, string $campo, string $valor): bool {
        $comando = "SELECT COUNT(*) as quantidadeRegistros FROM {$tabela}
            WHERE {$campo} = :valor
            AND ativo = :ativo";
        $parametros = ['valor' => $valor, 'ativo' => 1];

        $resultado = $this->consultar($comando, $parametros)[0];
        $existe = isset($resultado['quantidadeRegistros']) && $resultado['quantidadeRegistros'] > 0;

        return $existe;
    }

    public function executarComTransacao(callable $operacao): mixed {
        $transacaoAtiva = $this->emTransacao();
        $resultado = null;

        try {
            if (!$transacaoAtiva) {
                $this->iniciarTransacao();
            }

            $resultado = $operacao();

            if (!$transacaoAtiva) {
                $this->finalizarTransacao();
            }
        } catch (Throwable $e) {
            if (!$transacaoAtiva) {
                $this->desfazerTransacao();
            }

            throw $e;
        }

        return $resultado;
    }

    public function ultimoIdInserido(): int {
        return intval($this->pdo->lastInsertId());
    }

    public function iniciarTransacao(): void {
        $this->pdo->beginTransaction();
    }

    public function finalizarTransacao(): void {
        $this->pdo->commit();
    }

    public function desfazerTransacao(): void {
        $this->pdo->rollBack();
    }

    public function emTransacao(): bool {
        return $this->pdo->inTransaction();
    }
}