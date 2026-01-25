<?php

declare(strict_types=1);

use App\Data\ForeignKeySchema;

it('can be instantiated with required parameters', function (): void {
    $fk = new ForeignKeySchema(
        name: 'fk_posts_user_id',
        table: 'posts',
        columns: ['user_id'],
        referencedTable: 'users',
        referencedColumns: ['id'],
    );

    expect($fk->name)->toBe('fk_posts_user_id')
        ->and($fk->table)->toBe('posts')
        ->and($fk->columns)->toBe(['user_id'])
        ->and($fk->referencedTable)->toBe('users')
        ->and($fk->referencedColumns)->toBe(['id'])
        ->and($fk->onUpdate)->toBe('RESTRICT')
        ->and($fk->onDelete)->toBe('RESTRICT')
        ->and($fk->metadata)->toBe([]);
});

it('can be instantiated with all parameters', function (): void {
    $fk = new ForeignKeySchema(
        name: 'fk_orders_user_id',
        table: 'orders',
        columns: ['user_id'],
        referencedTable: 'users',
        referencedColumns: ['id'],
        onUpdate: 'CASCADE',
        onDelete: 'SET NULL',
        metadata: ['comment' => 'User relation'],
    );

    expect($fk->onUpdate)->toBe('CASCADE')
        ->and($fk->onDelete)->toBe('SET NULL')
        ->and($fk->metadata)->toBe(['comment' => 'User relation']);
});

it('can be created from array', function (): void {
    $data = [
        'name' => 'fk_comments_post_id',
        'table' => 'comments',
        'columns' => ['post_id'],
        'referenced_table' => 'posts',
        'referenced_columns' => ['id'],
        'on_update' => 'CASCADE',
        'on_delete' => 'CASCADE',
        'metadata' => [],
    ];

    $fk = ForeignKeySchema::fromArray($data);

    expect($fk->name)->toBe('fk_comments_post_id')
        ->and($fk->table)->toBe('comments')
        ->and($fk->referencedTable)->toBe('posts')
        ->and($fk->onUpdate)->toBe('CASCADE')
        ->and($fk->onDelete)->toBe('CASCADE');
});

it('can be created from array with defaults', function (): void {
    $data = [
        'name' => 'fk_test',
        'table' => 'table_a',
        'columns' => ['ref_id'],
        'referenced_table' => 'table_b',
        'referenced_columns' => ['id'],
    ];

    $fk = ForeignKeySchema::fromArray($data);

    expect($fk->onUpdate)->toBe('RESTRICT')
        ->and($fk->onDelete)->toBe('RESTRICT')
        ->and($fk->metadata)->toBe([]);
});

it('can be converted to array', function (): void {
    $fk = new ForeignKeySchema(
        name: 'fk_test',
        table: 'table_a',
        columns: ['ref_id'],
        referencedTable: 'table_b',
        referencedColumns: ['id'],
        onUpdate: 'CASCADE',
        onDelete: 'NO ACTION',
    );

    $array = $fk->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('fk_test')
        ->and($array['table'])->toBe('table_a')
        ->and($array['columns'])->toBe(['ref_id'])
        ->and($array['referenced_table'])->toBe('table_b')
        ->and($array['referenced_columns'])->toBe(['id'])
        ->and($array['on_update'])->toBe('CASCADE')
        ->and($array['on_delete'])->toBe('NO ACTION');
});

it('correctly identifies cascade on delete', function (): void {
    $cascade = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'RESTRICT', 'CASCADE');
    $restrict = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'RESTRICT', 'RESTRICT');
    $setNull = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'RESTRICT', 'SET NULL');

    expect($cascade->cascadesOnDelete())->toBeTrue()
        ->and($restrict->cascadesOnDelete())->toBeFalse()
        ->and($setNull->cascadesOnDelete())->toBeFalse();
});

it('correctly identifies cascade on update', function (): void {
    $cascade = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'CASCADE', 'RESTRICT');
    $restrict = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'RESTRICT', 'RESTRICT');

    expect($cascade->cascadesOnUpdate())->toBeTrue()
        ->and($restrict->cascadesOnUpdate())->toBeFalse();
});

it('correctly identifies composite foreign key', function (): void {
    $single = new ForeignKeySchema('fk1', 'a', ['user_id'], 'users', ['id']);
    $composite = new ForeignKeySchema('fk2', 'a', ['user_id', 'tenant_id'], 'users', ['id', 'tenant_id']);

    expect($single->isComposite())->toBeFalse()
        ->and($composite->isComposite())->toBeTrue();
});

it('returns correct column mapping for single column', function (): void {
    $fk = new ForeignKeySchema(
        name: 'fk_posts_user_id',
        table: 'posts',
        columns: ['user_id'],
        referencedTable: 'users',
        referencedColumns: ['id'],
    );

    expect($fk->getColumnMapping())->toBe('user_id -> users.id');
});

it('returns correct column mapping for composite key', function (): void {
    $fk = new ForeignKeySchema(
        name: 'fk_composite',
        table: 'child',
        columns: ['parent_id', 'tenant_id'],
        referencedTable: 'parent',
        referencedColumns: ['id', 'tenant_id'],
    );

    expect($fk->getColumnMapping())->toBe('parent_id -> parent.id, tenant_id -> parent.tenant_id');
});

it('handles case-insensitive cascade detection', function (): void {
    $lowercase = new ForeignKeySchema('fk', 'a', ['id'], 'b', ['id'], 'cascade', 'cascade');

    expect($lowercase->cascadesOnDelete())->toBeTrue()
        ->and($lowercase->cascadesOnUpdate())->toBeTrue();
});
