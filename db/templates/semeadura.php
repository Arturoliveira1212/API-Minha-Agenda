<?php

declare(strict_types=1);

use $useClassName;

class $className extends $baseClassName {

    public function getDependencies(): array {
        return [];
    }

    public function run(): void {
        $sql = <<<SQL
            DELETE FROM exemplo;

            INSERT INTO exemplo ( id, nome ) VALUES
            ( NULL, 'Exemplo' )
        SQL;
        $this->execute( $sql );
    }
}