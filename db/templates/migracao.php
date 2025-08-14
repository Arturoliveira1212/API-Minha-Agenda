<?php

declare(strict_types=1);

use $useClassName;

final class $className extends $baseClassName {

    public function up(): void {
        $sql = <<<'SQL'
            CREATE TABLE pedidos (
                id INT PRIMARY KEY AUTO_INCREMENT,
                idAdministrador INT,
                descricao VARCHAR(255),
                valor DECIMAL(10, 2),
                CONSTRAINT fk__id_administrador FOREIGN KEY (idAdministrador) REFERENCES administrador(id)
                    ON DELETE CASCADE ON UPDATE NO ACTION
            ) ENGINE=INNODB;
        SQL;
        $this->execute( $sql );
    }

    public function down(): void {
        $this->execute( 'DROP TABLE pedidos' );
    }
}