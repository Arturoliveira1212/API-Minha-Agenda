<?php

namespace MinhaAgenda\Auth;

use DateTime;

class TokenJWT {
    private readonly string $codigo;
    private readonly DateTime $dataCriacao;
    private readonly DateTime $dataExpiracao;

    public function __construct(string $codigo, DateTime $dataCriacao, DateTime $dataExpiracao) {
        $this->codigo = $codigo;
        $this->dataCriacao = $dataCriacao;
        $this->dataExpiracao = $dataExpiracao;
    }

    public function getCodigo(): string {
        return $this->codigo;
    }

    public function getDataCriacao(): DateTime {
        return $this->dataCriacao;
    }

    public function getDataExpiracao(): DateTime {
        return $this->dataExpiracao;
    }
}
