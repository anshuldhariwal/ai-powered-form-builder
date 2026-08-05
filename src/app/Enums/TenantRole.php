<?php

namespace App\Enums;

enum TenantRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canAdministerTenant(): bool
    {
        return $this === self::Owner;
    }

    public function canManageResources(): bool
    {
        return $this === self::Owner || $this === self::Editor;
    }

    public function canViewResources(): bool
    {
        return true;
    }
}
