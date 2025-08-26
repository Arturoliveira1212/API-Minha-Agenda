<?php

namespace MinhaAgenda\Model\Repository;

use Exception;
use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Sessao;
use MinhaAgenda\Model\Entity\Cliente;
use MinhaAgenda\Model\Repository\RepositoryEmBDR;

class SessaoRepository extends RepositoryEmBDR {

    protected function nomeTabela(): string {
        return 'sessao';
    }

    public function adicionarNovo(Model $sessao, int|null $idRecursoPai = null): void {
        if (!$sessao instanceof Sessao) {
            throw new Exception('Objeto deve ser do tipo Sessao.');
        }

        $this->criarNovaSessao($sessao);
    }

    protected function atualizar(Model $sessao): void {
        if (!$sessao instanceof Sessao) {
            throw new Exception('Objeto deve ser do tipo Sessao.');
        }

        $this->renovarSessao($sessao);
    }

    /**
     * Cria uma nova sessão no banco (usado no login inicial)
     */
    public function criarNovaSessao(Sessao $sessao): void {
        $comando = "INSERT INTO {$this->nomeTabela()} (
                idUsuario,
                accessToken,
                dataCriacaoAccessToken,
                dataExpiracaoAccessToken,
                refreshToken,
                dataCriacaoRefreshToken,
                dataExpiracaoRefreshToken,
                dataCriacao,
                dataAtualizacao,
                revogado,
                ativo
            ) VALUES (
                :idUsuario,
                :accessToken,
                :dataCriacaoAccessToken,
                :dataExpiracaoAccessToken,
                :refreshToken,
                :dataCriacaoRefreshToken,
                :dataExpiracaoRefreshToken,
                :dataCriacao,
                :dataAtualizacao,
                :revogado,
                :ativo
            )";
        $parametros = $this->parametros($sessao);

        $this->bancoDados->executar($comando, $parametros);
    }

    /**
     * Renova uma sessão existente (usado no refresh token)
     * ATUALIZA a linha existente ao invés de criar uma nova
     */
    public function renovarSessao(Sessao $sessao): void {
        $comando = "UPDATE {$this->nomeTabela()} SET
                accessToken = :accessToken,
                dataCriacaoAccessToken = :dataCriacaoAccessToken,
                dataExpiracaoAccessToken = :dataExpiracaoAccessToken,
                refreshToken = :refreshToken,
                dataCriacaoRefreshToken = :dataCriacaoRefreshToken,
                dataExpiracaoRefreshToken = :dataExpiracaoRefreshToken,
                dataAtualizacao = :dataAtualizacao
            WHERE id = :id AND ativo = 1";

        $parametros = $this->parametros($sessao);
        $parametros['id'] = $sessao->getId();

        $this->bancoDados->executar($comando, $parametros);
    }

    private function parametros(Sessao $sessao): array {
        return [
            'idUsuario' => $sessao->getUsuario()->getId(),
            'accessToken' => $sessao->getAccessToken(),
            'dataCriacaoAccessToken' => $sessao->getDataCriacaoAccessToken('Y-m-d H:i:s'),
            'dataExpiracaoAccessToken' => $sessao->getDataExpiracaoAccessToken('Y-m-d H:i:s'),
            'refreshToken' => $sessao->getRefreshToken(),
            'dataCriacaoRefreshToken' => $sessao->getDataCriacaoRefreshToken('Y-m-d H:i:s'),
            'dataExpiracaoRefreshToken' => $sessao->getDataExpiracaoRefreshToken('Y-m-d H:i:s'),
            'dataCriacao' => $sessao->getDataCriacao('Y-m-d H:i:s'),
            'dataAtualizacao' => $sessao->getDataAtualizacao('Y-m-d H:i:s'),
            'revogado' => $sessao->getRevogado() ? 1 : 0,
            'ativo' => 1 // Sempre ativo ao criar/atualizar
        ];
    }

    /**
     * Busca sessão pelo refresh token
     */
    public function obterPorRefreshToken(string $refreshToken): ?Sessao {
        $comando = "SELECT * FROM {$this->nomeTabela()} 
                   WHERE refreshToken = :refreshToken 
                   AND dataExpiracaoRefreshToken > NOW() 
                   AND revogado = 0 
                   AND ativo = 1";

        $parametros = ['refreshToken' => $refreshToken];
        $resultado = $this->bancoDados->consultar($comando, $parametros);

        if (empty($resultado)) {
            return null;
        }

        return $this->transformarEmSessao($resultado[0]);
    }

    /**
     * Busca sessão pelo access token
     */
    public function obterPorAccessToken(string $accessToken): ?Sessao {
        $comando = "SELECT * FROM {$this->nomeTabela()} 
                   WHERE accessToken = :accessToken 
                   AND dataExpiracaoAccessToken > NOW() 
                   AND revogado = 0 
                   AND ativo = 1";

        $parametros = ['accessToken' => $accessToken];
        $resultado = $this->bancoDados->consultar($comando, $parametros);

        if (empty($resultado)) {
            return null;
        }

        return $this->transformarEmSessao($resultado[0]);
    }

    /**
     * Revoga uma sessão (logout)
     */
    public function revogarSessao(int $idSessao): void {
        $comando = "UPDATE {$this->nomeTabela()} 
                   SET revogado = 1, dataAtualizacao = NOW() 
                   WHERE id = :id";

        $parametros = ['id' => $idSessao];
        $this->bancoDados->executar($comando, $parametros);
    }

    /**
     * Revoga todas as sessões de um usuário
     */
    public function revogarTodasSessoesUsuario(int $idUsuario): void {
        $comando = "UPDATE {$this->nomeTabela()} 
                   SET revogado = 1, dataAtualizacao = NOW() 
                   WHERE idUsuario = :idUsuario AND ativo = 1";

        $parametros = ['idUsuario' => $idUsuario];
        $this->bancoDados->executar($comando, $parametros);
    }

    /**
     * Remove sessões expiradas (limpeza automática)
     */
    public function limparSessoesExpiradas(): int {
        $comando = "UPDATE {$this->nomeTabela()} 
                   SET ativo = 0 
                   WHERE (dataExpiracaoAccessToken < NOW() 
                         AND dataExpiracaoRefreshToken < NOW()) 
                   AND ativo = 1";

        return $this->bancoDados->executar($comando);
    }

    /**
     * Transforma dados do banco em objeto Sessao
     */
    private function transformarEmSessao(array $dados): Sessao {
        // Aqui você precisará implementar baseado na sua classe Sessao
        // Como não tenho acesso à classe Sessao completa, vou fazer um exemplo básico
        $sessao = new Sessao(intval($dados['id']));
        // $sessao->setUsuario(...) // Você precisará buscar o usuário também
        // $sessao->setAccessToken($dados['accessToken']);
        // ... outros campos

        return $sessao;
    }

    protected function gerarQuery(array $restricoes = [], array &$parametros = []): string {
        $nomeTabela = $this->nomeTabela();

        $select = "SELECT * FROM {$nomeTabela}";
        $where = ' WHERE ativo = 1 ';
        $join = '';
        $orderBy = '';

        $comando = "{$select} {$join} {$where} {$orderBy}";

        return $comando;
    }

    protected function transformarEmObjeto(array $dados): Sessao {
        return $this->transformarEmSessao($dados);
    }
}

