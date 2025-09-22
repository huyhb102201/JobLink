<?php
namespace App\Http\Requests\Wizard;
use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'payment_type' => 'required|in:fixed,hourly',
            'budget'       => 'nullable|numeric|min:0',
        ];
    }
}