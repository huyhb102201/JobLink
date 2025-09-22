<?php
namespace App\Http\Requests\Wizard;
use Illuminate\Foundation\Http\FormRequest;

class Step4Request extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['deadline' => 'nullable|date|after:today'];
    }
}