<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class WhatsAppGatewayController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless(
                    $request->user()?->hasRole(\App\Enums\UserRole::SuperAdmin->value),
                    403,
                    'Unauthorized. Super Admin access required.'
                );
                return $next($request);
            },
        ];
    }

    public function __construct(
        protected WhatsAppGatewayService $gateway
    ) {}

    /**
     * Display WhatsApp Gateway monitoring dashboard and audit logs.
     */
    public function index(Request $request): View
    {
        $status = $this->gateway->getGatewayLiveStatus();

        $metrics = [
            'totalSent' => WhatsAppLog::where('status', 'sent')->count(),
            'totalFailed' => WhatsAppLog::where('status', 'failed')->count(),
            'sentToday' => WhatsAppLog::where('status', 'sent')->whereDate('created_at', Carbon::today())->count(),
            'totalAll' => WhatsAppLog::count(),
        ];

        $query = WhatsAppLog::with(['ticket'])->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'ilike', "%{$search}%")
                  ->orWhere('message', 'ilike', "%{$search}%")
                  ->orWhereHas('ticket', fn ($t) => $t->where('ticket_number', 'ilike', "%{$search}%"));
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        return view('admin.whatsapp.index', compact('status', 'metrics', 'logs'));
    }

    /**
     * AJAX endpoint to poll live gateway health, QR code, and connection status.
     */
    public function status(): JsonResponse
    {
        $status = $this->gateway->getGatewayLiveStatus();
        return response()->json($status);
    }

    /**
     * AJAX endpoint to send an immediate test WhatsApp notification.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'message' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $phone = $request->input('phone');
        $message = $request->input('message');

        $sent = $this->gateway->send(
            phone: $phone,
            message: $message,
            ticket: null,
            recipientName: 'Super Admin UI Test'
        );

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => "Pesan uji coba WhatsApp berhasil dikirim ke {$phone}.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim pesan WhatsApp. Pastikan perangkat WhatsApp terhubung ke gateway.',
        ], 422);
    }

    /**
     * Disconnect/logout the active WhatsApp session.
     */
    public function disconnect(): JsonResponse
    {
        $result = $this->gateway->disconnectGateway();

        if ($result['success'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => 'Sesi WhatsApp berhasil diputus. Silakan scan QR code baru untuk menautkan perangkat.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Gagal memutus sesi WhatsApp.',
        ], 500);
    }

    /**
     * Clear all logs or logs older than 30 days.
     */
    public function clearLogs(): RedirectResponse
    {
        WhatsAppLog::truncate();

        return back()->with('success', 'Seluruh log riwayat notifikasi WhatsApp berhasil dibersihkan.');
    }
}
