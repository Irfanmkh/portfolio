<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'sekolah' => ['required', 'string', 'max:255'],
        'jurusan' => ['required', 'string', 'max:255'],
        'start_date' => ['required', 'date'],
        'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        'description' => ['nullable', 'string'],
        'tugas_akhir' => ['nullable', 'string', 'max:255'],
        'highlights' => ['nullable', 'array'],
        'highlights.*' => ['string', 'nullable', 'max:255'],
        'tech_stack' => ['nullable', 'array'],
        'tech_stack.*' => ['string', 'nullable', 'max:255'],
        ];
    }
}
