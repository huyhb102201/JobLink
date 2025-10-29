@extends('admin.layouts.app')
@section('title', 'Duyệt rút tiền')

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.4/r-3.0.3/datatables.min.css">
@endpush

@section('content')
    <div class="container-fluid">

        <!-- Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <h1 class="h4 fw-bold mb-0">Duyệt rút tiền</h1>
                @if($q || $status)
                    <span class="badge rounded-pill text-bg-info">Đang lọc</span>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <!-- Stats -->
        <div class="row row-cols-2 row-cols-md-4 g-3 mb-3 stats-row">
            @php
                $cards = [
                    ['label' => 'Tổng', 'value' => $stats['total'], 'icon' => 'fa-sack-dollar', 'bg' => 'linear-gradient(135deg,#EEF2FF,#F5F3FF)'],
                    ['label' => 'Chờ duyệt', 'value' => $stats['pending'], 'icon' => 'fa-hourglass-half', 'bg' => 'linear-gradient(135deg,#FFF7ED,#FFFBEB)'],
                    ['label' => 'Đã duyệt', 'value' => $stats['approved'], 'icon' => 'fa-badge-check', 'bg' => 'linear-gradient(135deg,#ECFEFF,#E0F2FE)'],
                    ['label' => 'Đã từ chối', 'value' => $stats['rejected'], 'icon' => 'fa-circle-xmark', 'bg' => 'linear-gradient(135deg,#FEF2F2,#FFE4E6)'],
                ];
              @endphp

            @foreach ($cards as $c)
                <div class="col">
                    <div class="card h-100 border-0 stat-card" style="background: {{ $c['bg'] }};">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="label">{{ $c['label'] }}</div>
                                    <div class="value mb-0">{{ $c['value'] }}</div>
                                </div>
                                <div class="icon-wrap">
                                    <i class="fa-solid {{ $c['icon'] }} fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Table -->
        <div class="card border-0 shadow-sm">
            <div class="p-2 pt-3 pb-0">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <label for="statusFilter" class="text-muted small mb-0">Lọc trạng thái:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:auto; min-width:180px">
                        <option value="">Tất cả</option>
                        <option value="processing">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Đã từ chối</option>
                    </select>
                    <button id="clearFilter" class="btn btn-sm btn-outline-secondary">Xóa lọc</button>
                </div>
            </div>

            <div class="table-responsive p-2">
                <table id="wTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Người rút</th>
                            <th>Email</th>
                            <th>Ngân hàng</th>
                            <th>Số TK</th>
                            <th class="text-end">Số tiền</th>
                            <th class="text-end">Phí</th>
                            <th class="text-end">Thực nhận</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $w)
                            @php
                                $badge = [
                                    'processing' => 'warning',
                                    'completed' => 'primary',
                                    'failed' => 'danger',
                                    'paid' => 'success',
                                ][$w->status] ?? 'secondary';
                                $label = [
                                    'processing' => 'Chờ duyệt',
                                    'completed' => 'Đã duyệt',
                                    'failed' => 'Đã từ chối',
                                    'paid' => 'Đã chi trả',
                                ][$w->status] ?? $w->status;

                                preg_match('/\(([^)]+)\)/', $w->bank_name ?? '', $matches);
                                $shortName = $matches[1] ?? ($w->bank_name ?? '');
                            @endphp
                            <tr data-id="{{ $w->id }}" data-status="{{ $w->status }}">
                                <td><span class="text-muted">#</span>{{ $w->id }}</td>
                                <td class="fw-semibold">
                                    {{ optional(optional($w->account)->profile)->fullname ?? optional($w->account)->name ?? 'N/A' }}
                                </td>
                                <td class="text-muted small">{{ optional($w->account)->email }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $shortName }}</div>
                                    @if(!empty($w->bank_short) || !empty($w->bank_code))
                                        <div class="text-muted small">
                                            {{ $w->bank_short ?? '' }}@if(!empty($w->bank_short) && !empty($w->bank_code)) /
                                            @endif{{ $w->bank_code ?? '' }}
                                        </div>
                                    @endif
                                </td>
                                <td><code class="text-danger">{{ $w->bank_account_number }}</code></td>
                                <td class="text-end">{{ number_format($w->amount_cents, 0, ',', '.') }} ₫</td>
                                <td class="text-end">{{ number_format(($w->fee_cents ?? 0), 0, ',', '.') }} ₫</td>
                                @php
                                    $net = max(0, (int) $w->amount_cents - (int) ($w->fee_cents ?? 0));
                                @endphp
                                <td class="text-end">{{ number_format($net, 0, ',', '.') }} ₫</td>
                                <td><span class="badge rounded-pill text-bg-{{ $badge }}">{{ $label }}</span></td>
                                <td class="text-muted small">{{ $w->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-secondary btn-view" data-id="{{ $w->id }}"
                                            title="Xem nhanh">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        @if($w->status === 'processing')
                                            <button class="btn btn-outline-success btn-approve" data-id="{{ $w->id }}"
                                                title="Duyệt">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-reject" data-id="{{ $w->id }}"
                                                title="Từ chối">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $rows->links() }}
            </div>
        </div>

    </div>

    <!-- Modal chi tiết -->
    <div class="modal fade" id="wModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <h5 class="modal-title"><i class="fa fa-receipt me-2"></i> Chi tiết yêu cầu rút tiền</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- skeleton -->
                    <div id="wSkeleton">
                        <div class="placeholder-glow">
                            <div class="placeholder col-12 mb-2" style="height:16px;"></div>
                            <div class="placeholder col-10 mb-2" style="height:16px;"></div>
                            <div class="placeholder col-8  mb-2" style="height:16px;"></div>
                            <div class="placeholder col-12 mb-2" style="height:16px;"></div>
                            <div class="placeholder col-6" style="height:16px;"></div>
                        </div>
                    </div>

                    <!-- content -->
                    <div id="wContent" class="d-none">
                        <dl class="row mb-3" id="wList"><!-- filled by JS --></dl>

                        <h6 class="fw-bold mb-2">Lịch sử cộng tiền gần đây</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle" id="wHistory">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Job</th>
                                        <th class="text-end">Số tiền</th>
                                        <th>Loại</th>
                                        <th>Ghi chú</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody><!-- filled by JS --></tbody>
                            </table>
                        </div>

                        <hr>
                        <pre id="wJson" class="bg-light p-2 rounded small mb-0" style="white-space:pre-wrap"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    .placeholder {
        background: #eef2ff !important;
    }

    .placeholder-glow .placeholder {
        animation: shimmer 1.1s infinite linear;
    }

    @keyframes shimmer {
        0% {
            opacity: .4
        }

        50% {
            opacity: .9
        }

        100% {
            opacity: .4
        }
    }

    /* KPI cards */
    .stat-card {
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, .06);
        border: 1px solid rgba(0, 0, 0, .04);
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(17, 24, 39, .09);
    }

    .stat-card .label {
        font-size: .85rem;
        color: #64748b;
        /* slate-500 */
        letter-spacing: .2px;
    }

    .stat-card .value {
        font-weight: 800;
        font-size: 1.6rem;
    }

    .stat-card .icon-wrap {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, .65);
        backdrop-filter: saturate(1.6) blur(2px);
        color: #334155;
        /* slate-700 */
    }

    /* Status chips */
    .chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .28rem .6rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: .78rem;
        line-height: 1;
        border: 1px solid transparent;
    }

    .chip i {
        font-size: .85rem;
        line-height: 1;
    }

    /* variants */
    .chip-warning {
        background: #fff7ed;
        color: #b45309;
        border-color: #fde68a;
    }

    .chip-primary {
        background: #eef2ff;
        color: #3730a3;
        border-color: #c7d2fe;
    }

    .chip-success {
        background: #ecfdf5;
        color: #047857;
        border-color: #bbf7d0;
    }

    .chip-danger {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #fecaca;
    }

    .chip-secondary {
        background: #f1f5f9;
        color: #334155;
        border-color: #e2e8f0;
    }

    /* nhịp nháy nhẹ cho processing */
    .chip-warning {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, .25);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, .35);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
        }
    }

    /* Giữ 5 card thống kê ngang bằng table */
    .stats-row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: .5rem;
        padding-right: .5rem;
    }

    .stats-row>[class*="col-"] {
        padding-left: .5rem;
        padding-right: .5rem;
    }

    /* Làm chiều cao đồng đều hơn */
    .stat-card .card-body {
        padding: 1rem 1.25rem !important;
    }
