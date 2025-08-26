<?php

namespace MinhaAgenda\Trait;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait para facilitar o acesso aos dados do usuário autenticado
 */
trait UsuarioAutenticado
{
    /**
     * Obtém os dados do usuário autenticado do request
     */
    protected function getUsuarioAutenticado(ServerRequestInterface $request): ?array
    {
        return $request->getAttribute('usuario_autenticado');
    }

    /**
     * Obtém o ID do usuário autenticado
     */
    protected function getUsuarioId(ServerRequestInterface $request): ?int
    {
        $usuario = $this->getUsuarioAutenticado($request);
        return $usuario['id'] ?? null;
    }

    /**
     * Obtém o papel/role do usuário autenticado
     */
    protected function getUsuarioPapel(ServerRequestInterface $request): ?string
    {
        $usuario = $this->getUsuarioAutenticado($request);
        return $usuario['papel'] ?? null;
    }

    /**
     * Obtém o nome do usuário autenticado
     */
    protected function getUsuarioNome(ServerRequestInterface $request): ?string
    {
        $usuario = $this->getUsuarioAutenticado($request);
        return $usuario['nome'] ?? null;
    }

    /**
     * Verifica se o usuário é admin
     */
    protected function isAdmin(ServerRequestInterface $request): bool
    {
        $papel = $this->getUsuarioPapel($request);
        return in_array($papel, ['admin', 'super_admin']);
    }

    /**
     * Verifica se o usuário é super admin
     */
    protected function isSuperAdmin(ServerRequestInterface $request): bool
    {
        $papel = $this->getUsuarioPapel($request);
        return $papel === 'super_admin';
    }

    /**
     * Verifica se o usuário pode acessar o recurso com base no ID
     * (usuários comuns só podem acessar seus próprios dados)
     */
    protected function podeAcessarRecurso(ServerRequestInterface $request, int $recursoUsuarioId): bool
    {
        $usuario = $this->getUsuarioAutenticado($request);
        
        if (!$usuario) {
            return false;
        }

        // Super admin pode acessar tudo
        if ($usuario['papel'] === 'super_admin') {
            return true;
        }

        // Admin pode acessar dados de usuários comuns
        if ($usuario['papel'] === 'admin') {
            return true;
        }

        // Usuário comum só pode acessar seus próprios dados
        return $usuario['id'] === $recursoUsuarioId;
    }
}
