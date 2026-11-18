<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AddTicketCommentAction;
use App\Actions\Tickets\AssignTicketAction;
use App\Actions\Tickets\CreateTicketAction;
use App\Actions\Tickets\TransitionTicketStatusAction;
use App\Enums\TicketImpact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TicketUrgency;
use App\Enums\UserRole;
use App\Http\Requests\Tickets\AssignTicketRequest;
use App\Http\Requests\Tickets\StoreCommentRequest;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketStatusRequest;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        protected CreateTicketAction $createTicketAction,
        protected AssignTicketAction $assignTicketAction,
        protected TransitionTicketStatusAction $transitionStatusAction,
        protected AddTicketCommentAction $addCommentAction,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $currentUser = $request->user();

        $query = Ticket::query()
            ->forUser($currentUser)
            ->with(['branch', 'creator', 'category', 'assignedEdp'])
            ->filter($request->only(['status', 'priority', 'type', 'branch_id', 'search']));

        $tickets = $query->latest('created_at')->paginate(15)->withQueryString();

        $branches = $currentUser->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])
            ? Branch::active()->orderBy('name')->get()
            : ($currentUser->hasRole(UserRole::BranchEdp->value)
                ? $currentUser->assignedBranches
                : Branch::where('id', $currentUser->branch_id)->get());

        $categories = TicketCategory::active()->orderBy('name')->get();

        return view('tickets.index', compact('tickets', 'branches', 'categories'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        $currentUser = $request->user();

        $branches = $currentUser->hasAnyRole([UserRole::SuperAdmin->value, UserRole::CentralEdp->value])
            ? Branch::active()->orderBy('name')->get()
            : Branch::where('id', $currentUser->branch_id)->get();

        $categories = TicketCategory::active()->orderBy('name')->get();

        return view('tickets.create', compact('branches', 'categories'));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->createTicketAction->execute(
            data: $request->validated(),
            creator: $request->user(),
            attachment: $request->file('attachment')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} successfully submitted.");
    }

    public function show(Ticket $ticket, Request $request): View
    {
        $this->authorize('view', $ticket);

        $currentUser = $request->user();

        $ticket->load([
            'branch',
            'creator.department',
            'category',
            'assignedEdp',
            'attachments.uploader',
            'histories.user',
            'assignments.assignee',
            'assignments.assigner',
        ]);

        // Load comments filtered by permission to view internal notes
        $comments = $ticket->comments()
            ->visibleTo($currentUser)
            ->with(['user.roles', 'attachments'])
            ->get();

        // Eligible assignees for this ticket's branch
        $eligibleAssignees = collect();
        if ($currentUser->can('assign', $ticket)) {
            $eligibleAssignees = User::query()
                ->role([UserRole::CentralEdp->value, UserRole::BranchEdp->value])
                ->where(function ($q) use ($ticket) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', UserRole::CentralEdp->value))
                      ->orWhereHas('assignedBranches', fn ($b) => $b->where('branches.id', $ticket->branch_id));
                })
                ->active()
                ->orderBy('name')
                ->get();
        }

        return view('tickets.show', compact('ticket', 'comments', 'eligibleAssignees'));
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $assignee = User::findOrFail($request->validated('assigned_to'));

        $this->assignTicketAction->execute(
            ticket: $ticket,
            assignee: $assignee,
            actor: $request->user(),
            notes: $request->validated('notes')
        );

        return back()->with('success', "Ticket assigned to {$assignee->name}.");
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $newStatus = TicketStatus::from($request->validated('status'));

        $this->transitionStatusAction->execute(
            ticket: $ticket,
            newStatus: $newStatus,
            actor: $request->user(),
            resolutionNotes: $request->validated('resolution_notes')
        );

        return back()->with('success', "Ticket status updated to '{$newStatus->label()}'.");
    }

    public function addComment(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->addCommentAction->execute(
            ticket: $ticket,
            user: $request->user(),
            message: $request->validated('message'),
            isInternal: $request->boolean('is_internal'),
            attachment: $request->file('attachment')
        );

        return back()->with('success', 'Discussion update posted.');
    }
}
