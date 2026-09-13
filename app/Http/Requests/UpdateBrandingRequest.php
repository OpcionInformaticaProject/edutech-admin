<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateBrandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role->value, ['superadmin', 'admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp', 'svg'])->max(2048)],
            'login_logo' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp', 'svg'])->max(2048)],
            'favicon' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp', 'svg'])->max(512)],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['logo', 'login_logo', 'favicon'] as $field) {
                $file = $this->file($field);
                if (! $file || strtolower($file->getClientOriginalExtension()) !== 'svg') {
                    continue;
                }
                $svg = strtolower($file->getContent());
                if (preg_match('/<script|on\w+\s*=|javascript:|<foreignobject|https?:\/\//i', $svg)) {
                    $validator->errors()->add($field, 'El SVG contiene elementos no permitidos.');
                }
            }
        }];
    }
}
