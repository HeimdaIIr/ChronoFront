<?php

namespace App\Traits;

trait HasRolePermissions
{
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is organizer (orga)
     */
    public function isOrga(): bool
    {
        return $this->role === 'orga';
    }

    /**
     * Check if user is viewer
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if user can edit (admin or orga)
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'orga']);
    }

    /**
     * Check if user can only view
     */
    public function canOnlyView(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if user has specific role(s)
     */
    public function hasRole($roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }

        return $this->role === $roles;
    }
}
