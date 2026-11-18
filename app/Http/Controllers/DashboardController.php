<?php

namespace App\Http\Controllers;

use App\Enums\FeatureRequestStage;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Compute and render role-differentiated dashboard metrics and queues.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::RegularUser->value)) {
            return $this->regularUserDashboard($user);
        }

        if ($user->hasRole(UserRole::BranchEdp->value)) {
            return $this->branchEdpDashboard($user);
        }

        if ($user->hasRole(UserRole::CentralEdp->value)) {
            return $this->centralEdpDashboard($user);
        }

        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return $this->superAdminDashboard($user);
        }

        // Branch Admin or other role fallback
        return $this->branchAdminDashboard($user);
    }

    private function regularUserDashboard(User $user): View
    {
        $myTickets = Ticket::where('created_by', $user->id);

        $metrics = [
            'total_submitted' => (clone $myTickets)->count(),
            'open_tickets' => (clone $myTickets)->whereIn('status', [TicketStatus::Open, TicketStatus::UnderReview, TicketStatus::InProgress])->count(),
            'waiting_user' => (clone $myTickets)->where('status', TicketStatus::WaitingUser)->count(),
            'resolved' => (clone $myTickets)->where('status', TicketStatus::Resolved)->count(),
        ];

        $recentTickets = (clone $myTickets)
            ->with(['branch', 'category', 'assignedEdp'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('dashboard.user', compact('metrics', 'recentTickets'));
    }

    private function branchEdpDashboard(User $user): View
    {
        $assignedBranchIds = $user->assignedBranches()->pluck('branches.id')->toArray();

        $branchQuery = Ticket::whereIn('branch_id', $assignedBranchIds);

        $metrics = [
            'total_branch_tickets' => (clone $branchQuery)->count(),
            'assigned_to_me' => (clone $branchQuery)->where('assigned_to', $user->id)->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress, TicketStatus::WaitingUser])->count(),
            'unassigned_branch' => (clone $branchQuery)->whereNull('assigned_to')->whereIn('status', [TicketStatus::Open, TicketStatus::UnderReview])->count(),
            'critical_queue' => (clone $branchQuery)->where('priority', TicketPriority::Critical)->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])->count(),
        ];

        $myAssignedTickets = (clone $branchQuery)
            ->where('assigned_to', $user->id)
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress, TicketStatus::WaitingUser])
            ->with(['creator', 'category', 'branch'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->take(8)
            ->get();

        $unassignedTickets = (clone $branchQuery)
            ->whereNull('assigned_to')
            ->whereIn('status', [TicketStatus::Open, TicketStatus::UnderReview])
            ->with(['creator', 'category', 'branch'])
            ->orderBy('created_at', 'asc')
            ->take(8)
            ->get();

        return view('dashboard.branch_edp', compact('metrics', 'myAssignedTickets', 'unassignedTickets'));
    }

    private function centralEdpDashboard(User $user): View
    {
        $metrics = [
            'total_system_tickets' => Ticket::count(),
            'unassigned_tickets' => Ticket::whereNull('assigned_to')->whereIn('status', [TicketStatus::Open, TicketStatus::UnderReview])->count(),
            'critical_incidents' => Ticket::where('priority', TicketPriority::Critical)->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])->count(),
            'feature_requests_active' => FeatureRequest::whereNotIn('stage', [FeatureRequestStage::Completed, FeatureRequestStage::Rejected])->count(),
        ];

        $ticketsByBranch = Branch::withCount('tickets')
            ->where('is_active', true)
            ->orderBy('tickets_count', 'desc')
            ->take(10)
            ->get();

        $unassignedQueue = Ticket::whereNull('assigned_to')
            ->whereIn('status', [TicketStatus::Open, TicketStatus::UnderReview])
            ->with(['branch', 'creator', 'category'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->take(8)
            ->get();

        $activeFeatureRequests = FeatureRequest::whereNotIn('stage', [FeatureRequestStage::Completed, FeatureRequestStage::Rejected])
            ->with(['ticket.branch', 'ticket.creator'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('dashboard.central_edp', compact('metrics', 'ticketsByBranch', 'unassignedQueue', 'activeFeatureRequests'));
    }

    private function superAdminDashboard(User $user): View
    {
        $metrics = [
            'total_users' => User::count(),
            'total_branches' => Branch::count(),
            'total_tickets' => Ticket::count(),
            'active_feature_requests' => FeatureRequest::whereNotIn('stage', [FeatureRequestStage::Completed, FeatureRequestStage::Rejected])->count(),
        ];

        $ticketsByStatus = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recentAudits = TicketHistory::with(['ticket', 'actor'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $ticketsByBranch = Branch::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.super_admin', compact('metrics', 'ticketsByStatus', 'recentAudits', 'ticketsByBranch'));
    }

    private function branchAdminDashboard(User $user): View
    {
        $branchQuery = Ticket::where('branch_id', $user->branch_id);

        $metrics = [
            'total_branch_tickets' => (clone $branchQuery)->count(),
            'open_tickets' => (clone $branchQuery)->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])->count(),
            'resolved_tickets' => (clone $branchQuery)->where('status', TicketStatus::Resolved)->count(),
            'closed_tickets' => (clone $branchQuery)->where('status', TicketStatus::Closed)->count(),
        ];

        $recentTickets = (clone $branchQuery)
            ->with(['creator', 'category', 'assignedEdp'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('dashboard.branch_admin', compact('metrics', 'recentTickets'));
    }
}
