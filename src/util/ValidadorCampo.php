<?php

namespace MinhaAgenda\Util;

use DateTime;

class ValidadorCampo {
    private readonly Validador $validador;
    private readonly string $nomeCampo;
    private readonly mixed $valor;

    public function __construct(Validador $validador, string $nomeCampo, mixed $valor) {
        $this->validador = $validador;
        $this->nomeCampo = $nomeCampo;
        $this->valor = $valor;
    }

    private function valorEstaVazio(): bool {
        return $this->valor === null || $this->valor === '' || (is_array($this->valor) && empty($this->valor));
    }

    public function obrigatorio(string $mensagem = null): self {
        if ($this->valorEstaVazio()) {
            $mensagem ??= "O campo {$this->nomeCampo} é obrigatório";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function email(string $mensagem = null): self {
        if (!filter_var($this->valor, FILTER_VALIDATE_EMAIL)) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser um email válido";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function min(int $tamanhoMinimo, string $mensagem = null): self {
        $tamanho = mb_strlen((string) $this->valor);
        if ($tamanho < $tamanhoMinimo) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ter pelo menos {$tamanhoMinimo} caracteres";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function max(int $tamanhoMaximo, string $mensagem = null): self {
        $tamanho = mb_strlen((string) $this->valor);
        if ($tamanho > $tamanhoMaximo) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ter no máximo {$tamanhoMaximo} caracteres";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function numerico(string $mensagem = null): self {
        if (!is_numeric($this->valor)) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser numérico";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function inteiro(string $mensagem = null): self {
        if (filter_var($this->valor, FILTER_VALIDATE_INT) === false) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser um número inteiro";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function decimal(string $mensagem = null): self {
        if (filter_var($this->valor, FILTER_VALIDATE_FLOAT) === false) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser um número decimal";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function data(string $formato = 'Y-m-d', string $mensagem = null): self {
        $data = DateTime::createFromFormat($formato, (string) $this->valor);
        if (!$data) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser uma data válida no formato {$formato}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function em(array $valores, string $mensagem = null): self {
        if (!in_array($this->valor, $valores)) {
            $valoresPermitidos = implode(', ', $valores);
            $mensagem ??= "O campo {$this->nomeCampo} deve ser um dos valores: {$valoresPermitidos}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function maiorQue(float $valorComparacao, string $mensagem = null): self {
        $valorNumerico = is_numeric($this->valor) ? (float) $this->valor : null;
        if ($valorNumerico === null || $valorNumerico <= $valorComparacao) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser maior que {$valorComparacao}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function menorQue(float $valorComparacao, string $mensagem = null): self {
        $valorNumerico = is_numeric($this->valor) ? (float) $this->valor : null;
        if ($valorNumerico === null || $valorNumerico >= $valorComparacao) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser menor que {$valorComparacao}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function maiorOuIgualA(float $valorComparacao, string $mensagem = null): self {
        $valorNumerico = is_numeric($this->valor) ? (float) $this->valor : null;
        if ($valorNumerico === null || $valorNumerico < $valorComparacao) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser maior ou igual a {$valorComparacao}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function menorOuIgualA(float $valorComparacao, string $mensagem = null): self {
        $valorNumerico = is_numeric($this->valor) ? (float) $this->valor : null;
        if ($valorNumerico === null || $valorNumerico > $valorComparacao) {
            $mensagem ??= "O campo {$this->nomeCampo} deve ser menor ou igual a {$valorComparacao}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function regex(string $padrao, string $mensagem = null): self {
        if (!preg_match($padrao, (string) $this->valor)) {
            $mensagem ??= "O campo {$this->nomeCampo} não atende ao formato exigido";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }
}
