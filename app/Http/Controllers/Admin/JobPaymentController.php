<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminLogService;

class JobPaymentController extends Controller
{
    public function index(Request $request)
    {
        // Load tất cả payments với job và client
        $jobPayments = JobPayment::with([
            'job' => function ($q) {
                $q->with([
                    'client' => function ($q2) {
                        $q2->with('profile');
                    }
                ]);
            }
        ])->orderByDesc('created_at')->get();


        // Group payments by client (email + name)
        $groupedPayments = [];
        foreach ($jobPayments as $payment) {
            $job = $payment->job;

            // Xử lý trường hợp job hoặc client bị xóa
            if (!$job || !$job->client) {
                // Tạo group cho giao dịch không có client (job đã xóa)
                $clientKey = 'deleted_' . ($job ? $job->job_id : 'unknown');
                $clientName = $job ? "Job đã bị xóa (ID: {$job->job_id})" : "Job không tồn tại";
                $clientEmail = "N/A";
            } else {
                $client = $job->client;
                $profile = $client->profile;

                $clientName = $profile ? $profile->fullname : ($client->name ?? 'N/A');
                $clientEmail = $client->email ?? 'N/A';

                // Create unique key based on client name and email
                $clientKey = $clientEmail . '|' . $clientName;
            }

            if (!isset($groupedPayments[$clientKey])) {
                $groupedPayments[$clientKey] = [
                    'client_name' => $clientName,
                    'client_email' => $clientEmail,
                    'total_amount' => 0,
                    'payment_count' => 0,
                    'jobs' => []
                ];
            }

            // Group by job within client
            $jobId = $payment->job_id;
            if (!isset($groupedPayments[$clientKey]['jobs'][$jobId])) {
                $groupedPayments[$clientKey]['jobs'][$jobId] = [
                    'job_id' => $jobId,
                    'job_title' => $job ? $job->title : "Job đã bị xóa (ID: {$jobId})",
                    'job_budget' => $job ? $job->budget : 0,
                    'payments' => []
                ];
            }

            // Add payment to job
            $statusBadge = match ($payment->status) {
                'paid' => '<span class="badge bg-success">Đã thanh toán</span>',
                'pending' => '<span class="badge bg-warning">Chờ thanh toán</span>',
                'processing' => '<span class="badge bg-info">Đang xử lý</span>',
                'canceled' => '<span class="badge bg-secondary">Đã hủy</span>',
                'failed' => '<span class="badge bg-danger">Thất bại</span>',
                default => '<span class="badge bg-secondary">' . $payment->status . '</span>',
            };

            $groupedPayments[$clientKey]['jobs'][$jobId]['payments'][] = [
                'id' => $payment->id,
                'orderCode' => $payment->orderCode ?? 'N/A',
                'amount' => $payment->amount,
                'amount_formatted' => number_format($payment->amount ?? 0, 0, ',', '.'),
                'status' => $payment->status ?? 'pending',
                'status_badge' => $statusBadge,
                'description' => $payment->description,
                'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : 'N/A',
            ];

            // Tính tổng tiền chỉ cho giao dịch thành công
            if (in_array(strtolower($payment->status), ['paid', 'success'])) {
                $groupedPayments[$clientKey]['total_amount'] += $payment->amount ?? 0;
            }
            // Đếm TẤT CẢ giao dịch (kể cả pending, failed...)
            $groupedPayments[$clientKey]['payment_count']++;
        }

        // Convert jobs to indexed array for each client
        foreach ($groupedPayments as &$group) {
            $group['jobs'] = array_values($group['jobs']);
        }

        // Convert to indexed array
        $groupedPayments = array_values($groupedPayments);

        // Tính tổng tiền và số giao dịch TRỰC TIẾP từ database để chính xác
        $totalPaidAmount = JobPayment::where('status', 'paid')->sum('amount') ?? 0;
        $totalPaidTransactions = JobPayment::where('status', 'paid')->count();

        // Tổng số giao dịch và số tiền đang chờ (tính từ DB)
        $totalPendingAmount = JobPayment::whereIn('status', ['pending', 'processing', 'PENDING'])->sum('amount');
        $totalPayments = JobPayment::count();

        return view('admin.payments.job-payments', [
            'groupedPayments' => $groupedPayments,
            'totalPaidAmount' => $totalPaidAmount,
            'totalPendingAmount' => $totalPendingAmount,
            'totalPayments' => $totalPayments,
            'totalPaidTransactions' => $totalPaidTransactions,
        ]);
    }

    public function show($id)
    {
        try {
            $payment = JobPayment::with(['job.client.profile'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch thanh toán job.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'job_id' => $payment->job_id,
                    'orderCode' => $payment->orderCode,
                    'amount' => $payment->amount,
                    'amount_formatted' => number_format($payment->amount ?? 0, 0, ',', '.'),
                    'status' => $payment->status,
                    'status_badge' => $this->formatStatusBadge($payment->status),
                    'status_label' => $this->formatStatusLabel($payment->status),
                    'description' => $payment->description,
                    'created_at' => optional($payment->created_at)->format('d/m/Y H:i'),
                    'updated_at' => optional($payment->updated_at)->format('d/m/Y H:i'),
                    'job' => [
                        'id' => $payment->job?->job_id,
                        'title' => $payment->job?->title,
                        'budget' => $payment->job?->budget,
                    ],
                    'client' => [
                        'id' => $payment->job?->client?->account_id,
                        'fullname' => $payment->job?->client?->profile?->fullname,
                        'email' => $payment->job?->client?->email,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin job payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi lấy thông tin giao dịch.'
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,paid,canceled,failed',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $payment = JobPayment::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch.'
                ], 404);
            }

            $oldStatus = $payment->status;
            $payment->status = $request->status;
            if ($request->filled('description')) {
                $payment->description = $request->description;
            }
            $payment->save();

            // Log status change
            AdminLogService::logStatusChange(
                'JobPayment',
                $payment->id,
                $oldStatus,
                $request->status,
                "Cập nhật trạng thái thanh toán job #{$payment->job_id}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'status_badge' => $this->formatStatusBadge($payment->status),
                'status' => $payment->status,
                'status_label' => $this->formatStatusLabel($payment->status),
                'updated_at' => optional($payment->updated_at)->format('d/m/Y H:i'),
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật trạng thái job payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi cập nhật trạng thái.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = JobPayment::find($id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch.'
                ], 404);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa giao dịch thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa job payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi xóa giao dịch.'
            ], 500);
        }
    }

    private function formatStatusBadge(string $status): string
    {
        return match ($status) {
            'paid' => '<span class="badge bg-success">Đã thanh toán</span>',
            'processing' => '<span class="badge bg-primary">Đang xử lý</span>',
            'canceled' => '<span class="badge bg-secondary">Đã hủy</span>',
            'failed' => '<span class="badge bg-danger">Thất bại</span>',
            default => '<span class="badge bg-warning text-dark">Chờ thanh toán</span>',
        };
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Đã thanh toán',
            'processing' => 'Đang xử lý',
            'canceled' => 'Đã hủy',
            'failed' => 'Thất bại',
            default => 'Chờ thanh toán',
        };
    }
}
