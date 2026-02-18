<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Admin;

use Illuminate\Validation\Rule;

class UserResource
{
    public function fields(): array
    {
        return [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'rules' => ['required', 'string', 'email', 'max:255']],
            ['name' => 'password', 'type' => 'password', 'label' => 'Password', 'rules' => ['string', 'min:8', 'confirmed']],
            ['name' => 'is_admin', 'type' => 'checkbox', 'label' => 'Administrator', 'rules' => ['boolean']],
        ];
    }

    public function filters(): array
    {
        return [
            ['name' => 'search', 'type' => 'search', 'placeholder' => 'Search users...'],
            ['name' => 'admin_status', 'type' => 'select', 'label' => 'Role',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'admin', 'label' => 'Admins'],
                    ['value' => 'non-admin', 'label' => 'Non-Admins'],
                ]],
        ];
    }

    public function tableColumns(): array
    {
        return [
            ['name' => 'id', 'label' => 'ID', 'sortable' => true],
            ['name' => 'name', 'label' => 'Name', 'sortable' => true],
            ['name' => 'email', 'label' => 'Email', 'sortable' => true],
            ['name' => 'is_admin', 'label' => 'Role'],
            ['name' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
    }

    public function perPage(): int
    {
        return 15;
    }

    public function storeRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if ($field['name'] === 'email') {
                $rules['email'] = array_merge($field['rules'], ['unique:users,email']);

                continue;
            }

            if ($field['name'] === 'password') {
                $rules['password'] = array_merge(['required'], $field['rules']);

                continue;
            }

            $rules[$field['name']] = $field['rules'];
        }

        return $rules;
    }

    public function updateRules(int $userId): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if ($field['name'] === 'email') {
                $rules['email'] = array_merge(
                    $field['rules'],
                    [Rule::unique('users', 'email')->ignore($userId)]
                );

                continue;
            }

            if ($field['name'] === 'password') {
                $rules['password'] = array_merge(['nullable'], array_filter(
                    $field['rules'],
                    fn ($rule) => $rule !== 'required'
                ));

                continue;
            }

            $rules[$field['name']] = $field['rules'];
        }

        return $rules;
    }

    public function validationMessages(): array
    {
        return [
            'email.unique' => 'A user with this email address already exists.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
