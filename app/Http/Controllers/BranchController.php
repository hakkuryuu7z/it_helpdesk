<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Branches\StoreBranchRequest;
use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Branch::class);

        $user = $request->user();
        $query = Branch::query()->withCount(['departments', 'users']);

        // Scope branches for Branch EDP & Branch Admin
        if ($user->hasRole(UserRole::BranchEdp->value)) {
            $assignedBranchIds = $user->assignedBranches->pluck('id')->toArray();
            $query->whereIn('id', $assignedBranchIds);
        } elseif ($user->hasRole(UserRole::BranchAdmin->value)) {
            $query->where('id', $user->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        $branches = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = Branch::create($request->validated());

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' successfully created.");
    }

    public function show(Branch $branch): View
    {
        $this->authorize('view', $branch);

        $branch->load(['departments', 'assignedEdps']);
        $usersCount = $branch->users()->count();

        return view('branches.show', compact('branch', 'usersCount'));
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' successfully updated.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        if ($branch->users()->exists() || $branch->departments()->exists()) {
            return back()->with('error', 'Cannot delete branch because active departments or users are associated with it.');
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Branch successfully removed.');
    }
}
