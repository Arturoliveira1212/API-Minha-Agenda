<?php

namespace MinhaAgenda\Util;

class ValidadorCampo {
    private Validador $validador;
    private string $nomeCampo;
    private mixed $valor;

    public function __construct(Validador $validador, string $nomeCampo, mixed $valor) {
        $this->validador = $validador;
        $this->nomeCampo = $nomeCampo;
        $this->valor = $valor;
    }

    public function obrigatorio(string $mensagem = null): self {
        if ($this->valor === null || $this->valor === '' || $this->valor === []) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} é obrigatório";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function email(string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !filter_var($this->valor, FILTER_VALIDATE_EMAIL)) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser um email válido";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function min(int $tamanhoMinimo, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && strlen((string)$this->valor) < $tamanhoMinimo) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ter pelo menos {$tamanhoMinimo} caracteres";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function max(int $tamanhoMaximo, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && strlen((string)$this->valor) > $tamanhoMaximo) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ter no máximo {$tamanhoMaximo} caracteres";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function numerico(string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !is_numeric($this->valor)) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser numérico";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function inteiro(string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !filter_var($this->valor, FILTER_VALIDATE_INT)) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser um número inteiro";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function decimal(string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !filter_var($this->valor, FILTER_VALIDATE_FLOAT)) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser um número decimal";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function data(string $formato = 'Y-m-d', string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '') {
            $dataHora = \DateTime::createFromFormat($formato, (string)$this->valor);
            if (!$dataHora || $dataHora->format($formato) !== (string)$this->valor) {
                $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser uma data válida no formato {$formato}";
                $this->validador->adicionarErro($this->nomeCampo, $mensagem);
            }
        }

        return $this;
    }

    public function em(array $valores, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !in_array($this->valor, $valores)) {
            $valoresPermitidos = implode(', ', $valores);
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser um dos valores: {$valoresPermitidos}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function maiorQue(float $valorComparacao, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '') {
            $valorNumerico = is_numeric($this->valor) ? (float)$this->valor : null;
            if ($valorNumerico === null || $valorNumerico <= $valorComparacao) {
                $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser maior que {$valorComparacao}";
                $this->validador->adicionarErro($this->nomeCampo, $mensagem);
            }
        }

        return $this;
    }

    public function menorQue(float $valorComparacao, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '') {
            $valorNumerico = is_numeric($this->valor) ? (float)$this->valor : null;
            if ($valorNumerico === null || $valorNumerico >= $valorComparacao) {
                $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser menor que {$valorComparacao}";
                $this->validador->adicionarErro($this->nomeCampo, $mensagem);
            }
        }

        return $this;
    }

    public function maiorOuIgualA(float $valorComparacao, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '') {
            $valorNumerico = is_numeric($this->valor) ? (float)$this->valor : null;
            if ($valorNumerico === null || $valorNumerico < $valorComparacao) {
                $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser maior ou igual a {$valorComparacao}";
                $this->validador->adicionarErro($this->nomeCampo, $mensagem);
            }
        }

        return $this;
    }

    public function menorOuIgualA(float $valorComparacao, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '') {
            $valorNumerico = is_numeric($this->valor) ? (float)$this->valor : null;
            if ($valorNumerico === null || $valorNumerico > $valorComparacao) {
                $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser menor ou igual a {$valorComparacao}";
                $this->validador->adicionarErro($this->nomeCampo, $mensagem);
            }
        }

        return $this;
    }

    public function regex(string $padrao, string $mensagem = null): self {
        if ($this->valor !== null && $this->valor !== '' && !preg_match($padrao, (string)$this->valor)) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} não atende ao formato exigido";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function personalizado(callable $callback, string $mensagem): self {
        if ($this->valor !== null && !$callback($this->valor)) {
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }

    public function confirmarCom(string $outroCampo, string $mensagem = null): self {
        $outroValor = $this->validador->obterValorCampo($outroCampo);
        if ($this->valor !== $outroValor) {
            $mensagem = $mensagem ?? "O campo {$this->nomeCampo} deve ser igual ao campo {$outroCampo}";
            $this->validador->adicionarErro($this->nomeCampo, $mensagem);
        }

        return $this;
    }
}