</style>

@push('scripts')
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.4/r-3.0.3/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            // DataTables
            const dt = $('#wTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                deferRender: true,
                responsive: true,
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                language: {
                    search: 'Tìm:', lengthMenu: 'Hiển thị _MENU_ dòng',
                    info: '_START_–_END_ / _TOTAL_', paginate: { previous: '‹', next: '›' }
                }
            });
            // --- Filter theo trạng thái ---
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData, counter) {
                const selected = $('#statusFilter').val();     // '', 'processing', 'approved', 'rejected'
                if (!selected) return true;

                // Lấy status thực tế từ attribute của row
                const row = dt.row(dataIndex).node();
                const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

                if (selected === 'rejected') {
                    // "Đã từ chối" bao gồm cả 'rejected' và 'failed'
                    return rowStatus === 'rejected' || rowStatus === 'failed';
                }
                return rowStatus === selected;
            });

            $('#statusFilter').on('change', function () {
                dt.draw();
            });

            $('#clearFilter').on('click', function () {
                $('#statusFilter').val('');
                dt.draw();
            });

            const token = $('meta[name="csrf-token"]').attr('content');

            // Modal nhanh: cache + skeleton + AbortController
            const wCache = new Map(); // id -> data
            let currentFetch = null;

            function showSkeleton() { $('#wSkeleton').removeClass('d-none'); $('#wContent').addClass('d-none'); }
            function showContent() { $('#wSkeleton').addClass('d-none'); $('#wContent').removeClass('d-none'); }
            function vnd(n) { return new Intl.NumberFormat('vi-VN').format(n || 0) + ' ₫'; } // KHÔNG chia /100

            function renderDetail(data) {
                // phần chi tiết
                const m = [
                    ['ID', '#' + data.id],
                    ['Người rút', data.account?.profile?.fullname ?? data.account?.name ?? 'N/A'],
                    ['Email', data.account?.email ?? 'N/A'],
                    ['Ngân hàng', data.bank_name],
                    ['Số TK', data.bank_account_number],
                    ['Số tiền', vnd(data.amount_cents)],     // giữ nguyên
                    ['Phí', vnd(data.fee_cents)],            // giữ nguyên
                    ['Trạng thái', data.status],
                    ['Ghi chú', data.note ?? '—'],
                    ['Thời gian', data.created_at ?? '—'],
                ];
                $('#wList').html(m.map(([k, v]) => `
                              <dt class="col-sm-3 text-muted">${k}</dt>
                              <dd class="col-sm-9">${v}</dd>
                            `).join(''));

                // lịch sử cộng tiền
                const hist = Array.isArray(data.history) ? data.history : [];
                const rows = hist.length ? hist.map((h, i) => `
                              <tr>
                                <td>${i + 1}</td>
                                <td>
                                  ${h.job_title ? `<div class="fw-semibold">${h.job_title}</div>` : ''}
                                  <div class="text-muted small">#${h.job_id ?? '—'}</div>
                                </td>
                                <td class="text-end"><span class="fw-semibold text-success">${vnd(h.amount_cents)}</span></td>
                                <td><span class="badge text-bg-secondary">${h.type || '—'}</span></td>
                                <td class="text-muted small">${h.note ?? '—'}</td>
                                <td class="text-muted small">${h.created_at ?? ''}</td>
                              </tr>
                            `).join('') : `<tr><td colspan="6" class="text-center text-muted py-3">Không có lịch sử.</td></tr>`;
                $('#wHistory tbody').html(rows);
            }

            async function fetchDetail(id, controller) {
                const r = await fetch(`{{ url('/admin/withdrawals') }}/${id}`, { signal: controller.signal, cache: 'no-store' });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Không tải được');
                return j.data;
            }

            $(document).on('click', '.btn-view', async function () {
                const id = $(this).data('id');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('wModal')).show();
                showSkeleton();

                if (wCache.has(id)) { renderDetail(wCache.get(id)); showContent(); }

                if (currentFetch && currentFetch.abort) currentFetch.abort();
                const controller = new AbortController(); currentFetch = controller;

                try {
                    const data = await fetchDetail(id, controller);
                    wCache.set(id, data);
                    renderDetail(data);
                    showContent();
                } catch (e) {
                    if (e.name === 'AbortError') return;
                    $('#wList').html(`<div class="text-danger">Lỗi tải dữ liệu.</div>`);
                    $('#wJson').text('');
                    showContent();
                } finally {
                    if (currentFetch === controller) currentFetch = null;
                }
            });

            // Approve / Reject
            function updateRowUI(tr, newStatus) {
                const map = {
                    processing: { badge: 'warning', text: 'Chờ duyệt' },
                    approved: { badge: 'primary', text: 'Đã duyệt' },
                    rejected: { badge: 'danger', text: 'Đã từ chối' },
                    failed: { badge: 'danger', text: 'Đã từ chối' },
                    paid: { badge: 'success', text: 'Đã chi trả' },
                };
                const m = map[newStatus] || { badge: 'secondary', text: newStatus };
                tr.attr('data-status', newStatus); // 👈 cập nhật cho filter
                tr.find('td:eq(8)').html(`<span class="badge rounded-pill text-bg-${m.badge}">${m.text}</span>`);
                if (newStatus !== 'processing') { tr.find('.btn-approve,.btn-reject').remove(); }
            }


            $(document).on('click', '.btn-approve', async function () {
                const id = $(this).data('id');
                const ok = await Swal.fire({ icon: 'question', title: 'Duyệt yêu cầu?', text: `Xác nhận duyệt yêu cầu #${id}`, showCancelButton: true, confirmButtonText: 'Duyệt' }).then(r => r.isConfirmed);
                if (!ok) return;
                const tr = $(`tr[data-id="${id}"]`);
                const resp = await fetch(`{{ url('/admin/withdrawals') }}/${id}/approve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': token } });
                const j = await resp.json();
                if (!resp.ok || !j.success) return Swal.fire('Lỗi', j.message || 'Không thể duyệt', 'error');
                updateRowUI(tr, 'approved');
                Swal.fire({ icon: 'success', title: 'Đã duyệt', timer: 1200, showConfirmButton: false });
            });

            $(document).on('click', '.btn-reject', async function () {
                const id = $(this).data('id');
                const { value: reason, isConfirmed } = await Swal.fire({ icon: 'warning', title: 'Từ chối yêu cầu?', input: 'text', inputLabel: 'Lý do (tuỳ chọn)', showCancelButton: true, confirmButtonText: 'Từ chối' });
                if (!isConfirmed) return;
                const tr = $(`tr[data-id="${id}"]`);
                const fd = new FormData(); if (reason) fd.append('reason', reason);
                const resp = await fetch(`{{ url('/admin/withdrawals') }}/${id}/reject`, { method: 'POST', headers: { 'X-CSRF-TOKEN': token }, body: fd });
                const j = await resp.json();
                if (!resp.ok || !j.success) return Swal.fire('Lỗi', j.message || 'Không thể từ chối', 'error');
                updateRowUI(tr, 'rejected');
                Swal.fire({ icon: 'success', title: 'Đã từ chối', timer: 1200, showConfirmButton: false });
            });
        });
    </script>
@endpush