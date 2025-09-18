<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array {
        return [
            'fullname'    => ['required','string','max:255'],
            'email'       => ['nullable','email','max:255'],
            'description' => ['nullable','string','max:255'], // đúng schema VARCHAR(255)
            'skill'       => ['nullable','string','max:5000'], // CSV trong TEXT
        ];
    }
}
