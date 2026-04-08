<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->getKey() : null;
        $slugRule = Rule::unique('projects', 'slug');

        if ($projectId) {
            $slugRule = $slugRule->ignore($projectId);
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'made_at' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
            'thumbnail_remove' => ['nullable', 'boolean'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['file', 'image', 'max:4096'],
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['nullable', 'string'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'results' => ['nullable', 'array'],
            'results.*' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Ongoing,Completed'],
        ];
    }
}