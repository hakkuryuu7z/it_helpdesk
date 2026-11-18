<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $user = $request->user();
        $query = Department::query()->with('branch')->withCount('users');

        // Scope by branch access
        if ($user->hasRole(UserRole::BranchEdp->value)) {
            $assignedBranchIds = $user->assignedBranches->pluck('id')->toArray();
            $query->whereIn('branch_id', $assignedBranchIds);
        } elseif ($user->hasRole(UserRole::BranchAdmin->value)) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        $availableBranches = $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $user->branch_id)->get();

        return view('departments.index', compact('departments', 'availableBranches'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Department::class);

        $user = $request->user();
        $branches = $user->hasRole(UserRole::SuperAdmin->value)
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $user->branch_id)->get();

        return view('departments.create', compact('branches'));
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        return redirect()->route('departments.index')
            ->with('success', "Department '{$department->name}' successfully created.");
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        $branches = auth()->user()->hasRole(UserRole::SuperAdmin->value)
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', auth()->user()->branch_id)->get();

        return view('departments.edit', compact('department', 'branches'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')
            ->with('success', "Department '{$department->name}' successfully updated.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            return back()->with('error', 'Cannot delete department because active users are associated with it.');
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department successfully removed.');
    }
}
