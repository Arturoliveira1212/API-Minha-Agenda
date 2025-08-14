<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UsuarioSeeder extends AbstractSeed {

    public function getDependencies(): array {
        return [];
    }

    public function run(): void {
        $sql = <<<SQL
            DELETE FROM exemplo;

            INSERT INTO exemplo ( id, descricao ) VALUES
            ( NULL, 'Exemplo' )
        SQL;
        $this->execute( $sql );
    }
}