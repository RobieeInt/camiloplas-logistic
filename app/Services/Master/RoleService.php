<?php

namespace App\Services\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    public function paginate(string $search = '', int $perPage = 10, int $page = 1)
    {
        $offset = ($page - 1) * $perPage;

        $searchQuery = '';
        $bindings = [];

        if (!empty($search)) {
            $searchQuery = "AND roles.name LIKE ?";
            $bindings[] = "%{$search}%";
        }

        $countQuery = "
            SELECT COUNT(*) as total
            FROM roles
            WHERE guard_name = 'web'
            {$searchQuery}
        ";

        $countResult = DB::select($countQuery, $bindings);
        $total = $countResult[0]->total ?? 0;

        $query = "
            SELECT
                roles.id,
                roles.name,
                roles.guard_name,
                roles.created_at,
                COUNT(role_has_permissions.permission_id) as permission_count
            FROM roles
            LEFT JOIN role_has_permissions
                ON roles.id = role_has_permissions.role_id
            WHERE roles.guard_name = 'web'
            {$searchQuery}
            GROUP BY
                roles.id,
                roles.name,
                roles.guard_name,
                roles.created_at
            ORDER BY roles.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $roles = DB::select($query, $bindings);

        return new LengthAwarePaginator(
            $roles,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function all(): array
    {
        return DB::select("
            SELECT
                id,
                name
            FROM roles
            WHERE guard_name = 'web'
            ORDER BY name ASC
        ");
    }

    public function find(int $id): ?object
    {
        $result = DB::select("
            SELECT
                id,
                name,
                guard_name
            FROM roles
            WHERE id = ?
            LIMIT 1
        ", [$id]);

        return $result[0] ?? null;
    }

    public function create(string $name): int
    {
        DB::insert("
            INSERT INTO roles (
                name,
                guard_name,
                created_at,
                updated_at
            )
            VALUES (?, 'web', NOW(), NOW())
        ", [$name]);

        return DB::getPdo()->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        DB::update("
            UPDATE roles
            SET
                name = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [$name, $id]);
    }

    public function delete(int $id): void
    {
        DB::beginTransaction();

        try {
            DB::delete("
                DELETE FROM role_has_permissions
                WHERE role_id = ?
            ", [$id]);

            DB::delete("
                DELETE FROM model_has_roles
                WHERE role_id = ?
            ", [$id]);

            DB::delete("
                DELETE FROM roles
                WHERE id = ?
            ", [$id]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        DB::beginTransaction();

        try {
            DB::delete("
                DELETE FROM role_has_permissions
                WHERE role_id = ?
            ", [$roleId]);

            foreach ($permissionIds as $permissionId) {
                DB::insert("
                    INSERT INTO role_has_permissions (
                        permission_id,
                        role_id
                    )
                    VALUES (?, ?)
                ", [
                    $permissionId,
                    $roleId,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getRolePermissionIds(int $roleId): array
    {
        $results = DB::select("
            SELECT permission_id
            FROM role_has_permissions
            WHERE role_id = ?
        ", [$roleId]);

        return collect($results)
            ->pluck('permission_id')
            ->toArray();
    }

    public function roleIsUsed(int $id): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as total
            FROM model_has_roles
            WHERE role_id = ?
        ", [$id]);

        return ($result[0]->total ?? 0) > 0;
    }
}
