<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use JonesRussell\NorthCloud\Admin\UserResource;
use JonesRussell\NorthCloud\Http\Requests\Admin\StoreUserRequest;
use JonesRussell\NorthCloud\Http\Requests\Admin\UpdateUserRequest;

class UserController extends Controller
{
    protected UserResource $resource;

    protected string $userModel;

    public function __construct()
    {
        $this->resource = app(UserResource::class);
        $this->userModel = config('auth.providers.users.model');
    }

    public function index(Request $request): Response
    {
        $viewPrefix = config('northcloud.users.views.prefix', 'dashboard/users');

        $query = $this->indexQuery();

        $query->when($request->admin_status, function ($q, $status) {
            return $status === 'admin'
                ? $q->where('is_admin', true)
                : $q->where('is_admin', false);
        })
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->when($request->sort, fn ($q) => $q->orderBy($request->sort, $request->direction ?? 'desc'),
                fn ($q) => $q->latest('created_at')
            );

        $users = $query->paginate($this->resource->perPage())->withQueryString();

        $userModel = $this->userModel;

        return Inertia::render("{$viewPrefix}/Index", [
            'users' => $users,
            'filters' => $request->only(['admin_status', 'search', 'sort', 'direction']),
            'stats' => [
                'total' => $userModel::count(),
                'admins' => $userModel::where('is_admin', true)->count(),
                'non_admins' => $userModel::where('is_admin', false)->count(),
            ],
            'fields' => $this->resource->fields(),
            'filterDefinitions' => $this->resource->filters(),
            'columns' => $this->resource->tableColumns(),
        ]);
    }

    public function create(): Response
    {
        $viewPrefix = config('northcloud.users.views.prefix', 'dashboard/users');

        return Inertia::render("{$viewPrefix}/Create", [
            'fields' => $this->resource->fields(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Hash the password before creating
        $data['password'] = Hash::make($data['password']);

        $user = $this->userModel::create($data);

        $this->afterStore($user, $request);

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', 'User created successfully.');
    }

    public function show($user): Response
    {
        $viewPrefix = config('northcloud.users.views.prefix', 'dashboard/users');

        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        return Inertia::render("{$viewPrefix}/Show", [
            'user' => $user,
            'fields' => $this->resource->fields(),
        ]);
    }

    public function edit($user): Response
    {
        $viewPrefix = config('northcloud.users.views.prefix', 'dashboard/users');

        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        return Inertia::render("{$viewPrefix}/Edit", [
            'user' => $user,
            'fields' => $this->resource->fields(),
            'isSelf' => $user->id === auth()->id(),
        ]);
    }

    public function update(UpdateUserRequest $request, $user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        $data = $request->validated();

        // Self-protection: silently remove is_admin if updating self
        if ($user->id === auth()->id()) {
            unset($data['is_admin']);
        }

        // Handle optional password
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Remove password_confirmation from data
        unset($data['password_confirmation']);

        $user->update($data);

        $this->afterUpdate($user, $request);

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', 'User updated successfully.');
    }

    public function destroy($user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        // Self-protection: cannot delete yourself
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot delete your own account.');
        }

        $user->delete();

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', 'User deleted successfully.');
    }

    public function toggleAdmin($user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        // Self-protection: cannot toggle own admin status
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot change your own admin status.');
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        $status = $user->is_admin ? 'granted' : 'revoked';
        $message = "Admin access {$status} for {$user->name}.";

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        // Self-protection: filter out current user
        $ids = array_filter($request->ids, fn ($id) => $id !== auth()->id());

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        if (empty($ids)) {
            return to_route($routeName)->with('warning', 'You cannot delete your own account.');
        }

        $this->userModel::whereIn('id', $ids)->delete();

        $count = count($ids);
        $message = $count === 1
            ? 'User deleted successfully.'
            : "{$count} users deleted successfully.";

        return to_route($routeName)->with('success', $message);
    }

    public function bulkToggleAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_admin' => 'required|boolean',
        ]);

        // Self-protection: filter out current user
        $ids = array_filter($request->ids, fn ($id) => $id !== auth()->id());

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        if (empty($ids)) {
            return to_route($routeName)->with('warning', 'You cannot change your own admin status.');
        }

        $this->userModel::whereIn('id', $ids)->update(['is_admin' => $request->boolean('is_admin')]);

        $count = count($ids);
        $action = $request->boolean('is_admin') ? 'granted' : 'revoked';
        $message = $count === 1
            ? "Admin access {$action} for 1 user."
            : "Admin access {$action} for {$count} users.";

        return to_route($routeName)->with('success', $message);
    }

    // --- Extension hooks ---

    protected function indexQuery(): Builder
    {
        return $this->userModel::query();
    }

    protected function afterStore(Model $user, Request $request): void
    {
        //
    }

    protected function afterUpdate(Model $user, Request $request): void
    {
        //
    }
}
