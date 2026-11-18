<x-app-layout>
    <x-slot name="header">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Gateway & Audit Logs
            </h4>
            <div class="text-muted small">
                Monitoring status koneksi bot WhatsApp (Baileys), pemindaian QR Code perangkat, dan riwayat pengiriman notifikasi tiket.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
    </x-slot>

    <!-- Top KPI Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100 border-start border-4 {{ ($status['connected'] ?? false) ? 'border-success' : 'border-warning' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Status Gateway</span>
                        <i class="bi {{ ($status['connected'] ?? false) ? 'bi-shield-check text-success' : 'bi-exclamation-triangle text-warning' }} fs-5"></i>
                    </div>
                    <div class="fs-5 fw-bold mb-1">
                        @if ($status['connected'] ?? false)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-circle-fill text-success me-1" style="font-size: 0.5rem;"></i> Connected
                            </span>
                        @elseif ($status['online'] ?? false)
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="bi bi-qr-code me-1"></i> Waiting QR Scan
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                <i class="bi bi-x-circle me-1"></i> Service Offline
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary small font-monospace text-truncate" style="font-size: 0.75rem;" id="botUserLabel">
                        {{ $status['user'] ? 'Bot: ' . explode(':', $status['user'])[0] : 'Belum tertaut' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Terkirim Hari Ini</span>
                        <i class="bi bi-send-check text-primary fs-5"></i>
                    </div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($metrics['sentToday']) }}</div>
                    <div class="text-muted small" style="font-size: 0.75rem;">Notifikasi terkirim hari ini</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Total Berhasil</span>
                        <i class="bi bi-check-all text-info fs-5"></i>
                    </div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($metrics['totalSent']) }}</div>
                    <div class="text-muted small" style="font-size: 0.75rem;">Dari keseluruhan log</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">Pesan Gagal</span>
                        <i class="bi bi-x-octagon text-danger fs-5"></i>
                    </div>
                    <div class="fs-4 fw-bold {{ $metrics['totalFailed'] > 0 ? 'text-danger' : 'text-dark' }}">
                        {{ number_format($metrics['totalFailed']) }}
                    </div>
                    <div class="text-muted small" style="font-size: 0.75rem;">Koneksi terputus / nomor salah</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Connection & Tester Cards -->
    <div class="row g-4 mb-4">
        <!-- Gateway Connection / QR Box -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                    <span class="fw-bold small text-uppercase text-secondary">
                        <i class="bi bi-broadcast text-primary me-1"></i> Perangkat WhatsApp Gateway
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary small font-monospace">Port 3001</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4" id="gatewayContainer">
                    @if ($status['connected'] ?? false)
                        <div class="my-auto py-3">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center p-3 mb-3">
                                <i class="bi bi-whatsapp fs-1"></i>
                            </div>
                            <h5 class="fw-bold text-success mb-1">WhatsApp Terhubung Aktif</h5>
                            <p class="text-secondary small mb-3">
                                Gateway siap mengirimkan notifikasi tiket secara real-time.
                            </p>
                            <div class="p-3 bg-light rounded border mb-4 text-start small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Nomor Bot:</span>
                                    <strong class="font-monospace text-dark">{{ explode(':', $status['user'] ?? '')[0] }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Driver:</span>
                                    <span class="badge bg-dark-subtle text-dark">Baileys Multi-File Auth</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Status Socket:</span>
                                    <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Live & Ready</span>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnDisconnect">
                                <i class="bi bi-box-arrow-right me-1"></i> Tautkan Ulang / Putus Sesi (Logout)
                            </button>
                        </div>
                    @elseif ($status['online'] ?? false)
                        <div class="my-auto py-2">
                            <h5 class="fw-bold text-dark mb-1">Scan QR Code WhatsApp</h5>
                            <p class="text-muted small mb-3">Tautkan nomor HP resmi helpdesk/EDP untuk bot notifikasi.</p>

                            <div class="p-2 bg-white border rounded shadow-sm d-inline-block mb-3">
                                <img src="{{ $status['qr_data_url'] }}" alt="WhatsApp QR" id="qrImageElement" class="img-fluid" style="width: 250px; height: 250px;">
                            </div>

                            <ol class="text-start small text-secondary ps-3 mb-3 mx-auto" style="max-width: 320px;">
                                <li>Buka aplikasi <strong>WhatsApp</strong> di HP Anda</li>
                                <li>Pilih <strong>Perangkat Tertaut</strong> &rarr; <strong>Tautkan Perangkat</strong></li>
                                <li>Arahkan kamera ke QR Code di atas</li>
                            </ol>

                            <div class="text-muted small d-flex align-items-center justify-content-center gap-2">
                                <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                                <span style="font-size: 0.75rem;">Menunggu pemindaian... QR ter-refresh otomatis.</span>
                            </div>
                        </div>
                    @else
                        <div class="my-auto py-4 text-center">
                            <i class="bi bi-exclamation-octagon text-danger fs-1 d-block mb-2"></i>
                            <h5 class="fw-bold text-danger mb-1">Microservice Baileys Offline</h5>
                            <p class="text-muted small mb-3">
                                Layanan microservice Baileys di port 3001 tidak merespon health check.
                            </p>
                            <div class="p-3 bg-light rounded border text-start small font-monospace text-secondary mb-3">
                                node whatsapp-service/server.js
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.location.reload();">
                                <i class="bi bi-arrow-repeat me-1"></i> Cek Ulang Koneksi
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- WhatsApp Tester Box -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <span class="fw-bold small text-uppercase text-secondary">
                        <i class="bi bi-send text-primary me-1"></i> Uji Coba Pengiriman Pesan (Tester)
                    </span>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Kirimkan pesan uji coba instan ke nomor WhatsApp mana pun untuk memverifikasi fungsionalitas gateway.
                    </p>

                    <form id="testWaForm">
                        @csrf
                        <div class="mb-3">
                            <label for="test_phone" class="form-label small fw-semibold">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control font-monospace" id="test_phone" name="phone"
                                       placeholder="Contoh: 081234567890 atau 6281234567890" required>
                            </div>
                            <div class="form-text small" style="font-size: 0.725rem;">Format nomor lokal (08...) otomatis dikonversi ke format 628...</div>
                        </div>

                        <div class="mb-3">
                            <label for="test_message" class="form-label small fw-semibold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="test_message" name="message" rows="5" required>🔔 *TES NOTIFIKASI IT HELPDESK*

Halo! Pesan ini dikirim langsung dari Control Center WhatsApp Gateway.
Gateway berfungsi dengan normal dan siap mengirim notifikasi tiket.

Waktu: {{ now()->format('d M Y, H:i:s') }} WIB
Centralized IT Helpdesk System</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2" id="btnSubmitTest">
                            <i class="bi bi-send-fill"></i>
                            <span>Kirim Notifikasi Uji Coba</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Audit Logs Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-text text-primary me-2"></i>Riwayat Log Notifikasi WhatsApp
                </h6>
                <div class="text-muted small" style="font-size: 0.75rem;">
                    Daftar seluruh notifikasi yang dikirimkan oleh sistem helpdesk ke teknisi dan user.
                </div>
            </div>

            <!-- Filter & Clear Buttons -->
            <div class="d-flex align-items-center gap-2">
                <form method="POST" action="{{ route('admin.whatsapp.clear-logs') }}" id="clearLogsForm">
                    @csrf
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearLogs">
                        <i class="bi bi-trash3 me-1"></i> Bersihkan Log
                    </button>
                </form>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card-body border-bottom bg-light py-2">
            <form method="GET" action="{{ route('admin.whatsapp.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nomor, nama, tiket, atau pesan..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Terkirim (Sent)</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal (Failed)</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Stream -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 140px;">Waktu</th>
                        <th>Penerima</th>
                        <th>Terkait Tiket</th>
                        <th>Pesan Notifikasi</th>
                        <th class="text-center" style="width: 100px;">Status</th>
                        <th class="text-end pe-3" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="ps-3 text-secondary" style="font-size: 0.775rem;">
                                <div class="fw-semibold text-dark">{{ $log->created_at->format('d M Y') }}</div>
                                <div>{{ $log->created_at->format('H:i:s') }} WIB</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $log->recipient_name ?: 'Teknisi / User' }}</div>
                                <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.725rem;">
                                    +{{ $log->recipient_phone }}
                                </span>
                            </td>

                            <td>
                                @if ($log->ticket)
                                    <a href="{{ route('tickets.show', $log->ticket) }}" class="badge bg-primary-subtle text-primary text-decoration-none border border-primary-subtle">
                                        <i class="bi bi-ticket-perforated me-1"></i>{{ $log->ticket->ticket_number }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border">Manual / Tester</span>
                                @endif
                            </td>

                            <td>
                                <div class="text-truncate" style="max-width: 320px;" title="{{ $log->message }}">
                                    {{ Str::limit(str_replace("\n", ' ', $log->message), 80) }}
                                </div>
                                @if ($log->error_message)
                                    <div class="text-danger small mt-1 font-monospace" style="font-size: 0.7rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ Str::limit($log->error_message, 60) }}
                                    </div>
                                @endif
                            </td>

                            <td class="text-center">
                                @if ($log->isSent())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check2 me-1"></i>Sent
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-x me-1"></i>Failed
                                    </span>
                                @endif
                            </td>

                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2"
                                        onclick="showLogDetail({{ json_encode($log->recipient_phone) }}, {{ json_encode($log->recipient_name ?? '-') }}, {{ json_encode($log->message) }}, {{ json_encode($log->error_message ?? '') }}, {{ json_encode($log->status) }}, {{ json_encode($log->created_at->format('d M Y, H:i:s')) }})">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-left-dots fs-2 text-secondary opacity-50 d-block mb-2"></i>
                                Belum ada riwayat pengiriman pesan WhatsApp yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Detail Message Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="modalTitle">Detail Log WhatsApp</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 pb-2 border-bottom small">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted d-block">Penerima:</span>
                                <strong id="modalRecipient"></strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Waktu:</span>
                                <span id="modalTime" class="font-monospace text-secondary"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Pesan Lengkap:</label>
                        <div class="p-3 bg-light rounded border text-secondary font-monospace small" id="modalMessage" style="white-space: pre-wrap; max-height: 250px; overflow-y: auto;"></div>
                    </div>
                    <div id="modalErrorContainer" class="d-none">
                        <label class="form-label small fw-semibold text-danger">Pesan Error / Penyebab Gagal:</label>
                        <div class="p-2 bg-danger-subtle text-danger rounded border border-danger-subtle small font-monospace" id="modalError"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Modal detail viewer
        function showLogDetail(phone, name, message, errorMsg, status, time) {
            document.getElementById('modalRecipient').innerText = (name !== '-' ? name + ' (' + phone + ')' : '+' + phone);
            document.getElementById('modalTime').innerText = time;
            document.getElementById('modalMessage').innerText = message;

            const errContainer = document.getElementById('modalErrorContainer');
            if (status === 'failed' && errorMsg) {
                document.getElementById('modalError').innerText = errorMsg;
                errContainer.classList.remove('d-none');
            } else {
                errContainer.classList.add('d-none');
            }

            const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Test WA Form Submission
            const testForm = document.getElementById('testWaForm');
            if (testForm) {
                testForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const btn = document.getElementById('btnSubmitTest');
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mengirim pesan...';

                    const formData = new FormData(testForm);

                    fetch('{{ route('admin.whatsapp.send-test') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(res => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        if (res.status === 200 && res.body.success) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terkirim!',
                                    text: res.body.message,
                                    confirmButtonColor: '#2563eb'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                alert(res.body.message);
                                window.location.reload();
                            }
                        } else {
                            const errMsg = res.body.message || (res.body.errors ? Object.values(res.body.errors).flat().join('<br>') : 'Gagal mengirim pesan.');
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Mengirim',
                                    html: errMsg,
                                    confirmButtonColor: '#ef4444'
                                });
                            } else {
                                alert(errMsg);
                            }
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Kesalahan Sistem',
                                text: 'Tidak dapat menghubungi server: ' + err.message,
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    });
                });
            }

            // Disconnect Session Button
            const btnDisconnect = document.getElementById('btnDisconnect');
            if (btnDisconnect) {
                btnDisconnect.addEventListener('click', function () {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Putus Sesi WhatsApp?',
                            text: 'Bot WhatsApp akan logout dan berhenti mengirim notifikasi sampai Anda memindai QR Code baru.',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Putus Sesi',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executeDisconnect();
                            }
                        });
                    } else if (confirm('Apakah Anda yakin ingin memutus sesi WhatsApp ini?')) {
                        executeDisconnect();
                    }
                });
            }

            function executeDisconnect() {
                fetch('{{ route('admin.whatsapp.disconnect') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sesi Diputus',
                                text: data.message,
                                confirmButtonColor: '#2563eb'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message);
                            window.location.reload();
                        }
                    } else {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message || 'Gagal memutus sesi.',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    }
                });
            }

            // Clear Logs Button
            const btnClearLogs = document.getElementById('btnClearLogs');
            const clearLogsForm = document.getElementById('clearLogsForm');
            if (btnClearLogs && clearLogsForm) {
                btnClearLogs.addEventListener('click', function () {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Bersihkan Semua Log WhatsApp?',
                            text: 'Seluruh riwayat log notifikasi WhatsApp akan dihapus permanen dari database.',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Bersihkan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                clearLogsForm.submit();
                            }
                        });
                    } else if (confirm('Bersihkan semua riwayat log WhatsApp?')) {
                        clearLogsForm.submit();
                    }
                });
            }

            // Realtime polling if QR code is waiting
            @if (!($status['connected'] ?? false) && ($status['online'] ?? false))
                const pollInterval = setInterval(() => {
                    fetch('{{ route('admin.whatsapp.status') }}')
                        .then(res => res.json())
                        .then(data => {
                            if (data.connected) {
                                clearInterval(pollInterval);
                                if (window.Swal) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Perangkat Terhubung!',
                                        text: 'WhatsApp berhasil ditautkan ke sistem.',
                                        confirmButtonColor: '#2563eb'
                                    }).then(() => window.location.reload());
                                } else {
                                    window.location.reload();
                                }
                            } else if (data.qr_data_url) {
                                const qrImg = document.getElementById('qrImageElement');
                                if (qrImg) qrImg.src = data.qr_data_url;
                            }
                        })
                        .catch(() => {});
                }, 5000);
            @endif
        });
    </script>
    @endpush
</x-app-layout>
