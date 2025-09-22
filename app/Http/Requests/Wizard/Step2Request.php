<?php
// app/Http/Requests/Wizard/Step2Request.php
namespace App\Http\Requests\Wizard;
use Illuminate\Foundation\Http\FormRequest;

class Step2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'description' => 'required|string|min:30',
            'category_id' => 'nullable|exists:job_categories,category_id',
            'content' => 'nullable|string',   // nội dung chi tiết (HTML)
        ];
    }

}
