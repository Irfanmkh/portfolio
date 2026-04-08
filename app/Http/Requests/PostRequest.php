<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $post = $this->route('post');
        $postId = $post instanceof Post ? $post->getKey() : null;
        $slugRule = Rule::unique('posts', 'slug');

        if ($postId) {
            $slugRule = $slugRule->ignore($postId);
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'content' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Draft,Published'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}