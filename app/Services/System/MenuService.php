<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class MenuService
{
    public function paginate(string $search = '', int $perPage = 10, int $page = 1)
    {
        $offset = ($page - 1) * $perPage;

        $searchQuery = '';
        $bindings = [];

        if (!empty($search)) {
            $searchQuery = "
                AND (
                    m.name LIKE ?
                    OR m.route LIKE ?
                    OR m.permission_name LIKE ?
                    OR p.name LIKE ?
                )
            ";

            $bindings = [
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
            ];
        }

        $countQuery = "
            SELECT COUNT(*) as total
            FROM menus m
            LEFT JOIN menus p ON m.parent_id = p.id
            WHERE 1=1
            {$searchQuery}
        ";

        $countResult = DB::select($countQuery, $bindings);
        $total = $countResult[0]->total ?? 0;

        $query = "
            SELECT
                m.id,
                m.parent_id,
                p.name as parent_name,
                m.name,
                m.route,
                m.icon,
                m.permission_name,
                m.sort_order,
                m.is_active,
                m.created_at
            FROM menus m
            LEFT JOIN menus p ON m.parent_id = p.id
            WHERE 1=1
            {$searchQuery}
            ORDER BY
                COALESCE(m.parent_id, m.id) ASC,
                m.parent_id ASC,
                m.sort_order ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $menus = DB::select($query, $bindings);

        return new LengthAwarePaginator(
            $menus,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function find(int $id): ?object
    {
        $result = DB::select("
            SELECT
                id,
                parent_id,
                name,
                route,
                icon,
                permission_name,
                sort_order,
                is_active
            FROM menus
            WHERE id = ?
            LIMIT 1
        ", [$id]);

        return $result[0] ?? null;
    }

    public function parents(?int $exceptId = null): array
    {
        $exceptQuery = '';
        $bindings = [];

        if ($exceptId) {
            $exceptQuery = "AND id != ?";
            $bindings[] = $exceptId;
        }

        return DB::select("
            SELECT
                id,
                name
            FROM menus
            WHERE parent_id IS NULL
            {$exceptQuery}
            ORDER BY sort_order ASC, name ASC
        ", $bindings);
    }

    public function create(array $data): int
    {
        DB::insert("
            INSERT INTO menus (
                parent_id,
                name,
                route,
                icon,
                permission_name,
                sort_order,
                is_active,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            $data['parent_id'] ?: null,
            $data['name'],
            $data['route'] ?: null,
            $data['icon'] ?: null,
            $data['permission_name'] ?: null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ? 1 : 0,
        ]);

        return DB::getPdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        DB::update("
            UPDATE menus
            SET
                parent_id = ?,
                name = ?,
                route = ?,
                icon = ?,
                permission_name = ?,
                sort_order = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $data['parent_id'] ?: null,
            $data['name'],
            $data['route'] ?: null,
            $data['icon'] ?: null,
            $data['permission_name'] ?: null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ? 1 : 0,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        DB::beginTransaction();

        try {
            DB::update("
                UPDATE menus
                SET parent_id = NULL
                WHERE parent_id = ?
            ", [$id]);

            DB::delete("
                DELETE FROM menus
                WHERE id = ?
            ", [$id]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getSidebarMenus(int $userId): array
    {
        $permissions = DB::select("
            SELECT DISTINCT permissions.name
            FROM permissions
            INNER JOIN role_has_permissions
                ON permissions.id = role_has_permissions.permission_id
            INNER JOIN model_has_roles
                ON role_has_permissions.role_id = model_has_roles.role_id
            WHERE model_has_roles.model_type = 'App\\\\Models\\\\User'
            AND model_has_roles.model_id = ?
        ", [$userId]);

        $permissionNames = collect($permissions)->pluck('name')->toArray();

        $parents = DB::select("
            SELECT *
            FROM menus
            WHERE parent_id IS NULL
            AND is_active = 1
            ORDER BY sort_order ASC
        ");

        foreach ($parents as $parent) {
            $bindings = [$parent->id];

            $permissionQuery = '';

            if (!empty($permissionNames)) {
                $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));

                $permissionQuery = "
                    AND (
                        permission_name IS NULL
                        OR permission_name = ''
                        OR permission_name IN ({$placeholders})
                    )
                ";

                $bindings = array_merge($bindings, $permissionNames);
            } else {
                $permissionQuery = "
                    AND (
                        permission_name IS NULL
                        OR permission_name = ''
                    )
                ";
            }

            $parent->children = DB::select("
                SELECT *
                FROM menus
                WHERE parent_id = ?
                AND is_active = 1
                {$permissionQuery}
                ORDER BY sort_order ASC
            ", $bindings);
        }

        return collect($parents)
            ->filter(function ($parent) use ($permissionNames) {
                $parentAllowed =
                    empty($parent->permission_name)
                    || in_array($parent->permission_name, $permissionNames);

                return $parentAllowed || count($parent->children) > 0;
            })
            ->values()
            ->toArray();
    }

    public function hasChildren(int $id): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as total
            FROM menus
            WHERE parent_id = ?
        ", [$id]);

        return ($result[0]->total ?? 0) > 0;
    }
}
