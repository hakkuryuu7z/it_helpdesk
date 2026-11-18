<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $currentUser = $request->user();
        $query = User::query()->with(['branch', 'department', 'roles']);

        // Scope by branch for Branch Admin
        if ($currentUser->hasRole(UserRole::BranchAdmin->value)) {
            $query->where('branch_id', $currentUser->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $branches = $currentUser->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $currentUser->branch_id)->get();

        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'branches', 'roles'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $currentUser = $request->user();

        $branches = $currentUser->hasRole(UserRole::SuperAdmin->value)
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $currentUser->branch_id)->get();

        $departments = Department::active()
            ->when(!$currentUser->hasRole(UserRole::SuperAdmin->value), fn ($q) => $q->where('branch_id', $currentUser->branch_id))
            ->orderBy('name')
            ->get();

        $roles = $currentUser->hasRole(UserRole::SuperAdmin->value)
            ? Role::orderBy('name')->get()
            : Role::where('name', UserRole::RegularUser->value)->get();

        return view('users.create', compact('branches', 'departments', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roleName = $data['role'];
        $assignedBranchIds = $data['assigned_branches'] ?? [];

        unset($data['role'], $data['assigned_branches']);
        $data['password'] = Hash::make($data['password']);

        DB::transaction(function () use ($data, $roleName, $assignedBranchIds) {
            $user = User::create($data);
            $user->syncRoles([$roleName]);

            if ($roleName === UserRole::BranchEdp->value && !empty($assignedBranchIds)) {
                $user->assignedBranches()->sync($assignedBranchIds);
            }
        });

        return redirect()->route('users.index')
            ->with('success', "User '{$data['name']}' successfully registered.");
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['branch', 'department', 'roles', 'assignedBranches']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $currentUser = auth()->user();

        $branches = $currentUser->hasRole(UserRole::SuperAdmin->value)
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $currentUser->branch_id)->get();

        $departments = Department::active()
            ->when(!$currentUser->hasRole(UserRole::SuperAdmin->value), fn ($q) => $q->where('branch_id', $currentUser->branch_id))
            ->orderBy('name')
            ->get();

        $roles = $currentUser->hasRole(UserRole::SuperAdmin->value)
            ? Role::orderBy('name')->get()
            : Role::where('name', UserRole::RegularUser->value)->get();

        $user->load(['roles', 'assignedBranches']);

        return view('users.edit', compact('user', 'branches', 'departments', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleName = $data['role'] ?? null;
        $assignedBranchIds = $data['assigned_branches'] ?? [];

        unset($data['role'], $data['assigned_branches']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $roleName, $assignedBranchIds) {
            $user->update($data);

            if ($roleName && auth()->user()->hasRole(UserRole::SuperAdmin->value)) {
                $user->syncRoles([$roleName]);

                if ($roleName === UserRole::BranchEdp->value) {
                    $user->assignedBranches()->sync($assignedBranchIds);
                } else {
                    $user->assignedBranches()->detach();
                }
            }
        });

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' successfully updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User successfully removed.');
    }
}
