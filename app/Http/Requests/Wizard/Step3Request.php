<?php
namespace App\Http\Requests\Wizard;
use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
           'payment_type' => 'required|in:fixed,hourly',
            'quantity'     => 'required|integer|min:1',   // ✅ giữ lại
            'total_budget' => 'nullable|integer|min:0',   // ✅ giữ lại
            'budget'       => 'nullable|integer|min:0', 
        ];
    }
}