<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class UserService
{
    /**
     * Get all users with their roles
     */
    public function getAll($filters = [])
    {
        $query = User::with(['roles:id,name', 'distribution:id,name']);

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Sort
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSorts = ['name', 'email', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?User
    {
        return User::with(['roles:id,name', 'distribution:id,name'])->find($id);
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        if (array_key_exists('distribution_id', $data)) {
            $userData['distribution_id'] = $data['distribution_id'];
        }

        $user = User::create($userData);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    /**
     * Update user details
     */
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (array_key_exists('distribution_id', $data)) {
            $updateData['distribution_id'] = $data['distribution_id'];
        }

        $user->update($updateData);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            throw new \Exception('You cannot delete your own account.');
        }

        return $user->delete();
    }
}
