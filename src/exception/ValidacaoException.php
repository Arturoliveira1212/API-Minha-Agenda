<?php

namespace MinhaAgenda\Exception;

use Exception;

class ValidacaoException extends Exception {
    private array $erros;

    public function __construct(array $erros) {
        $this->erros = $erros;
    }

    public function obterErros(): array {
        return $this->erros;
    }
}
