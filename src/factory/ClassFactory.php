<?php

namespace MinhaAgenda\Factory;

use InvalidArgumentException;
use MinhaAgenda\Controller\Controller;
use MinhaAgenda\Database\BancoDadosRelacional;
use MinhaAgenda\Model\Repository\Repository;
use MinhaAgenda\Service\Service;

abstract class ClassFactory {
    public const CAMINHO_CONTROLLER = SRC_PATH . '\\controllers\\';
    public const CAMINHO_SERVICE = SRC_PATH . '\\services\\';
    public const CAMINHO_REPOSITORY = SRC_PATH . '\\repositories\\';

    public static function makeController(string $classe): Controller {
        $nomeController = substr(strrchr($classe, '\\'), 1);
        $controller = self::CAMINHO_CONTROLLER . $nomeController . 'Controller';
        if (!class_exists($controller)) {
            throw new InvalidArgumentException("Controller $controller não encontrado.");
        }

        return new $controller(self::makeService($classe));
    }

    public static function makeService(string $classe): Service {
        $nomeService = substr(strrchr($classe, '\\'), 1);
        $service = self::CAMINHO_SERVICE . $nomeService . 'Service';
        if (!class_exists($service)) {
            throw new InvalidArgumentException("Service $service não encontrado.");
        }

        return new $service(self::makeRepository($classe));
    }

    public static function makeRepository(string $classe): Repository {
        $nomeRepository = substr(strrchr($classe, '\\'), 1);
        $repository = self::CAMINHO_REPOSITORY . $nomeRepository . 'Repository';
        if (!class_exists($repository)) {
            throw new InvalidArgumentException("Repository $classe não encontrado.");
        }

        return new $repository(new BancoDadosRelacional());
    }
}
