<?php

namespace MinhaAgenda\Controller;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use MinhaAgenda\Util\Validador;
use MinhaAgenda\Enum\StatusHttp;
use MinhaAgenda\Controller\Controller;
use MinhaAgenda\Exception\ValidacaoException;

class ClienteController extends Controller {

    public function novo(Request $request, Response $response, array $args): Response {
        $corpoRequisicao = (array) $request->getParsedBody();
        $this->validarCorpoRequisicaoNovo($corpoRequisicao);
        $id = $this->service->salvar($corpoRequisicao);

        return $this->enviarResposta($response, StatusHttp::CREATED, 'Usuário criado com sucesso.', [
            'idCriado' => $id
        ]);
    }

    private function validarCorpoRequisicaoNovo(array $corpoRequisicao): void {
        $validador = new Validador($corpoRequisicao);
        $validador->campo('nome')->obrigatorio()->min(3)->max(100);
        $validador->campo('email')->obrigatorio()->email();
        $validador->campo('senha')->obrigatorio()->min(8)->max(20);

        if (!$validador->ehValido()) {
            throw new ValidacaoException($validador->obterErros());
        }
    }

    public function atualizar(Request $request, Response $response, array $args): Response {
        return $this->enviarResposta($response, StatusHttp::OK, 'Usuário atualizado com sucesso.');
    }

    public function obterTodos(Request $request, Response $response, array $args): Response {
        return $this->enviarResposta($response, StatusHttp::OK, 'Lista de usuários', [
            'dados' => [] // Aqui você deve retornar a lista de usuários
        ]);
    }

    public function obterComId(Request $request, Response $response, array $args): Response {
        return $this->enviarResposta($response, StatusHttp::OK, 'Usuário encontrado', [
            'dados' => [] // Aqui você deve retornar os dados do usuário
        ]);
    }

    public function excluirComId(Request $request, Response $response, array $args): Response {
        return $this->enviarResposta($response, StatusHttp::NO_CONTENT);
    }
}
