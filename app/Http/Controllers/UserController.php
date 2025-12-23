<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rules;

use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private RoleService $roleService,
        private \App\Services\DistributionService $distributionService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'sort_field', 'sort_direction']);

        return Inertia::render('Users/Index', [
            'users' => $this->userService->getAll($filters),
            'roles' => Role::all(),
            'assignableDistributions' => $this->distributionService->getActive(),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(\App\Http\Requests\UserRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(\App\Http\Requests\UserRequest $request, int $id)
    {
        $this->userService->update($id, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id)
    {
        try {
            $this->userService->delete($id);
            return redirect()
                ->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('users.index')
                ->with('error', $e->getMessage());
        }
    }
}
