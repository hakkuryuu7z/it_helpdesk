<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Compute and display analytics and reporting metrics.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Ticket::with(['branch', 'category', 'creator', 'assignedEdp'])
            ->forUser($user);

        $this->applyFilters($query, $request);

        // Cloned queries for aggregations
        $totalCount = (clone $query)->count();
        $resolvedCount = (clone $query)->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])->count();

        // Calculate Average Resolution Time in hours for resolved tickets using PostgreSQL EPOCH extraction
        $avgResolutionHours = (clone $query)
            ->whereNotNull('resolved_at')
            ->select(DB::raw('ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600)::numeric, 1) as avg_hours'))
            ->value('avg_hours') ?? 0;

        // Tickets by Branch
        $ticketsByBranch = (clone $query)
            ->join('branches', 'tickets.branch_id', '=', 'branches.id')
            ->select('branches.code', 'branches.name', DB::raw('count(tickets.id) as count'))
            ->groupBy('branches.code', 'branches.name')
            ->orderBy('count', 'desc')
            ->get();

        // Tickets by Category
        $ticketsByCategory = (clone $query)
            ->join('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
            ->select('ticket_categories.name', DB::raw('count(tickets.id) as count'))
            ->groupBy('ticket_categories.name')
            ->orderBy('count', 'desc')
            ->get();

        // Tickets by Priority
        $ticketsByPriority = (clone $query)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Paginated records for table view
        $tickets = (clone $query)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = TicketCategory::where('is_active', true)->orderBy('name')->get();

        return view('reports.index', compact(
            'totalCount',
            'resolvedCount',
            'avgResolutionHours',
            'ticketsByBranch',
            'ticketsByCategory',
            'ticketsByPriority',
            'tickets',
            'branches',
            'categories'
        ));
    }

    /**
     * Stream CSV export enforcing strict backend authorization boundaries.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $query = Ticket::with(['branch', 'category', 'creator', 'assignedEdp'])
            ->forUser($user);

        $this->applyFilters($query, $request);

        $filename = 'it_helpdesk_report_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'Ticket Number',
                'Branch Code',
                'Branch Name',
                'Type',
                'Category',
                'Subject',
                'Priority',
                'Status',
                'Reported By',
                'Assigned Technician',
                'Created At',
                'Resolved At',
                'Resolution Hours',
                'Resolution Notes',
            ]);

            // Stream tickets in chunks of 200
            $query->chunk(200, function ($tickets) use ($handle) {
                foreach ($tickets as $ticket) {
                    $resolutionHours = '';
                    if ($ticket->resolved_at) {
                        $resolutionHours = round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 1);
                    }

                    fputcsv($handle, [
                        $ticket->ticket_number,
                        $ticket->branch->code ?? '',
                        $ticket->branch->name ?? '',
                        $ticket->type->label(),
                        $ticket->category->name ?? 'Uncategorized',
                        $this->sanitizeCsvField($ticket->subject),
                        $ticket->priority->label(),
                        $ticket->status->label(),
                        $ticket->creator->name ?? '',
                        $ticket->assignedEdp->name ?? 'Unassigned',
                        $ticket->created_at->format('Y-m-d H:i:s'),
                        $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i:s') : '',
                        $resolutionHours,
                        $this->sanitizeCsvField($ticket->resolution_notes ?? ''),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('start_date')) {
            $query->whereDate('tickets.created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tickets.created_at', '<=', $request->end_date);
        }

        if ($request->filled('branch_id')) {
            $query->where('tickets.branch_id', $request->branch_id);
        }

        if ($request->filled('category_id')) {
            $query->where('tickets.category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('tickets.status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('tickets.priority', $request->priority);
        }

        if ($request->filled('type')) {
            $query->where('tickets.type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                  ->orWhere('subject', 'ilike', "%{$search}%");
            });
        }
    }

    /**
     * Prevent CSV Formula Injection vulnerability (CWE-1236).
     */
    private function sanitizeCsvField(string $value): string
    {
        if (in_array(substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"])) {
            return "'" . $value;
        }
        return $value;
    }
}
