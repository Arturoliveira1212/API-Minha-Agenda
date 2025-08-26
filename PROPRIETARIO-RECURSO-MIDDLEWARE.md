# Middleware de Proprietário de Recurso

O `ProprietarioRecursoMiddleware` é um middleware que garante que usuários só possam modificar recursos que pertencem a eles, implementando um controle de acesso granular.

## Como Funciona

1. **Verificação de Método**: Só verifica os métodos HTTP especificados (por padrão: PUT, PATCH, DELETE)
2. **Bypass para Admins**: Administradores e super-administradores podem modificar qualquer recurso
3. **Verificação de Propriedade**: Para usuários normais, verifica se eles são donos do recurso

## Uso Básico

### Para Usuários

```php
// Usuários só podem modificar seus próprios dados
$group->put('/{id}', UsuarioController::class . ':atualizar')
    ->add(MiddlewareFactory::apenasProprioRecurso('usuario', 'id', ['PUT']));

$group->delete('/{id}', UsuarioController::class . ':excluirComId')
    ->add(MiddlewareFactory::apenasProprioRecurso('usuario', 'id', ['DELETE']));
```

### Para Outros Recursos (Tarefas, Eventos, Agendamentos)

```php
// Para tarefas - usuários só podem modificar suas próprias tarefas
$group->put('/{id}', TarefaController::class . ':atualizar')
    ->add(MiddlewareFactory::apenasProprioRecurso('tarefa', 'id', ['PUT']));

// Para eventos
$group->delete('/{id}', EventoController::class . ':excluir')
    ->add(MiddlewareFactory::apenasProprioRecurso('evento', 'id', ['DELETE']));

// Para agendamentos
$group->patch('/{id}', AgendamentoController::class . ':atualizarParcial')
    ->add(MiddlewareFactory::apenasProprioRecurso('agendamento', 'id', ['PATCH']));
```

## Parâmetros do Middleware

```php
MiddlewareFactory::apenasProprioRecurso(
    $tipoRecurso,        // Tipo do recurso: 'usuario', 'tarefa', 'evento', etc.
    $parametroId,        // Nome do parâmetro na rota (padrão: 'id')
    $metodosPermitidos   // Array dos métodos HTTP a verificar (padrão: ['PUT', 'PATCH', 'DELETE'])
)
```

## Configuração de Recursos

O middleware automaticamente mapeia os tipos de recurso para suas respectivas tabelas:

- `usuario` → Verificação direta (usuário só pode modificar a si mesmo)
- `tarefa` → Tabela `tarefas`, coluna `usuario_id`
- `evento` → Tabela `eventos`, coluna `usuario_id`  
- `agendamento` → Tabela `agendamentos`, coluna `usuario_id`

## Cenários de Uso

### Cenário 1: Usuário tentando modificar seus próprios dados
- ✅ **Permitido**: Se o ID na URL corresponde ao ID do usuário autenticado

### Cenário 2: Usuário tentando modificar dados de outro usuário
- ❌ **Negado**: Retorna HTTP 403 (Forbidden)

### Cenário 3: Admin modificando qualquer recurso
- ✅ **Permitido**: Administradores têm acesso total

### Cenário 4: Usuário modificando sua própria tarefa
- ✅ **Permitido**: Se a consulta no banco confirma que a tarefa pertence ao usuário

### Cenário 5: Usuário tentando modificar tarefa de outro usuário
- ❌ **Negado**: Retorna HTTP 403 (Forbidden)

## Estrutura de Banco Esperada

Para que o middleware funcione corretamente com recursos além de usuários, suas tabelas devem ter:

```sql
-- Exemplo para tabela de tarefas
CREATE TABLE tarefas (
    id INT PRIMARY KEY,
    titulo VARCHAR(255),
    descricao TEXT,
    usuario_id INT,  -- Coluna que identifica o proprietário
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Exemplo para tabela de eventos  
CREATE TABLE eventos (
    id INT PRIMARY KEY,
    nome VARCHAR(255),
    data_evento DATE,
    usuario_id INT,  -- Coluna que identifica o proprietário
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

## Exemplo Completo de Implementação

```php
// routes/tarefas.php
$group->group('/tarefas', function ($group): void {
    // Criar nova tarefa - qualquer usuário pode criar
    $group->post('/', TarefaController::class . ':criar');
    
    // Listar tarefas - pode implementar filtro por usuário no controller
    $group->get('', TarefaController::class . ':listar');
    
    // Ver tarefa específica
    $group->get('/{id}', TarefaController::class . ':obter');
    
    // Modificar tarefa - só o proprietário pode
    $group->put('/{id}', TarefaController::class . ':atualizar')
        ->add(MiddlewareFactory::apenasProprioRecurso('tarefa', 'id', ['PUT']));
    
    // Atualizar parcialmente - só o proprietário pode  
    $group->patch('/{id}', TarefaController::class . ':atualizarParcial')
        ->add(MiddlewareFactory::apenasProprioRecurso('tarefa', 'id', ['PATCH']));
    
    // Excluir tarefa - só o proprietário pode
    $group->delete('/{id}', TarefaController::class . ':excluir')
        ->add(MiddlewareFactory::apenasProprioRecurso('tarefa', 'id', ['DELETE']));
})
    ->add(MiddlewareFactory::papel('usuario'))
    ->add(MiddlewareFactory::autenticacao());
```

## Vantagens

1. **Segurança**: Impede que usuários modifiquem recursos de outros usuários
2. **Flexibilidade**: Configurável para diferentes tipos de recursos
3. **Transparente**: Administradores mantêm acesso total
4. **Performance**: Só faz consulta ao banco quando necessário
5. **Reutilizável**: Mesmo middleware serve para diferentes recursos

## Notas Importantes

- O middleware depende dos traits `RespostaAPI` e `UsuarioAutenticado`
- Requer que o sistema de autenticação esteja configurado corretamente
- Para novos tipos de recurso, adicione a configuração no método `verificarProprietarioNoBanco`
- Em caso de erro na consulta ao banco, o acesso é negado por segurança
