<?php

namespace MinhaAgenda\Trait;

use MinhaAgenda\Enum\StatusHttp;
use Slim\Psr7\Response;

trait RespostaAPI {
    public function enviarResposta(
        Response $response, StatusHttp $status = StatusHttp::OK, string $mensagem = '', array $dados = [], array $headers = ['Content-type' => 'application/json']
    ): Response {
        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        if (!empty($mensagem)) {
            $dados['message'] = $mensagem;
        }

        if (!empty($dados)) {
            $response->getBody()->write(json_encode([
                'sucess' => StatusHttp::statusEhSucesso($status),
                ...$dados
            ]));
        }

        return $response->withStatus($status->value);
    }
}