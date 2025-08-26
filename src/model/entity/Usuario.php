<?php

namespace MinhaAgenda\Model\Entity;

use MinhaAgenda\Enum\TipoUsuario;

interface Usuario {
    const TAMANHO_MINIMO_SENHA = 8;
    const TAMANHO_MAXIMO_SENHA = 20;

    public function getId(): int;
    public function getNome(): string;
    public function getEmail(): string;
    public function getSenha(): string;
    public function getTipo(): TipoUsuario;
}