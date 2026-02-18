<?php

namespace JonesRussell\NorthCloud\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use JonesRussell\NorthCloud\Admin\ArticleResource;
use JonesRussell\NorthCloud\Http\Requests\Admin\StoreArticleRequest;
use JonesRussell\NorthCloud\Http\Requests\Admin\UpdateArticleRequest;

class ArticleController extends Controller
{
    protected ArticleResource $resource;

    protected string $articleModel;

    public function __construct()
    {
        $this->resource = app(ArticleResource::class);
        $this->articleModel = config('northcloud.models.article');
    }

    public function index(Request $request): Response
    {
        $viewPrefix = config('northcloud.admin.views.prefix', 'dashboard/articles');

        $query = $this->indexQuery();

        $query->when($request->status, fn ($q) => $request->status === 'draft'
            ? $q->whereNull('published_at')
            : $q->whereNotNull('published_at')
        )
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->when($request->sort, fn ($q) => $q->orderBy($request->sort, $request->direction ?? 'desc'),
                fn ($q) => $q->latest('created_at')
            );

        $articles = $query->paginate($this->resource->perPage())->withQueryString();

        $articleModel = $this->articleModel;

        return Inertia::render("{$viewPrefix}/Index", [
            'articles' => $articles,
            'filters' => $request->only(['status', 'search', 'tag', 'source', 'sort', 'direction']),
            'stats' => [
                'total' => $articleModel::count(),
                'drafts' => $articleModel::whereNull('published_at')->count(),
                'published' => $articleModel::whereNotNull('published_at')->count(),
            ],
            'fields' => $this->resource->fields(),
            'filterDefinitions' => $this->resource->filters(),
            'columns' => $this->resource->tableColumns(),
            'relationOptions' => $this->resource->resolveRelationOptions(),
        ]);
    }

