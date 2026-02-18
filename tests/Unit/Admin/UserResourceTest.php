<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Admin\UserResource;

beforeEach(function () {
    $this->resource = new UserResource;
});

// --- fields() ---

it('returns field definitions for user management', function () {
    $fields = $this->resource->fields();

    expect($fields)->toBeArray()->toHaveCount(4);

    $fieldNames = array_column($fields, 'name');
    expect($fieldNames)->toBe(['name', 'email', 'password', 'is_admin']);
});

it('defines name as a required text field', function () {
    $fields = collect($this->resource->fields());
    $name = $fields->firstWhere('name', 'name');

    expect($name)
        ->toMatchArray([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Name',
            'required' => true,
        ]);
    expect($name['rules'])->toContain('required', 'string', 'max:255');
});

it('defines email as a required email field', function () {
    $fields = collect($this->resource->fields());
    $email = $fields->firstWhere('name', 'email');

    expect($email)
        ->toMatchArray([
            'name' => 'email',
            'type' => 'email',
            'label' => 'Email',
            'required' => true,
        ]);
    expect($email['rules'])->toContain('required', 'string', 'email', 'max:255');
});

it('defines password as a password field', function () {
    $fields = collect($this->resource->fields());
    $password = $fields->firstWhere('name', 'password');

    expect($password)
        ->toMatchArray([
            'name' => 'password',
            'type' => 'password',
            'label' => 'Password',
        ]);
    expect($password)->not->toHaveKey('required');
});

it('defines is_admin as a checkbox field', function () {
    $fields = collect($this->resource->fields());
    $isAdmin = $fields->firstWhere('name', 'is_admin');

    expect($isAdmin)
        ->toMatchArray([
            'name' => 'is_admin',
            'type' => 'checkbox',
            'label' => 'Administrator',
        ]);
    expect($isAdmin['rules'])->toContain('boolean');
});

// --- filters() ---

it('returns filter definitions', function () {
    $filters = $this->resource->filters();

    expect($filters)->toBeArray()->toHaveCount(2);
});

it('includes a search filter', function () {
    $filters = collect($this->resource->filters());
    $search = $filters->firstWhere('name', 'search');

    expect($search)->toMatchArray([
        'name' => 'search',
        'type' => 'search',
        'placeholder' => 'Search users...',
    ]);
});

it('includes an admin_status select filter', function () {
    $filters = collect($this->resource->filters());
    $status = $filters->firstWhere('name', 'admin_status');

    expect($status['type'])->toBe('select');
    expect($status['label'])->toBe('Role');

    $optionValues = array_column($status['options'], 'value');
    expect($optionValues)->toBe(['', 'admin', 'non-admin']);

    $optionLabels = array_column($status['options'], 'label');
    expect($optionLabels)->toBe(['All', 'Admins', 'Non-Admins']);
});

// --- tableColumns() ---

it('returns table column definitions', function () {
    $columns = $this->resource->tableColumns();

    expect($columns)->toBeArray()->toHaveCount(5);

    $columnNames = array_column($columns, 'name');
    expect($columnNames)->toBe(['id', 'name', 'email', 'is_admin', 'created_at']);
});

it('marks id, name, email, and created_at as sortable', function () {
    $columns = collect($this->resource->tableColumns());

    $sortableColumns = $columns->filter(fn ($col) => $col['sortable'] ?? false)->pluck('name')->values()->all();
    expect($sortableColumns)->toBe(['id', 'name', 'email', 'created_at']);
});

it('does not mark is_admin as sortable', function () {
    $columns = collect($this->resource->tableColumns());
    $isAdmin = $columns->firstWhere('name', 'is_admin');

    expect($isAdmin)->not->toHaveKey('sortable');
});

// --- perPage() ---

it('returns a default perPage value', function () {
    expect($this->resource->perPage())->toBe(15);
});

// --- storeRules() ---

it('generates store rules with required password and unique email', function () {
    $rules = $this->resource->storeRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('password');
    expect($rules)->toHaveKey('is_admin');

    // Password must be required for store
    expect($rules['password'])->toContain('required');
    expect($rules['password'])->toContain('min:8');
    expect($rules['password'])->toContain('confirmed');

    // Email must have unique rule
    $emailRules = $rules['email'];
    $hasUnique = collect($emailRules)->contains(fn ($rule) => is_string($rule) ? str_contains($rule, 'unique') : $rule instanceof \Illuminate\Validation\Rules\Unique);
    expect($hasUnique)->toBeTrue();
});

// --- updateRules() ---

it('generates update rules with nullable password and unique email ignoring self', function () {
    $rules = $this->resource->updateRules(42);

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('password');

    // Password should be nullable for update
    expect($rules['password'])->toContain('nullable');
    expect($rules['password'])->not->toContain('required');

    // Email should have unique rule ignoring user 42
    $emailRules = $rules['email'];
    $uniqueRule = collect($emailRules)->first(fn ($rule) => $rule instanceof \Illuminate\Validation\Rules\Unique);
    expect($uniqueRule)->not->toBeNull();
});

// --- validationMessages() ---

it('returns custom validation messages', function () {
    $messages = $this->resource->validationMessages();

    expect($messages)->toBeArray();
    expect($messages)->toHaveKey('email.unique');
    expect($messages['email.unique'])->toBeString();
});
