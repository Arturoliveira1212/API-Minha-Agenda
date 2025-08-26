<?php

namespace MinhaAgenda\Auth;

use DateTime;
use Throwable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MinhaAgenda\Auth\TokenJWT;
use MinhaAgenda\Auth\PayloadJWT;

class AutenticacaoJWT {

    public static function gerarToken(
        string $id,
        string $nome,
        string $papel,
        int $duracaoEmSegundos = 3600,
        string $algoritimoCriptografia = 'HS256'
    ): TokenJWT {
        $dataCriacao = new DateTime();
        $dataExpiracao = new DateTime()->setTimestamp($dataCriacao->getTimestamp() + $duracaoEmSegundos);

        $payloadJWT = new PayloadJWT($id, $nome, $papel, $dataExpiracao->getTimestamp(), $dataExpiracao->getTimestamp());
        $token = JWT::encode($payloadJWT->emArray(), $_ENV['SECRET_KEY_JWT'], $algoritimoCriptografia);
        $tokenJWT = new TokenJWT($token, $dataCriacao, $dataExpiracao);

        return $tokenJWT;
    }

    public static function decodificarToken(string $token, string $algoritimoCriptografia = 'HS256'): PayloadJWT|null {
        try {
            $payload = JWT::decode($token, new Key($_ENV['SECRET_KEY_JWT'], $algoritimoCriptografia));
            $payloadJWT = new PayloadJWT($payload->sub, $payload->name, $payload->role, $payload->iat, $payload->exp);

            return $payloadJWT;
        } catch (Throwable $e) {
            return null;
        }
    }
}
