#!/bin/bash

# Script de inicialização para o contêiner da API
# Este script aguarda o MySQL estar disponível, executa migrações e inicia o Apache

echo "=== Iniciando API Minha Agenda ==="

# Aguarda o MySQL estar disponível
echo "🔄 Aguardando MySQL estar disponível..."
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    echo "   ⏳ MySQL ainda não está pronto, aguardando..."
    sleep 2
done
echo "✅ MySQL está disponível!"

# Executa as migrações do banco de dados
echo "🔄 Executando migrações do banco de dados..."
if vendor/bin/phinx migrate; then
    echo "✅ Migrações executadas com sucesso!"
else
    echo "❌ Erro ao executar migrações!"
    exit 1
fi

# Executa seeds se existirem (opcional)
echo "🔄 Verificando se há seeds para executar..."
if [ -n "$(ls -A db/seeds 2>/dev/null)" ]; then
    echo "📊 Executando seeds..."
    vendor/bin/phinx seed:run
    echo "✅ Seeds executados com sucesso!"
else
    echo "ℹ️  Nenhum seed encontrado, continuando..."
fi

# Inicia o servidor Apache
echo "🚀 Iniciando servidor Apache..."
echo "🌐 API estará disponível em http://localhost:8080"
echo "========================="

# Executa o Apache em primeiro plano
exec apache2-foreground
