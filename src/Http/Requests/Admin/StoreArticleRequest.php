<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use JonesRussell\NorthCloud\Admin\ArticleResource;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policyClass = config('northcloud.admin.policy');

        if ($policyClass) {
            return app($policyClass)->viewAdmin($this->user());
        }

        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return app(ArticleResource::class)->storeRules();
    }

    public function messages(): array
    {
        return app(ArticleResource::class)->validationMessages();
    }
}
