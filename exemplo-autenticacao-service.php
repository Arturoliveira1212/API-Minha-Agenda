<?php

namespace MinhaAgenda\Service;

use MinhaAgenda\Model\Entity\Sessao;
use MinhaAgenda\Model\Repository\SessaoRepository;
use MinhaAgenda\Model\Repository\UsuarioRepository;
use MinhaAgenda\Auth\TokenJWT;
use MinhaAgenda\Exception\NaoAutorizadoException;
use DateTime;

/**
 * Exemplo de implementação do fluxo de sessão correto
 */
class AutenticacaoService {
    
    private SessaoRepository $sessaoRepository;
    private UsuarioRepository $usuarioRepository;
    private TokenJWT $tokenJWT;

    public function __construct(
        SessaoRepository $sessaoRepository,
        UsuarioRepository $usuarioRepository,
        TokenJWT $tokenJWT
    ) {
        $this->sessaoRepository = $sessaoRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->tokenJWT = $tokenJWT;
    }

    /**
     * LOGIN INICIAL - Cria NOVA linha na tabela sessao
     */
    public function login(string $email, string $senha): array {
        // 1. Validar credenciais
        $usuario = $this->usuarioRepository->obterPorEmail($email);
        if (!$usuario || !password_verify($senha, $usuario->getSenha())) {
            throw new NaoAutorizadoException('Credenciais inválidas.');
        }

        // 2. Gerar tokens
        $accessToken = $this->tokenJWT->gerarAccessToken($usuario);
        $refreshToken = $this->tokenJWT->gerarRefreshToken($usuario);

        // 3. CRIAR NOVA SESSÃO (nova linha na tabela)
        $sessao = new Sessao();
        $sessao->setUsuario($usuario);
        $sessao->setAccessToken($accessToken);
        $sessao->setRefreshToken($refreshToken);
        $sessao->setDataCriacaoAccessToken(new DateTime());
        $sessao->setDataExpiracaoAccessToken(new DateTime('+15 minutes'));
        $sessao->setDataCriacaoRefreshToken(new DateTime());
        $sessao->setDataExpiracaoRefreshToken(new DateTime('+30 days'));
        $sessao->setDataCriacao(new DateTime());
        $sessao->setDataAtualizacao(new DateTime());
        $sessao->setRevogado(false);

        // 4. Salvar no banco (INSERT - nova linha)
        $this->sessaoRepository->criarNovaSessao($sessao);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 900, // 15 minutos
            'token_type' => 'Bearer'
        ];
    }

    /**
     * RENOVAÇÃO DE TOKEN - ATUALIZA linha existente (NÃO cria nova)
     */
    public function renovarToken(string $refreshToken): array {
        // 1. Buscar sessão pelo refresh token
        $sessao = $this->sessaoRepository->obterPorRefreshToken($refreshToken);
        if (!$sessao) {
            throw new NaoAutorizadoException('Refresh token inválido ou expirado.');
        }

        // 2. Gerar NOVOS tokens
        $usuario = $sessao->getUsuario();
        $novoAccessToken = $this->tokenJWT->gerarAccessToken($usuario);
        $novoRefreshToken = $this->tokenJWT->gerarRefreshToken($usuario);

        // 3. ATUALIZAR sessão existente (mesmo ID)
        $sessao->setAccessToken($novoAccessToken);
        $sessao->setRefreshToken($novoRefreshToken);
        $sessao->setDataCriacaoAccessToken(new DateTime());
        $sessao->setDataExpiracaoAccessToken(new DateTime('+15 minutes'));
        $sessao->setDataCriacaoRefreshToken(new DateTime());
        $sessao->setDataExpiracaoRefreshToken(new DateTime('+30 days'));
        $sessao->setDataAtualizacao(new DateTime());

        // 4. Salvar no banco (UPDATE - mesma linha)
        $this->sessaoRepository->renovarSessao($sessao);

        return [
            'access_token' => $novoAccessToken,
            'refresh_token' => $novoRefreshToken,
            'expires_in' => 900, // 15 minutos
            'token_type' => 'Bearer'
        ];
    }

    /**
     * LOGOUT - Revoga sessão
     */
    public function logout(string $accessToken): void {
        $sessao = $this->sessaoRepository->obterPorAccessToken($accessToken);
        if ($sessao) {
            $this->sessaoRepository->revogarSessao($sessao->getId());
        }
    }

    /**
     * VALIDAÇÃO DE TOKEN - Para middleware de autenticação
     */
    public function validarAccessToken(string $accessToken): ?array {
        $sessao = $this->sessaoRepository->obterPorAccessToken($accessToken);
        if (!$sessao) {
            return null;
        }

        $usuario = $sessao->getUsuario();
        return [
            'id' => $usuario->getId(),
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail(),
            'tipo' => $usuario->getTipo()->value
        ];
    }

    /**
     * LIMPEZA AUTOMÁTICA - Para ser executada via cronjob
     */
    public function limparSessoesExpiradas(): int {
        return $this->sessaoRepository->limparSessoesExpiradas();
    }
}
