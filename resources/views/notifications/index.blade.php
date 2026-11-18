<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1">Notifications</h4>
            <div class="text-muted small">System alerts, ticket assignments, status updates, and discussion activities</div>
        </div>
        @if (auth()->user()->unreadNotifications()->count() > 0)
            <div>
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-check-all me-1"></i> Mark All as Read
                    </button>
                </form>
            </div>
        @endif
    </x-slot>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Inbox ({{ $notifications->total() }})</span>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item p-3 {{ $notification->read_at ? 'bg-white' : 'bg-light border-start border-4 border-primary' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex gap-3">
                            <div class="mt-1">
                                <i class="bi {{ match($notification->data['type'] ?? '') {
                                    'ticket_created' => 'bi-ticket-perforated-fill text-primary',
                                    'ticket_assigned' => 'bi-person-check-fill text-indigo',
                                    'status_changed' => 'bi-arrow-repeat text-warning',
                                    'comment_added' => 'bi-chat-left-dots-fill text-success',
                                    'feature_stage_changed' => 'bi-lightbulb-fill text-info',
                                    default => 'bi-bell-fill text-secondary'
                                } }} fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                    @if (!$notification->read_at)
                                        <span class="badge bg-primary ms-1">New</span>
                                    @endif
                                </div>
                                <div class="text-secondary small mt-1">
                                    {{ $notification->data['message'] ?? '' }}
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    View <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    No notifications in your inbox.
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
