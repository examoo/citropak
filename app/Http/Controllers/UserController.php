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

        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('Users/Index', [
            'users' => $this->userService->getAll($filters),
            'roles' => $this->roleService->getAll($distributionId),
            'assignableDistributions' => $this->distributionService->getActive(),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(\App\Http\Requests\UserRequest $request)
    {
        $data = $request->validated();
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        // Force distribution if scoped
        if ($distributionId) {
            $data['distribution_id'] = $distributionId;
        }

        $this->userService->create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(\App\Http\Requests\UserRequest $request, int $id)
    {
        $data = $request->validated();
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        // If scoped, ensure they can't change distribution (or it stays set to current)
        // Actually, preventing change to another distribution is good.
        // But if they are editing an existing user, we might want to keep that user's distribution?
        // User said "same for user you create". Update might be different.
        // If I am Scoped Admin, I should only see my users.
        // If I edit my user, I shouldn't be able to move them to another distribution.
        // So forcing current distribution is safe for Scoped Admin.
        // For Global Admin ($distributionId is null), they can change it via input (if field exists).
        
        if ($distributionId) {
            $data['distribution_id'] = $distributionId;
        }

        $this->userService->update($id, $data);

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
