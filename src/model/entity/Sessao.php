<?php

namespace MinhaAgenda\Model\Entity;

use DateTime;

class Sessao extends Model {
    private Usuario $usuario;
    private string $accessToken;
    private DateTime $dataCriacaoAccessToken;
    private DateTime $dataExpiracaoAccessToken;
    private string $refreshToken;
    private DateTime $dataCriacaoRefreshToken;
    private DateTime $dataExpiracaoRefreshToken;
    private DateTime $dataCriacao;
    private DateTime|null $dataAtualizacao;
    private bool $revogado;

    public function getUsuario(): Usuario {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): self {
        $this->usuario = $usuario;
        return $this;
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getDataCriacaoAccessToken(string $formato = null): DateTime|string {
        if ($formato != null) {
            return $this->dataCriacaoAccessToken->format($formato);
        }
        return $this->dataCriacaoAccessToken;
    }

    public function setDataCriacaoAccessToken(DateTime $dataCriacaoAccessToken): self {
        $this->dataCriacaoAccessToken = $dataCriacaoAccessToken;
        return $this;
    }

    public function getDataExpiracaoAccessToken(string $formato = null): DateTime|string {
        if ($formato != null) {
            return $this->dataExpiracaoAccessToken->format($formato);
        }
        return $this->dataExpiracaoAccessToken;
    }

    public function setDataExpiracaoAccessToken(DateTime $dataExpiracaoAccessToken): self {
        $this->dataExpiracaoAccessToken = $dataExpiracaoAccessToken;
        return $this;
    }

    public function getRefreshToken(): string {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getDataCriacaoRefreshToken(string $formato = null): DateTime|string {
        if ($formato != null) {
            return $this->dataCriacaoRefreshToken->format($formato);
        }
        return $this->dataCriacaoRefreshToken;
    }

    public function setDataCriacaoRefreshToken(DateTime $dataCriacaoRefreshToken): self {
        $this->dataCriacaoRefreshToken = $dataCriacaoRefreshToken;
        return $this;
    }

    public function getDataExpiracaoRefreshToken(string $formato = null): DateTime|string {
        if ($formato != null) {
            return $this->dataExpiracaoRefreshToken->format($formato);
        }
        return $this->dataExpiracaoRefreshToken;
    }

    public function setDataExpiracaoRefreshToken(DateTime $dataExpiracaoRefreshToken): self {
        $this->dataExpiracaoRefreshToken = $dataExpiracaoRefreshToken;
        return $this;
    }

    public function getDataCriacao(string $formato = null): DateTime|string {
        if ($formato != null) {
            return $this->dataCriacao->format($formato);
        }
        return $this->dataCriacao;
    }

    public function setDataCriacao(DateTime $dataCriacao): self {
        $this->dataCriacao = $dataCriacao;
        return $this;
    }

    public function getDataAtualizacao(string $formato = null): DateTime|string|null {
        if ($formato != null && $this->dataAtualizacao != null) {
            return $this->dataAtualizacao->format($formato);
        }
        return $this->dataAtualizacao;
    }

    public function setDataAtualizacao(?DateTime $dataAtualizacao): self {
        $this->dataAtualizacao = $dataAtualizacao;
        return $this;
    }

    public function getRevogado(): bool {
        return $this->revogado;
    }

    public function setRevogado(bool $revogado): self {
        $this->revogado = $revogado;
        return $this;
    }

    public function emArray(): array {
        return [
            'accessToken' => [
                'token' => $this->getAccessToken(),
                'criadoEm' => $this->getDataCriacaoAccessToken('Y-m-d H:i:s'),
                'expiraEm' => $this->getDataExpiracaoAccessToken('Y-m-d H:i:s'),
            ],
            'refreshToken' => [
                'token' => $this->getRefreshToken(),
                'criadoEm' => $this->getDataCriacaoRefreshToken('Y-m-d H:i:s'),
                'expiraEm' => $this->getDataExpiracaoRefreshToken('Y-m-d H:i:s'),
            ]
        ];
    }
}