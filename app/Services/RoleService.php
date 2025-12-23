<?php

namespace App\Services;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

class RoleService
{
    /**
     * System roles that cannot be deleted
     */
    protected array $systemRoles = ['superadmin', 'admin', 'customer'];

    /**
     * Get all roles with permissions count
     */
    public function getAll($distributionId = null): Collection
    {
        $query = Role::withCount('permissions')
            ->with(['permissions:id,name', 'distribution:id,name,code']);

        if ($distributionId) {
            // Show global roles AND roles for this distribution
            $query->where(function($q) use ($distributionId) {
                $q->where('distribution_id', $distributionId)
                  ->orWhere(function($sub) {
                      $sub->whereNull('distribution_id')
                          ->whereIn('name', array_diff($this->systemRoles, ['superadmin']));
                  });
            });
        }
        // If no distribution (Global Admin View), show ALL roles? 
        // Or maybe just show Global roles? 
        // Usually "All Distributions" means you can see everything. 
        // But the requirement says "if null distribution then it for all".
        // Let's assume Global Admin sees ALL.

        return $query->orderBy('name')->get();
    }

    /**
     * Get a single role with permissions
     */
    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * Get all available permissions grouped by module
     */
    public function getPermissionsGrouped(): array
    {
        $permissions = Permission::orderBy('name')->get();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';
            $action = $parts[1] ?? $permission->name;

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $action,
            ];
        }

        return $grouped;
    }

    /**
     * Create a new role with permissions
     */
    public function create(array $data): Role
    {
        $roleData = [
            'name' => $data['name'],
            'guard_name' => 'web',
        ];

        if (array_key_exists('distribution_id', $data)) {
            $roleData['distribution_id'] = $data['distribution_id'];
        }

        $role = Role::create($roleData);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role;
    }

    /**
     * Update a role's permissions
     */
    public function update(int $id, array $data): Role
    {
        $role = Role::findOrFail($id);

        // Prevent renaming system roles
        if (!in_array($role->name, $this->systemRoles) && isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh('permissions');
    }

    /**
     * Delete a role if not a system role
     */
    public function delete(int $id): bool
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, $this->systemRoles)) {
            throw new \Exception("Cannot delete system role: {$role->name}");
        }

        return $role->delete();
    }

    /**
     * Check if a role is a system role
     */
    public function isSystemRole(string $name): bool
    {
        return in_array($name, $this->systemRoles);
    }
}
