<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private RoleService $service) {}

    /**
     * Display a listing of roles.
     */
    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => $this->service->getAll(),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        return Inertia::render('Roles/Form', [
            'role' => null,
            'permissionsGrouped' => $this->service->getPermissionsGrouped(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->service->create($validated);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(int $id): Response
    {
        $role = $this->service->find($id);

        if (!$role) {
            abort(404);
        }

        return Inertia::render('Roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'isSystemRole' => $this->service->isSystemRole($role->name),
            ],
            'permissionsGrouped' => $this->service->getPermissionsGrouped(),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->service->update($id, $validated);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return redirect()
                ->route('roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('roles.index')
                ->with('error', $e->getMessage());
        }
    }
}
