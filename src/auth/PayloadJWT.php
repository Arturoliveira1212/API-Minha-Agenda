<?php

namespace MinhaAgenda\Auth;

class PayloadJWT {
    private readonly int $sub;
    private readonly ?string $name;
    private readonly ?string $role;
    private readonly int $iat;
    private readonly int $exp;

    public function __construct(int $sub, ?string $name, ?string $role, int $iat, int $exp) {
        $this->sub = $sub;
        $this->name = $name;
        $this->role = $role;
        $this->iat = $iat;
        $this->exp = $exp;
    }

    public function sub(): int {
        return $this->sub;
    }

    public function name(): string|null {
        return $this->name;
    }

    public function role(): string|null {
        return $this->role;
    }

    public function iat(): int {
        return $this->iat;
    }

    public function exp(): int {
        return $this->exp;
    }

    public function emArray(): array {
        return [
            'sub' => $this->sub,
            'name' => $this->name,
            'role' => $this->role,
            'iat' => $this->iat,
            'exp' => $this->exp
        ];
    }
}
