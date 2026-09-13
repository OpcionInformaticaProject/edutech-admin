<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use App\Models\Group;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role->value, ['superadmin', 'admin', 'cartera', 'coordinador'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enrollment_date' => ['required', 'date', 'before_or_equal:today'],
            'billing_start_date' => ['required', 'date', 'after_or_equal:enrollment_date'],
            'agreed_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'group_id' => ['required', Rule::exists('groups', 'id')],
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $belongsToOrganization = Group::query()->whereKey($this->input('group_id'))
                ->whereHas('course.program', fn ($query) => $query->where('organization_id', $this->user()->organization_id))->exists();
            if (! $belongsToOrganization) {
                $validator->errors()->add('group_id', 'El grupo seleccionado no pertenece a la organización.');
            }
        }];
    }
}
