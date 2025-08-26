<?php

namespace MinhaAgenda\Trait;

trait Criptografavel {

    public function gerarHashSenha(string $senha): string {
        return password_hash($senha, PASSWORD_DEFAULT);
    }

    public function verificarHashSenha(string $senha, string $hash): bool {
        return password_verify($senha, $hash);
    }

    public function gerarHash(string $conteudo, string $algoritimoCriptografia = 'sha256'): string {
        return hash($algoritimoCriptografia, $conteudo);
    }
}
