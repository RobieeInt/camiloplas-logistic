<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function paginate(string $search = '', int $perPage = 10, int $page = 1)
    {
        $offset = ($page - 1) * $perPage;

        $searchQuery = '';
        $bindings = [];

        if (!empty($search)) {
            $searchQuery = "
                AND (
                    users.name LIKE ?
                    OR users.email LIKE ?
                )
            ";

            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        // total data
        $countQuery = "
            SELECT COUNT(DISTINCT users.id) as total
            FROM users
            LEFT JOIN model_has_roles
                ON users.id = model_has_roles.model_id
                AND model_has_roles.model_type = 'App\\\\Models\\\\User'
            LEFT JOIN roles
                ON model_has_roles.role_id = roles.id
            WHERE 1=1
            {$searchQuery}
        ";

        $countResult = DB::select($countQuery, $bindings);

        $total = $countResult[0]->total ?? 0;

        // data
        $query = "
            SELECT
                users.id,
                users.name,
                users.email,
                users.created_at,
                GROUP_CONCAT(roles.name) as role_names
            FROM users
            LEFT JOIN model_has_roles
                ON users.id = model_has_roles.model_id
                AND model_has_roles.model_type = 'App\\\\Models\\\\User'
            LEFT JOIN roles
                ON model_has_roles.role_id = roles.id
            WHERE 1=1
            {$searchQuery}
            GROUP BY
                users.id,
                users.name,
                users.email,
                users.created_at
            ORDER BY users.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $users = DB::select($query, $bindings);

        return new LengthAwarePaginator(
            $users,
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
        $query = "
            SELECT
                id,
                name,
                email
            FROM users
            WHERE id = ?
            LIMIT 1
        ";

        $result = DB::select($query, [$id]);

        return $result[0] ?? null;
    }

    public function create(array $data): int
    {
        DB::insert("
            INSERT INTO users (
                name,
                email,
                password,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, NOW(), NOW())
        ", [
            $data['name'],
            $data['email'],
            Hash::make($data['password']),
        ]);

        return DB::getPdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (!empty($data['password'])) {

            DB::update("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    password = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $data['name'],
                $data['email'],
                Hash::make($data['password']),
                $id,
            ]);

        } else {

            DB::update("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $data['name'],
                $data['email'],
                $id,
            ]);
        }
    }

    public function delete(int $id): void
    {
        DB::delete("
            DELETE FROM users
            WHERE id = ?
        ", [$id]);
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        DB::beginTransaction();

        try {

            DB::delete("
                DELETE FROM model_has_roles
                WHERE model_type = 'App\\\\Models\\\\User'
                AND model_id = ?
            ", [$userId]);

            foreach ($roleIds as $roleId) {

                DB::insert("
                    INSERT INTO model_has_roles (
                        role_id,
                        model_type,
                        model_id
                    )
                    VALUES (?, 'App\\\\Models\\\\User', ?)
                ", [
                    $roleId,
                    $userId,
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getUserRoleIds(int $userId): array
    {
        $query = "
            SELECT role_id
            FROM model_has_roles
            WHERE model_type = 'App\\\\Models\\\\User'
            AND model_id = ?
        ";

        $results = DB::select($query, [$userId]);

        return collect($results)
            ->pluck('role_id')
            ->toArray();
    }
}
