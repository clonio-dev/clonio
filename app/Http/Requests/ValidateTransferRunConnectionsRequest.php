<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DatabaseConnection;
use App\Models\TransferRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use RuntimeException;

class ValidateTransferRunConnectionsRequest extends FormRequest
{
    private ?DatabaseConnection $sourceConnection = null;

    private ?DatabaseConnection $targetConnection = null;

    /** @var array<string, array<string, list<array{name: string, type: string, nullable: bool}>>> */
    private array $sourceSchema = [];

    /** @var array<string, array<string, list<array{name: string, type: string, nullable: bool}>>> */
    private array $targetSchema = [];

    public function authorize(): bool
    {
        return $this->user()->can('create', TransferRun::class);
    }

    /**
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_connection_id.required' => 'Please select a source connection.',
            'source_connection_id.exists' => 'The selected source connection is invalid.',
            'target_connection_id.required' => 'Please select a target connection.',
            'target_connection_id.exists' => 'The selected target connection is invalid.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateConnectionsCanConnect($validator);
            },
        ];
    }

    public function getSourceConnection(): ?DatabaseConnection
    {
        return $this->sourceConnection;
    }

    public function getTargetConnection(): ?DatabaseConnection
    {
        return $this->targetConnection;
    }

    /**
     * @return array<string, list<array{name: string, type: string, nullable: bool}>>
     */
    public function getSourceSchema(): array
    {
        return $this->sourceSchema;
    }

    /**
     * @return array<string, list<array{name: string, type: string, nullable: bool}>>
     */
    public function getTargetSchema(): array
    {
        return $this->targetSchema;
    }

    private function validateConnectionsCanConnect(Validator $validator): void
    {
        $this->sourceConnection = DatabaseConnection::query()
            ->forUser($this->user()->id)
            ->find($this->input('source_connection_id'));

        $this->targetConnection = DatabaseConnection::query()
            ->forUser($this->user()->id)
            ->find($this->input('target_connection_id'));

        if (! $this->sourceConnection || ! $this->targetConnection) {
            return;
        }

        $service = app(DatabaseInformationRetrievalService::class);

        // Test source connection
        try {
            $sourceConnectionData = $this->sourceConnection->toConnectionDataDto();
            $service->getConnection($sourceConnectionData);
            $this->sourceSchema = $this->retrieveSchema($service, $sourceConnectionData);
            $this->sourceConnection->update(['last_tested_at' => now()]);
        } catch (RuntimeException $e) {
            $validator->errors()->add(
                'source_connection_id',
                "Could not connect to source database: {$e->getMessage()}"
            );

            return;
        }

        // Test target connection
        try {
            $targetConnectionData = $this->targetConnection->toConnectionDataDto();
            $service->getConnection($targetConnectionData);
            $this->targetSchema = $this->retrieveSchema($service, $targetConnectionData);
            $this->targetConnection->update(['last_tested_at' => now()]);
        } catch (RuntimeException $e) {
            $validator->errors()->add(
                'target_connection_id',
                "Could not connect to target database: {$e->getMessage()}"
            );
        }
    }

    /**
     * @return array<string, list<array{name: string, type: string, nullable: bool}>>
     */
    private function retrieveSchema(DatabaseInformationRetrievalService $service, \App\Data\ConnectionData $connectionData): array
    {
        $tables = $service->getTableNames($connectionData);
        $schema = [];

        foreach ($tables as $tableName) {
            $connection = $service->getConnection($connectionData);
            assert($connection instanceof \Illuminate\Database\Connection);

            $columns = $connection->getSchemaBuilder()->getColumns($tableName);
            $schema[$tableName] = array_map(fn (array $column): array => [
                'name' => $column['name'],
                'type' => $column['type'],
                'nullable' => $column['nullable'],
            ], $columns);
        }

        return $schema;
    }
}
