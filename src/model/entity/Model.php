<?php

namespace MinhaAgenda\Model\Entity;

use JsonSerializable;

abstract class Model implements JsonSerializable {
    protected int $id;

    public function __construct(int $id = 0) {
        $this->id = $id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): self {
        $this->id = $id;
        return $this;
    }

    public function temIdInexistente(): bool {
        return $this->id === 0;
    }

    abstract public function emArray(): array;

    public function jsonSerialize(): array {
        return $this->emArray();
    }
}

