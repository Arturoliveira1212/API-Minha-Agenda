<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CriaTabelaUsuario extends AbstractMigration {

    public function up(): void {
        $sql = <<<'SQL'
            CREATE TABLE exemplo (
                id INT PRIMARY KEY AUTO_INCREMENT,
                idAdministrador INT,
                descricao VARCHAR(255),
                valor DECIMAL(10, 2)
            ) ENGINE=INNODB;
        SQL;
        $this->execute( $sql );
    }

    public function down(): void {
        $this->execute( 'DROP TABLE exemplo' );
    }
}