<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Cloning;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCloningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Cloning::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'source_connection_id' => [
                'required',
                Rule::exists('database_connections', 'id')
                    ->where('user_id', $this->user()->id),
            ],
            'target_connection_id' => [
                'required',
                Rule::exists('database_connections', 'id')
                    ->where('user_id', $this->user()->id),
            ],
            'anonymization_config' => ['nullable', 'string'],
            'is_scheduled' => ['nullable', 'boolean'],
            'schedule' => ['nullable', 'string', 'max:100'],
            'execute_now' => ['nullable', 'boolean'],
            'trigger_config' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for this cloning configuration.',
            'source_connection_id.required' => 'Please select a source connection.',
            'source_connection_id.exists' => 'The selected source connection is invalid.',
            'target_connection_id.required' => 'Please select a target connection.',
            'target_connection_id.exists' => 'The selected target connection is invalid.',
        ];
    }
}
