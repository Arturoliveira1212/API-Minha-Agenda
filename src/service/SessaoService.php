<?php

namespace MinhaAgenda\Service;

use DateTime;
use Exception;
use MinhaAgenda\Auth\TokenJWT;
use MinhaAgenda\Service\Service;
use MinhaAgenda\Enum\TipoUsuario;
use MinhaAgenda\Model\Entity\Model;
use MinhaAgenda\Model\Entity\Sessao;
use MinhaAgenda\Auth\AutenticacaoJWT;
use MinhaAgenda\Factory\ClassFactory;
use MinhaAgenda\Model\Entity\Cliente;
use MinhaAgenda\Model\Entity\Usuario;
use MinhaAgenda\Trait\Criptografavel;
use MinhaAgenda\Model\Entity\Administrador;
use MinhaAgenda\Exception\NaoAutorizadoException;

class SessaoService extends Service {
    use Criptografavel;
    const DURACAO_ACCESS_TOKEN = 3600; // 1 hora
    const DURACAO_REFRESH_TOKEN = 86400; // 24 horas

    public function login(array $dados, TipoUsuario $tipoUsuario): Sessao {
        $usuario = $this->obterUsuario($dados['email'], $tipoUsuario);
        if (!$usuario instanceof Usuario || !$this->verificarHashSenha($dados['senha'], $usuario->getSenha())) {
            throw new NaoAutorizadoException('Usuário ou senha inválidos.');
        }

        $accessToken = AutenticacaoJWT::gerarToken(
            $usuario->getId(),
            $usuario->getNome(),
            $usuario->getTipo()->value,
            self::DURACAO_ACCESS_TOKEN
        );
        $refreshToken = AutenticacaoJWT::gerarToken(
            $usuario->getId(),
            $usuario->getNome(),
            $usuario->getTipo()->value,
            self::DURACAO_REFRESH_TOKEN
        );

        $sessao = $this->criarSessao($usuario, $accessToken, $refreshToken);
        $this->repository->salvarSessao($sessao);

        return $sessao;
    }

    private function obterUsuario(string $email, TipoUsuario $tipoUsuario): Usuario {
        $service = $tipoUsuario == TipoUsuario::CLIENTE
            ? ClassFactory::makeService(Cliente::class)
            : ClassFactory::makeService(Administrador::class);

        return $service->obterComEmail($email);
    }

    private function criarSessao(Usuario $usuario, TokenJWT $accessToken, TokenJWT $refreshToken): Sessao {
        return new Sessao()
            ->setUsuario($usuario)
            ->setAccessToken($this->gerarHash($accessToken->getCodigo()))
            ->setDataCriacaoAccessToken($accessToken->getDataCriacao())
            ->setDataExpiracaoAccessToken($accessToken->getDataExpiracao())
            ->setRefreshToken($this->gerarHash($refreshToken->getCodigo()))
            ->setDataCriacaoRefreshToken($refreshToken->getDataCriacao())
            ->setDataExpiracaoRefreshToken($refreshToken->getDataExpiracao())
            ->setDataCriacao(new DateTime())
            ->setRevogado(false);
    }

    protected function criarObjeto(array $dados, int|null $id = null, int|null $idRecursoPai = null): Model {
        throw new Exception('Método não implementado.');
    }
}