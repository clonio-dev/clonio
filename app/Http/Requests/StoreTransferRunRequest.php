<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TransferRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRunRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', TransferRun::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
        ];
    }
}
