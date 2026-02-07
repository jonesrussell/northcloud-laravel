<?php

namespace JonesRussell\NorthCloud\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use JonesRussell\NorthCloud\Admin\ArticleResource;

class UpdateArticleRequest extends FormRequest
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
        $article = $this->route('article');
        $articleId = $article instanceof \Illuminate\Database\Eloquent\Model
            ? $article->id
            : (int) $article;

        return app(ArticleResource::class)->updateRules($articleId);
    }

    public function messages(): array
    {
        return app(ArticleResource::class)->validationMessages();
    }
}
