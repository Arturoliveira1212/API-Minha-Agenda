<?php

namespace MinhaAgenda\Model\Entity;

use MinhaAgenda\Model\Entity\Model;

class Usuario extends Model {
    private string $nome;
    private string $email;

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

    public function emArray(): array {
        return [
            'id' => $this->getId(),
            'nome' => $this->getNome(),
            'email' => $this->getEmail(),
        ];
    }
}