    public function create(): Response
    {
        $viewPrefix = config('northcloud.admin.views.prefix', 'dashboard/articles');

        return Inertia::render("{$viewPrefix}/Create", [
            'fields' => $this->resource->fields(),
            'relationOptions' => $this->resource->resolveRelationOptions(),
            'articleableOptions' => $this->getArticleableOptions(),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Separate relation data from scalar data
        $relations = $this->extractRelations($data);

        $article = $this->articleModel::create($data);

        $this->syncRelations($article, $relations);
        $this->syncArticleable($article, $request);
        $this->afterStore($article, $request);

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', 'Article created successfully.');
    }

    public function show($article): Response
    {
        $viewPrefix = config('northcloud.admin.views.prefix', 'dashboard/articles');

        if (! $article instanceof Model) {
            $article = $this->articleModel::findOrFail($article);
        }

        $article->load(['newsSource', 'tags']);

        return Inertia::render("{$viewPrefix}/Show", [
            'article' => $article,
            'fields' => $this->resource->fields(),
        ]);
    }

    public function edit($article): Response
    {
        $viewPrefix = config('northcloud.admin.views.prefix', 'dashboard/articles');

        if (! $article instanceof Model) {
            $article = $this->articleModel::findOrFail($article);
        }

        $article->load(['newsSource', 'tags']);

        return Inertia::render("{$viewPrefix}/Edit", [
            'article' => $article,
            'fields' => $this->resource->fields(),
            'relationOptions' => $this->resource->resolveRelationOptions(),
            'articleableOptions' => $this->getArticleableOptions(),
        ]);
    }

    public function update(UpdateArticleRequest $request, $article): RedirectResponse
    {
        if (! $article instanceof Model) {
            $article = $this->articleModel::findOrFail($article);
        }

        $data = $request->validated();
        $relations = $this->extractRelations($data);

        $article->update($data);

        $this->syncRelations($article, $relations);
        $this->syncArticleable($article, $request);
        $this->afterUpdate($article, $request);

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', 'Article updated successfully.');
    }

    public function destroy($article): RedirectResponse
    {
        if (! $article instanceof Model) {
            $article = $this->articleModel::findOrFail($article);
        }

        $article->delete();

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', 'Article deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $this->articleModel::whereIn('id', $request->ids)->delete();

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article deleted successfully.'
            : "{$count} articles deleted successfully.";

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function bulkPublish(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $this->articleModel::whereIn('id', $request->ids)->update(['published_at' => now()]);

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article published successfully.'
            : "{$count} articles published successfully.";

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function bulkUnpublish(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $this->articleModel::whereIn('id', $request->ids)->update(['published_at' => null]);

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article unpublished successfully.'
            : "{$count} articles unpublished successfully.";

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function togglePublish($article): RedirectResponse
    {
        if (! $article instanceof Model) {
            $article = $this->articleModel::findOrFail($article);
        }

        if ($article->published_at) {
            $article->update(['published_at' => null]);
            $message = 'Article unpublished successfully.';
        } else {
            $article->update(['published_at' => now()]);
            $message = 'Article published successfully.';
        }

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function searchAssociatable(Request $request): JsonResponse
    {
        if (! config('northcloud.articleable.enabled', false)) {
            abort(404);
        }

        $request->validate([
            'model' => 'required|string',
            'q' => 'nullable|string|max:255',
        ]);

        $modelClass = $request->input('model');
        $models = config('northcloud.articleable.models', []);

        if (! isset($models[$modelClass]) || ! class_exists($modelClass)) {
            abort(422, 'Invalid model type.');
        }

        $config = $models[$modelClass];
        $query = $modelClass::query();

        if ($q = $request->input('q')) {
            $searchColumns = $config['search'] ?? ['name'];
            $query->where(function ($qb) use ($searchColumns, $q) {
                foreach ($searchColumns as $col) {
                    $qb->orWhere($col, 'LIKE', "%{$q}%");
                }
            });
        }

        $displayField = $config['display'] ?? 'name';

        return response()->json(
            $query->select(['id', $displayField])
                ->orderBy($displayField)
                ->limit(20)
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->{$displayField},
                ])
        );
    }

    public function trashed(Request $request): Response
    {
        $viewPrefix = config('northcloud.admin.views.prefix', 'dashboard/articles');
        $newsSourceModel = config('northcloud.models.news_source');

        $articles = $this->articleModel::onlyTrashed()
            ->with(['newsSource', 'tags'])
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->when($request->sort, fn ($q) => $q->orderBy($request->sort, $request->direction ?? 'desc'),
                fn ($q) => $q->latest('deleted_at')
            )
            ->paginate($this->resource->perPage())
            ->withQueryString();

        return Inertia::render("{$viewPrefix}/Trashed", [
            'articles' => $articles,
            'filters' => $request->only(['search', 'source', 'sort', 'direction']),
            'newsSources' => $newsSourceModel::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'trashed' => $this->articleModel::onlyTrashed()->count(),
                'active' => $this->articleModel::count(),
            ],
            'columns' => $this->resource->tableColumns(),
            'filterDefinitions' => $this->resource->filters(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $article = $this->articleModel::onlyTrashed()->findOrFail($id);
        $article->restore();

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'trashed';

        return to_route($routeName)->with('success', 'Article restored successfully.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = $this->articleModel::onlyTrashed()->whereIn('id', $request->ids)->restore();

        $message = $count === 1
            ? 'Article restored successfully.'
            : "{$count} articles restored successfully.";

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'trashed';

        return to_route($routeName)->with('success', $message);
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $article = $this->articleModel::onlyTrashed()->findOrFail($id);
        $article->forceDelete();

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'trashed';

        return to_route($routeName)->with('success', 'Article permanently deleted.');
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = $this->articleModel::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();

        $message = $count === 1
            ? 'Article permanently deleted.'
            : "{$count} articles permanently deleted.";

        $routeName = config('northcloud.admin.name_prefix', 'dashboard.articles.').'trashed';

        return to_route($routeName)->with('success', $message);
    }

    // --- Extension hooks ---

    protected function indexQuery(): Builder
    {
        return $this->articleModel::query()->with(['newsSource', 'tags']);
    }

    protected function afterStore(Model $article, Request $request): void
    {
        //
    }

    protected function afterUpdate(Model $article, Request $request): void
    {
        //
    }

    // --- Helpers ---

    private function extractRelations(array &$data): array
    {
        $relations = [];

        foreach ($this->resource->fields() as $field) {
            if ($field['type'] === 'belongs-to-many' && isset($data[$field['name']])) {
                $relations[$field['name']] = $data[$field['name']];
                unset($data[$field['name']]);
            }
        }

        return $relations;
    }

    private function syncRelations(Model $article, array $relations): void
    {
        foreach ($relations as $name => $ids) {
            $field = collect($this->resource->fields())->firstWhere('name', $name);
            if ($field && isset($field['relationship'])) {
                $article->{$field['relationship']}()->sync($ids);
            }
        }
    }

    private function syncArticleable(Model $article, Request $request): void
    {
        if (! config('northcloud.articleable.enabled', false)) {
            return;
        }

        if ($request->has('articleable_type') && $request->has('articleable_id')) {
            $article->update([
                'articleable_type' => $request->input('articleable_type') ?: null,
                'articleable_id' => $request->input('articleable_id') ?: null,
            ]);
        }
    }

    /** @return array<int, array{model: string, label: string, display: string}> */
    private function getArticleableOptions(): array
    {
        if (! config('northcloud.articleable.enabled', false)) {
            return [];
        }

        $options = [];
        foreach (config('northcloud.articleable.models', []) as $modelClass => $config) {
            $options[] = [
                'model' => $modelClass,
                'label' => $config['label'] ?? class_basename($modelClass),
                'display' => $config['display'] ?? 'name',
            ];
        }

        return $options;
    }
}
