<?php

declare(strict_types=1);

use App\Data\IndexSchema;

it('can be instantiated with required parameters', function (): void {
    $index = new IndexSchema(
        name: 'PRIMARY',
        type: 'primary',
        columns: ['id'],
    );

    expect($index->name)->toBe('PRIMARY')
        ->and($index->type)->toBe('primary')
        ->and($index->columns)->toBe(['id'])
        ->and($index->metadata)->toBe([]);
});

it('can be instantiated with all parameters', function (): void {
    $index = new IndexSchema(
        name: 'idx_users_email',
        type: 'unique',
        columns: ['email'],
        metadata: ['comment' => 'Email unique index'],
    );

    expect($index->name)->toBe('idx_users_email')
        ->and($index->type)->toBe('unique')
        ->and($index->columns)->toBe(['email'])
        ->and($index->metadata)->toBe(['comment' => 'Email unique index']);
});

it('can be created from array', function (): void {
    $data = [
        'name' => 'idx_posts_title',
        'type' => 'fulltext',
        'columns' => ['title', 'content'],
        'metadata' => ['parser' => 'ngram'],
    ];

    $index = IndexSchema::fromArray($data);

    expect($index->name)->toBe('idx_posts_title')
        ->and($index->type)->toBe('fulltext')
        ->and($index->columns)->toBe(['title', 'content'])
        ->and($index->metadata)->toBe(['parser' => 'ngram']);
});

it('can be created from array without metadata', function (): void {
    $data = [
        'name' => 'PRIMARY',
        'type' => 'primary',
        'columns' => ['id'],
    ];

    $index = IndexSchema::fromArray($data);

    expect($index->metadata)->toBe([]);
});

it('can be converted to array', function (): void {
    $index = new IndexSchema(
        name: 'idx_name',
        type: 'index',
        columns: ['first_name', 'last_name'],
        metadata: ['key' => 'value'],
    );

    $array = $index->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('idx_name')
        ->and($array['type'])->toBe('index')
        ->and($array['columns'])->toBe(['first_name', 'last_name'])
        ->and($array['metadata'])->toBe(['key' => 'value']);
});

it('correctly identifies primary key', function (): void {
    $primary = new IndexSchema('PRIMARY', 'primary', ['id']);
    $unique = new IndexSchema('idx_email', 'unique', ['email']);
    $index = new IndexSchema('idx_name', 'index', ['name']);

    expect($primary->isPrimary())->toBeTrue()
        ->and($unique->isPrimary())->toBeFalse()
        ->and($index->isPrimary())->toBeFalse();
});

it('correctly identifies unique index', function (): void {
    $primary = new IndexSchema('PRIMARY', 'primary', ['id']);
    $unique = new IndexSchema('idx_email', 'unique', ['email']);
    $index = new IndexSchema('idx_name', 'index', ['name']);
    $fulltext = new IndexSchema('idx_content', 'fulltext', ['content']);

    expect($primary->isUnique())->toBeTrue()
        ->and($unique->isUnique())->toBeTrue()
        ->and($index->isUnique())->toBeFalse()
        ->and($fulltext->isUnique())->toBeFalse();
});

it('correctly identifies composite index', function (): void {
    $single = new IndexSchema('idx_email', 'unique', ['email']);
    $composite = new IndexSchema('idx_name', 'index', ['first_name', 'last_name']);

    expect($single->isComposite())->toBeFalse()
        ->and($composite->isComposite())->toBeTrue();
});

it('returns correct column list', function (): void {
    $single = new IndexSchema('idx_email', 'unique', ['email']);
    $composite = new IndexSchema('idx_name', 'index', ['first_name', 'last_name', 'middle_name']);

    expect($single->getColumnList())->toBe('email')
        ->and($composite->getColumnList())->toBe('first_name, last_name, middle_name');
});
