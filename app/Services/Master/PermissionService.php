<?php

namespace App\Services\Master;

use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function all(): array
    {
        return DB::select("
            SELECT
                id,
                name,
                guard_name
            FROM permissions
            WHERE guard_name = 'web'
            ORDER BY name ASC
        ");
    }
}
