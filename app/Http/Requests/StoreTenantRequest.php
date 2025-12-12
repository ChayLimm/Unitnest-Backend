<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You might want to add authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:tenants,email',
            'phone' => 'nullable|string|max:20',
            'telegram_id' => 'nullable|string|max:20',
            'identify_id' => 'nullable|string|max:20',
            'profile_image_url' => 'nullable|url|max:255',
            'identify_image_url' => 'nullable|url|max:255',
            'emergency_contact' => 'nullable|array',
            'emergency_contact.name' => 'required_with:emergency_contact|string|max:100',
            'emergency_contact.phone' => 'required_with:emergency_contact|string|max:20',
            'emergency_contact.relationship' => 'required_with:emergency_contact|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'emergency_contact.name.required_with' => 'Emergency contact name is required when emergency contact is provided.',
            'emergency_contact.phone.required_with' => 'Emergency contact phone is required when emergency contact is provided.',
            'emergency_contact.relationship.required_with' => 'Emergency contact relationship is required when emergency contact is provided.',
        ];
    }
}