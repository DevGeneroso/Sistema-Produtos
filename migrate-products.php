<?php
/**
 * Script para migrar o banco de dados existente para a nova estrutura
 * Adiciona a coluna user_id à tabela products e associa produtos existentes ao usuário admin
 */

require_once 'config/database.php';

try {
    // Verificar se a coluna user_id já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'user_id'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "Adicionando coluna user_id à tabela products...\n";
        
        // Adicionar coluna user_id
        $pdo->exec("ALTER TABLE products ADD COLUMN user_id INT NOT NULL DEFAULT 1");
        
        // Adicionar chave estrangeira
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT fk_products_users 
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        
        // Atualizar produtos existentes para associá-los ao usuário admin (id=1)
        $pdo->exec("UPDATE products SET user_id = 1");
        
        // Adicionar índice único para código por usuário
        $pdo->exec("ALTER TABLE products ADD UNIQUE INDEX unique_code_per_user (code, user_id)");
        
        echo "Migração concluída com sucesso!\n";
    } else {
        echo "A coluna user_id já existe na tabela products. Nenhuma ação necessária.\n";
    }
    
} catch (PDOException $e) {
    echo "Erro durante a migração: " . $e->getMessage() . "\n";
}
