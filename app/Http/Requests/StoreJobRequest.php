<?php
// app/Http/Requests/StoreJobRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:200',
            'description'  => 'required|string|min:20',
            'budget'       => 'nullable|numeric|min:0',
            'payment_type' => 'required|in:fixed,hourly',
            'deadline'     => 'nullable|date|after:today',
        ];
    }
}
