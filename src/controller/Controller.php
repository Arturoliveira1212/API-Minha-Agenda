<?php

namespace MinhaAgenda\Controller;

use MinhaAgenda\Trait\RespostaAPI;
use MinhaAgenda\Service\Service;

abstract class Controller {
    protected readonly Service $service;

    public function __construct(Service $service) {
        $this->service = $service;
    }

    use RespostaAPI;
}

