<?php

namespace MinhaAgenda\Controller;

use MinhaAgenda\Enum\TipoUsuario;
use MinhaAgenda\Model\Entity\Usuario;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use MinhaAgenda\Util\Validador;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Controller\Controller;
use MinhaAgenda\Exception\ValidacaoException;

class SessaoController extends Controller {

    private function obterTipoUsuario(array $args): TipoUsuario {
        $tipoUsuario = null;

        match ($args['tipoUsuario']) {
            'clientes' => $tipoUsuario = TipoUsuario::CLIENTE,
            'funcionarios' => $tipoUsuario = TipoUsuario::FUNCIONARIO,
            'gerentes' => $tipoUsuario = TipoUsuario::GERENTE,
            'administradores' => $tipoUsuario = TipoUsuario::ADMINISTRADOR,
            default => throw new ValidacaoException(['Tipo de usuário inválido']),
        };

        return $tipoUsuario;
    }

    public function login(Request $request, Response $response, array $args): Response {
        $corpoRequisicao = (array) $request->getParsedBody();

        $tipoUsuario = $this->obterTipoUsuario($args);
        $this->validarCorpoRequisicaoLogin($corpoRequisicao);
        $sessao = $this->service->login($corpoRequisicao, $tipoUsuario);

        return $this->enviarResposta($response, StatusHttp::OK, 'Login realizado com sucesso.', $sessao);
    }

    private function validarCorpoRequisicaoLogin(array $corpoRequisicao): void {
        $validador = new Validador($corpoRequisicao);
        $validador->campo('email')->obrigatorio()->email();
        $validador->campo('senha')->obrigatorio()->min(Usuario::TAMANHO_MINIMO_SENHA)->max(Usuario::TAMANHO_MAXIMO_SENHA);

        if (!$validador->ehValido()) {
            throw new ValidacaoException($validador->obterErros());
        }
    }

    public function refresh(Request $request, Response $response, array $args): Response {
        $tipoUsuarios = $args['tipoUsuarios'] ?? null;
        $mensagem = ($tipoUsuarios === 'clientes') ?
            'Token de cliente renovado com sucesso.' :
            'Token de administrador renovado com sucesso.';

        return $this->enviarResposta($response, StatusHttp::OK, $mensagem);
    }

    public function logout(Request $request, Response $response, array $args): Response {
        $tipoUsuarios = $args['tipoUsuarios'] ?? null;
        $mensagem = ($tipoUsuarios === 'clientes') ?
            'Cliente deslogado com sucesso.' :
            'Administrador deslogado com sucesso.';

        return $this->enviarResposta($response, StatusHttp::OK, $mensagem);
    }
}