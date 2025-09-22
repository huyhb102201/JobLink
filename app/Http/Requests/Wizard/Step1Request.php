<?php
namespace App\Http\Requests\Wizard;
use Illuminate\Foundation\Http\FormRequest;

class Step1Request extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['title' => 'required|string|max:200'];
    }
}