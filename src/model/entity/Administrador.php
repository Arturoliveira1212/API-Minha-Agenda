<?php

namespace MinhaAgenda\Model\Entity;

use MinhaAgenda\Enum\TipoUsuario;
use MinhaAgenda\Model\Entity\Model;

class Administrador extends Model implements Usuario {
    private string $nome;
    private string $email;
    private string $senha;
    private TipoUsuario $tipo;

    public function getNome(): string {
        return $this->nome;
    }

    public function setNome(string $nome): self {
        $this->nome = $nome;
        return $this;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): self {
        $this->email = $email;
        return $this;
    }

    public function getSenha(): string {
        return $this->senha;
    }

    public function setSenha(string $senha): self {
        $this->senha = $senha;
        return $this;
    }

    public function getTipo(): TipoUsuario {
        return $this->tipo;
    }

    public function setTipo($tipo): self {
        $this->tipo = $tipo;
        return $this;
    }

    public function emArray(): array {
        return [
            'id' => $this->getId(),
            'nome' => $this->getNome(),
            'email' => $this->getEmail(),
        ];
    }
}
