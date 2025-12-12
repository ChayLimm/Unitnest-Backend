<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|nullable|email|max:255|unique:tenants,email,' . $tenantId,
            'phone' => 'sometimes|nullable|string|max:20',
            'telegram_id' => 'sometimes|nullable|string|max:20',
            'identify_id' => 'sometimes|nullable|string|max:20',
            'profile_image_url' => 'sometimes|nullable|url|max:255',
            'identify_image_url' => 'sometimes|nullable|url|max:255',
            'emergency_contact' => 'sometimes|nullable|array',
            'emergency_contact.name' => 'required_with:emergency_contact|string|max:100',
            'emergency_contact.phone' => 'required_with:emergency_contact|string|max:20',
            'emergency_contact.relationship' => 'required_with:emergency_contact|string|max:50',
        ];
    }
}