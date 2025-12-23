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
    public function index(Request $request): Response
    {
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('Roles/Index', [
            'roles' => $this->service->getAll($distributionId),
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
            'distributions' => \App\Models\Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(\App\Http\Requests\RoleRequest $request)
    {
        $data = $request->validated();
        
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($userDistributionId === 'all') $userDistributionId = null;
        
        // If user is scoped to a distribution, force it.
        // If user is global (null), allow them to choose (from request).
        // If request has no distribution_id (Global chosen), it works.
        
        if ($userDistributionId) {
            $data['distribution_id'] = $userDistributionId;
        } else {
            // Use the input from form (can be null for Global, or a specific ID)
            $data['distribution_id'] = $request->input('distribution_id'); 
        }

        $this->service->create($data);

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
                'distribution_id' => $role->distribution_id, // Pass existing distribution_id
            ],
            'permissionsGrouped' => $this->service->getPermissionsGrouped(),
            'distributions' => \App\Models\Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(\App\Http\Requests\RoleRequest $request, int $id)
    {
        $this->service->update($id, $request->validated());

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
