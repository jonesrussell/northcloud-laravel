<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Http\Requests\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use JonesRussell\NorthCloud\Admin\UserResource;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof Model
            ? $user->id
            : (int) $user;

        return app(UserResource::class)->updateRules($userId);
    }

    public function messages(): array
    {
        return app(UserResource::class)->validationMessages();
    }
}
