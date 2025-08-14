<?php

namespace MinhaAgenda\Util;

class Validador {
    private array $dados = [];
    private array $erros = [];

    public function __construct(array $dados) {
        $this->dados = $dados;
    }

    public function campo(string $nomeCampo): ValidadorCampo {
        return new ValidadorCampo($this, $nomeCampo, $this->dados[$nomeCampo] ?? null);
    }

    public function adicionarErro(string $campo, string $mensagem): void {
        $this->erros[$campo][] = $mensagem;
    }

    public function ehValido(): bool {
        return empty($this->erros);
    }

    public function obterErros(): array {
        return $this->erros;
    }

    public function obterPrimeiroErro(): ?string {
        foreach ($this->erros as $errosCampo) {
            return $errosCampo[0] ?? null;
        }

        return null;
    }

    public function obterErrosDoCampo(string $campo): array {
        return $this->erros[$campo] ?? [];
    }

    public function obterValorCampo(string $campo): mixed {
        return $this->dados[$campo] ?? null;
    }
}
