<?php

namespace JonesRussell\NorthCloud\Admin;

use Illuminate\Validation\Rule;

class ArticleResource
{
    public function fields(): array
    {
        return [
            ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            ['name' => 'url', 'type' => 'url', 'label' => 'URL', 'required' => true, 'rules' => ['required', 'url']],
            ['name' => 'excerpt', 'type' => 'textarea', 'label' => 'Excerpt', 'rules' => ['nullable', 'string', 'max:500']],
            ['name' => 'content', 'type' => 'richtext', 'label' => 'Content', 'required' => true, 'rules' => ['required', 'string']],
            ['name' => 'image_url', 'type' => 'url', 'label' => 'Image URL', 'rules' => ['nullable', 'url']],
            ['name' => 'author', 'type' => 'text', 'label' => 'Author', 'rules' => ['nullable', 'string', 'max:255']],
            ['name' => 'news_source_id', 'type' => 'belongs-to', 'label' => 'Source', 'required' => true,
                'rules' => ['required', 'exists:news_sources,id'], 'relationship' => 'newsSource', 'display_field' => 'name'],
            ['name' => 'tags', 'type' => 'belongs-to-many', 'label' => 'Tags',
                'rules' => ['array'], 'item_rules' => ['exists:tags,id'], 'relationship' => 'tags', 'display_field' => 'name'],
            ['name' => 'published_at', 'type' => 'datetime', 'label' => 'Published At', 'rules' => ['nullable', 'date']],
            ['name' => 'is_featured', 'type' => 'checkbox', 'label' => 'Featured', 'rules' => ['boolean']],
        ];
    }

    public function filters(): array
    {
        return [
            ['name' => 'search', 'type' => 'search', 'placeholder' => 'Search articles...'],
            ['name' => 'status', 'type' => 'select', 'label' => 'Status',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'published', 'label' => 'Published'],
                    ['value' => 'draft', 'label' => 'Drafts'],
                ]],
            ['name' => 'source', 'type' => 'belongs-to', 'label' => 'Source',
                'relationship' => 'newsSource', 'display_field' => 'name'],
        ];
    }

    public function tableColumns(): array
    {
        return [
            ['name' => 'id', 'label' => 'ID', 'sortable' => true],
            ['name' => 'title', 'label' => 'Title', 'sortable' => true],
            ['name' => 'news_source', 'label' => 'Source'],
            ['name' => 'tags', 'label' => 'Tags'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'published_at', 'label' => 'Published', 'sortable' => true],
            ['name' => 'view_count', 'label' => 'Views', 'sortable' => true],
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
            if ($field['name'] === 'url') {
                $rules['url'] = array_merge($field['rules'], ['unique:articles,url']);

                continue;
            }

            if (isset($field['item_rules'])) {
                $rules[$field['name']] = $field['rules'];
                $rules[$field['name'].'.*'] = $field['item_rules'];

                continue;
            }

            $rules[$field['name']] = $field['rules'];
        }

        return $rules;
    }

    public function updateRules(int $articleId): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if ($field['name'] === 'url') {
                $rules['url'] = array_merge(
                    $field['rules'],
                    [Rule::unique('articles', 'url')->ignore($articleId)]
                );

                continue;
            }

            if (isset($field['item_rules'])) {
                $rules[$field['name']] = $field['rules'];
                $rules[$field['name'].'.*'] = $field['item_rules'];

                continue;
            }

            $rules[$field['name']] = $field['rules'];
        }

        return $rules;
    }

    public function resolveRelationOptions(): array
    {
        $options = [];
        $newsSourceModel = config('northcloud.models.news_source');
        $tagModel = config('northcloud.models.tag');

        foreach ($this->fields() as $field) {
            if ($field['type'] === 'belongs-to' && $field['name'] === 'news_source_id') {
                $options['news_sources'] = $newsSourceModel::query()
                    ->when(method_exists($newsSourceModel, 'scopeActive'), fn ($q) => $q->active())
                    ->orderBy($field['display_field'])
                    ->get(['id', $field['display_field']]);
            } elseif ($field['type'] === 'belongs-to-many' && $field['name'] === 'tags') {
                $options['tags'] = $tagModel::query()
                    ->orderBy($field['display_field'])
                    ->get(['id', $field['display_field']]);
            }
        }

        return $options;
    }

    public function validationMessages(): array
    {
        return [
            'url.unique' => 'An article with this URL already exists.',
            'news_source_id.exists' => 'The selected news source is invalid.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
        ];
    }
}
