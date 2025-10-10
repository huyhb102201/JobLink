<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PaymentExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->payments->map(function ($payment) {
            return [
                'Mã đơn hàng' => $payment->order_code ?? 'N/A',
                'Email người dùng' => $payment->account->email ?? 'N/A',
                'Tên người dùng' => $payment->account->profile->fullname ?? $payment->account->name ?? 'N/A',
                'Số tiền' => number_format($payment->amount) . ' VNĐ',
                'Trạng thái' => $this->getStatusText($payment->status),
                'Phương thức thanh toán' => $payment->payment_method ?? 'N/A',
                'Mô tả' => $payment->description ?? 'Không có mô tả',
                'Ngày tạo' => $payment->created_at->format('d/m/Y H:i:s'),
                'Ngày cập nhật' => $payment->updated_at->format('d/m/Y H:i:s'),
            ];
        });
    }

    private function getStatusText($status)
    {
        switch ($status) {
            case 'success':
                return 'Thành công';
            case 'failed':
                return 'Thất bại';
            case 'pending':
                return 'Đang chờ';
            default:
                return ucfirst($status);
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Mã đơn hàng',
            'Email người dùng',
            'Tên người dùng',
            'Số tiền',
            'Trạng thái',
            'Phương thức thanh toán',
            'Mô tả',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }
}