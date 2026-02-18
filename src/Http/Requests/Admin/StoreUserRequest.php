<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use JonesRussell\NorthCloud\Admin\UserResource;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policyClass = config('northcloud.users.policy');

        if ($policyClass) {
            return app($policyClass)->viewAdmin($this->user());
        }

        if (method_exists($this->user(), 'isAdmin')) {
            return $this->user()->isAdmin();
        }

        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return app(UserResource::class)->storeRules();
    }

    public function messages(): array
    {
        return app(UserResource::class)->validationMessages();
    }
}
