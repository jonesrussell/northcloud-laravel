# User Management Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add admin user management (CRUD + toggle admin + bulk actions) to northcloud-laravel so all consuming sites get it automatically.

**Architecture:** Mirror the existing article admin pattern with `IsAdministrator` trait, `UserResource`, `UserController`, publishable Vue pages/components. User model resolved via `config('auth.providers.users.model')`. Self-protection prevents admins from deleting/demoting themselves.

**Tech Stack:** Laravel 12, Pest 4, Vue 3, Inertia v2, Tailwind CSS v4, Orchestra Testbench

**Design Doc:** `docs/plans/2026-02-17-user-management-design.md`

---

### Task 1: Test Infrastructure - User Fixture Model

Tests need a User model with the trait applied. The package doesn't own a User model, so we create a test fixture.

**Files:**
- Create: `tests/Fixtures/User.php`
- Modify: `tests/TestCase.php`

**Step 1: Create test User fixture**

```php
// tests/Fixtures/User.php
<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use JonesRussell\NorthCloud\Concerns\IsAdministrator;

class User extends Authenticatable
{
    use IsAdministrator;

    protected $guarded = [];

    protected $table = 'users';
}
```

Note: This will fail to create until the trait exists (Task 2). That's expected — just create the file.

**Step 2: Update TestCase to set up users table and auth config**

In `tests/TestCase.php`, add the users table migration and configure the auth provider to use our fixture model:

```php
// Add to defineEnvironment():
$app['config']->set('auth.providers.users.model', \JonesRussell\NorthCloud\Tests\Fixtures\User::class);

// Add to defineDatabaseMigrations():
$this->loadMigrationsFrom(__DIR__.'/../database/admin-migrations');
```

Also add a `createUsersTable()` helper that runs the standard Laravel users migration inline (since the package doesn't ship one):

```php
protected function createUsersTable(): void
{
    $this->app['db']->connection()->getSchemaBuilder()->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```

Override `defineDatabaseMigrations()` to call `createUsersTable()` before loading package migrations:

```php
protected function defineDatabaseMigrations(): void
{
    $this->createUsersTable();
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    $this->loadMigrationsFrom(__DIR__.'/../database/admin-migrations');
}
```

**Step 3: Commit**

```bash
git add tests/Fixtures/User.php tests/TestCase.php
git commit -m "test: add User fixture model and auth test infrastructure"
```

---

### Task 2: IsAdministrator Trait - Tests

**Files:**
- Create: `tests/Unit/Concerns/IsAdministratorTest.php`

**Step 1: Write failing tests**

```php
// tests/Unit/Concerns/IsAdministratorTest.php
<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    // Users table + is_admin column created by TestCase
});

it('returns true for admin users via isAdmin()', function () {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    expect($user->isAdmin())->toBeTrue();
});

it('returns false for non-admin users via isAdmin()', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    expect($user->isAdmin())->toBeFalse();
});

it('casts is_admin to boolean automatically', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'is_admin' => 1,
    ]);

    expect($user->is_admin)->toBeBool()->toBeTrue();
});

it('filters admin users with scopeAdmin', function () {
    User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('pw'), 'is_admin' => true]);
    User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $admins = User::admin()->get();

    expect($admins)->toHaveCount(1);
    expect($admins->first()->email)->toBe('admin@test.com');
});

it('filters non-admin users with scopeNonAdmin', function () {
    User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('pw'), 'is_admin' => true]);
    User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $nonAdmins = User::nonAdmin()->get();

    expect($nonAdmins)->toHaveCount(1);
    expect($nonAdmins->first()->email)->toBe('user@test.com');
});

it('defaults is_admin to false', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user->fresh()->isAdmin())->toBeFalse();
});
```

**Step 2: Run tests to verify they fail**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Concerns/IsAdministratorTest.php`
Expected: FAIL — `IsAdministrator` trait not found

**Step 3: Commit test file**

```bash
git add tests/Unit/Concerns/IsAdministratorTest.php
git commit -m "test: add IsAdministrator trait unit tests (red)"
```

---

### Task 3: IsAdministrator Trait - Implementation

**Files:**
- Create: `src/Concerns/IsAdministrator.php`

**Step 1: Implement the trait**

```php
// src/Concerns/IsAdministrator.php
<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait IsAdministrator
{
    public function initializeIsAdministrator(): void
    {
        $this->mergeCasts([
            'is_admin' => 'boolean',
        ]);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    public function scopeNonAdmin(Builder $query): Builder
    {
        return $query->where('is_admin', false);
    }
}
```

**Step 2: Run tests to verify they pass**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Concerns/IsAdministratorTest.php`
Expected: All 6 tests PASS

**Step 3: Run full test suite to verify no regressions**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest`
Expected: All tests PASS

**Step 4: Commit**

```bash
git add src/Concerns/IsAdministrator.php
git commit -m "feat: add IsAdministrator trait with scopes and auto-cast"
```

---

### Task 4: Update EnsureUserIsAdmin Middleware

Update the middleware to use `isAdmin()` when available, with fallback to `is_admin` property.

**Files:**
- Modify: `src/Http/Middleware/EnsureUserIsAdmin.php`
- Create: `tests/Feature/Admin/EnsureUserIsAdminTest.php`

**Step 1: Write failing test**

```php
// tests/Feature/Admin/EnsureUserIsAdminTest.php
<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

it('allows admin users through the middleware', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard/users');

    $response->assertStatus(200);
})->skip('Routes not registered yet — will pass after Task 8');

it('blocks non-admin users with 403', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard/articles');

    $response->assertForbidden();
});

it('uses isAdmin() method when trait is applied', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);

    expect(method_exists($admin, 'isAdmin'))->toBeTrue();
    expect($admin->isAdmin())->toBeTrue();
});
```

**Step 2: Update the middleware**

In `src/Http/Middleware/EnsureUserIsAdmin.php`, update the `elseif` branch to prefer `isAdmin()`:

```php
} elseif (method_exists($request->user(), 'isAdmin')) {
    if (! $request->user()->isAdmin()) {
        abort(403, 'Unauthorized. Admin access required.');
    }
} elseif (! $request->user()?->is_admin) {
    abort(403, 'Unauthorized. Admin access required.');
}
```

**Step 3: Run tests**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Feature/Admin/EnsureUserIsAdminTest.php`
Expected: Non-skipped tests PASS

**Step 4: Commit**

```bash
git add src/Http/Middleware/EnsureUserIsAdmin.php tests/Feature/Admin/EnsureUserIsAdminTest.php
git commit -m "feat: update admin middleware to use IsAdministrator trait method"
```

---

### Task 5: UserResource Class

**Files:**
- Create: `src/Admin/UserResource.php`
- Create: `tests/Unit/Admin/UserResourceTest.php`

**Step 1: Write failing test**

```php
// tests/Unit/Admin/UserResourceTest.php
<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Admin\UserResource;

beforeEach(function () {
    $this->resource = new UserResource;
});

it('defines fields for name, email, password, is_admin', function () {
    $fields = $this->resource->fields();
    $fieldNames = array_column($fields, 'name');

    expect($fieldNames)->toContain('name', 'email', 'password', 'is_admin');
});

it('has required name and email fields', function () {
    $fields = collect($this->resource->fields());

    expect($fields->firstWhere('name', 'name')['required'])->toBeTrue();
    expect($fields->firstWhere('name', 'email')['required'])->toBeTrue();
});

it('defines search and admin_status filters', function () {
    $filters = $this->resource->filters();
    $filterNames = array_column($filters, 'name');

    expect($filterNames)->toContain('search', 'admin_status');
});

it('defines sortable table columns', function () {
    $columns = $this->resource->tableColumns();
    $sortableColumns = collect($columns)->where('sortable', true)->pluck('name')->all();

    expect($sortableColumns)->toContain('id', 'name', 'email', 'created_at');
});

it('generates store rules with required password', function () {
    $rules = $this->resource->storeRules();

    expect($rules['password'])->toContain('required');
    expect($rules['email'])->toContain('unique:users,email');
});

it('generates update rules with optional password', function () {
    $rules = $this->resource->updateRules(1);

    expect($rules['password'])->toContain('nullable');
    expect(collect($rules['email']))->toContain(
        fn ($rule) => is_object($rule) || str_contains((string) $rule, 'unique')
    );
});

it('returns validation messages', function () {
    $messages = $this->resource->validationMessages();

    expect($messages)->toHaveKey('email.unique');
});
```

**Step 2: Run tests to verify they fail**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Admin/UserResourceTest.php`
Expected: FAIL — class not found

**Step 3: Implement UserResource**

```php
// src/Admin/UserResource.php
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
            ['name' => 'password', 'type' => 'password', 'label' => 'Password', 'rules' => ['required', 'string', 'min:8', 'confirmed']],
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
                    ['value' => 'non_admin', 'label' => 'Non-Admins'],
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    public function updateRules(int $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    public function validationMessages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Admin/UserResourceTest.php`
Expected: All tests PASS

**Step 5: Commit**

```bash
git add src/Admin/UserResource.php tests/Unit/Admin/UserResourceTest.php
git commit -m "feat: add UserResource with fields, filters, columns, and validation"
```

---

### Task 6: Form Requests - StoreUserRequest & UpdateUserRequest

**Files:**
- Create: `src/Http/Requests/Admin/StoreUserRequest.php`
- Create: `src/Http/Requests/Admin/UpdateUserRequest.php`

**Step 1: Implement StoreUserRequest**

```php
// src/Http/Requests/Admin/StoreUserRequest.php
<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use JonesRussell\NorthCloud\Admin\UserResource;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policyClass = config('northcloud.admin.policy');

        if ($policyClass) {
            return app($policyClass)->viewAdmin($this->user());
        }

        return method_exists($this->user(), 'isAdmin')
            ? $this->user()->isAdmin()
            : ($this->user()?->is_admin ?? false);
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
```

**Step 2: Implement UpdateUserRequest**

```php
// src/Http/Requests/Admin/UpdateUserRequest.php
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
        $policyClass = config('northcloud.admin.policy');

        if ($policyClass) {
            return app($policyClass)->viewAdmin($this->user());
        }

        return method_exists($this->user(), 'isAdmin')
            ? $this->user()->isAdmin()
            : ($this->user()?->is_admin ?? false);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof Model ? $user->id : (int) $user;

        return app(UserResource::class)->updateRules($userId);
    }

    public function messages(): array
    {
        return app(UserResource::class)->validationMessages();
    }
}
```

**Step 3: Commit**

```bash
git add src/Http/Requests/Admin/StoreUserRequest.php src/Http/Requests/Admin/UpdateUserRequest.php
git commit -m "feat: add StoreUserRequest and UpdateUserRequest form requests"
```

---

### Task 7: UserController - Core CRUD

**Files:**
- Create: `src/Http/Controllers/Admin/UserController.php`

**Step 1: Implement the controller**

```php
// src/Http/Controllers/Admin/UserController.php
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
            return match ($status) {
                'admin' => $q->where('is_admin', true),
                'non_admin' => $q->where('is_admin', false),
                default => $q,
            };
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
            'currentUserId' => $request->user()->id,
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
        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);

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

    public function edit(Request $request, $user): Response
    {
        $viewPrefix = config('northcloud.users.views.prefix', 'dashboard/users');

        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        return Inertia::render("{$viewPrefix}/Edit", [
            'user' => $user,
            'fields' => $this->resource->fields(),
            'isSelf' => $request->user()->id === $user->id,
        ]);
    }

    public function update(UpdateUserRequest $request, $user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        $data = $request->validated();

        // Only update password if provided
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        unset($data['password_confirmation']);

        // Self-protection: ignore is_admin change for current user
        if ($request->user()->id === $user->id) {
            unset($data['is_admin']);
        }

        $user->update($data);

        $this->afterUpdate($user, $request);

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, $user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        if ($request->user()->id === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        $user->delete();

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', 'User deleted successfully.');
    }

    public function toggleAdmin(Request $request, $user): RedirectResponse
    {
        if (! $user instanceof Model) {
            $user = $this->userModel::findOrFail($user);
        }

        if ($request->user()->id === $user->id) {
            abort(403, 'You cannot change your own admin status.');
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        $status = $user->is_admin ? 'granted' : 'revoked';

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', "Admin access {$status} for {$user->name}.");
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        // Filter out current user's ID
        $ids = collect($request->ids)->reject(fn ($id) => $id === $request->user()->id)->values()->all();

        if (empty($ids)) {
            $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

            return to_route($routeName)->with('warning', 'No users were deleted. You cannot delete your own account.');
        }

        $this->userModel::whereIn('id', $ids)->delete();

        $count = count($ids);
        $message = $count === 1
            ? 'User deleted successfully.'
            : "{$count} users deleted successfully.";

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

        return to_route($routeName)->with('success', $message);
    }

    public function bulkToggleAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_admin' => 'required|boolean',
        ]);

        // Filter out current user's ID
        $ids = collect($request->ids)->reject(fn ($id) => $id === $request->user()->id)->values()->all();

        if (empty($ids)) {
            $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

            return to_route($routeName)->with('warning', 'No changes made. You cannot change your own admin status.');
        }

        $this->userModel::whereIn('id', $ids)->update(['is_admin' => $request->boolean('is_admin')]);

        $count = count($ids);
        $action = $request->boolean('is_admin') ? 'granted admin access' : 'revoked admin access';
        $message = $count === 1
            ? "User {$action} successfully."
            : "{$count} users {$action} successfully.";

        $routeName = config('northcloud.users.name_prefix', 'dashboard.users.').'index';

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
```

**Step 2: Commit**

```bash
git add src/Http/Controllers/Admin/UserController.php
git commit -m "feat: add UserController with full CRUD, toggle-admin, bulk actions, self-protection"
```

---

### Task 8: Routes & Configuration

**Files:**
- Create: `routes/users.php`
- Modify: `config/northcloud.php`
- Modify: `src/NorthCloudServiceProvider.php`

**Step 1: Create routes file**

```php
// routes/users.php
<?php

use Illuminate\Support\Facades\Route;

if (! config('northcloud.users.enabled', true)) {
    return;
}

$middleware = config('northcloud.users.middleware', ['web', 'auth', 'northcloud-admin']);
$prefix = config('northcloud.users.prefix', 'dashboard/users');
$namePrefix = config('northcloud.users.name_prefix', 'dashboard.users.');
$controller = config('northcloud.users.controller');

Route::middleware($middleware)->prefix($prefix)->name($namePrefix)->group(function () use ($controller) {
    // Bulk actions (must be before {user} routes)
    Route::post('bulk-delete', [$controller, 'bulkDelete'])->name('bulk-delete');
    Route::post('bulk-toggle-admin', [$controller, 'bulkToggleAdmin'])->name('bulk-toggle-admin');
    Route::post('{user}/toggle-admin', [$controller, 'toggleAdmin'])->name('toggle-admin');

    // Resource routes
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('create', [$controller, 'create'])->name('create');
    Route::post('/', [$controller, 'store'])->name('store');
    Route::get('{user}', [$controller, 'show'])->name('show');
    Route::get('{user}/edit', [$controller, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{user}', [$controller, 'update'])->name('update');
    Route::delete('{user}', [$controller, 'destroy'])->name('destroy');
});
```

**Step 2: Add users config block to `config/northcloud.php`**

Add before the closing `];`:

```php
'users' => [
    'enabled' => (bool) env('NORTHCLOUD_USER_MANAGEMENT', true),
    'middleware' => ['web', 'auth', 'northcloud-admin'],
    'prefix' => 'dashboard/users',
    'name_prefix' => 'dashboard.users.',
    'resource' => \JonesRussell\NorthCloud\Admin\UserResource::class,
    'controller' => \JonesRussell\NorthCloud\Http\Controllers\Admin\UserController::class,
    'views' => [
        'prefix' => 'dashboard/users',
    ],
],
```

Add "Users" to `navigation.items`:

```php
'navigation' => [
    'enabled' => true,
    'items' => [
        [
            'title' => 'Articles',
            'route' => 'dashboard.articles.index',
            'icon' => 'FileText',
        ],
        [
            'title' => 'Users',
            'route' => 'dashboard.users.index',
            'icon' => 'Users',
        ],
    ],
],
```

**Step 3: Update NorthCloudServiceProvider**

In `register()`, add deep merge for users config and register UserResource singleton:

```php
$this->deepMergeConfigKey('northcloud.users');
$this->deepMergeConfigKey('northcloud.users.views');

$this->app->singleton(\JonesRussell\NorthCloud\Admin\UserResource::class, function ($app) {
    $resourceClass = config('northcloud.users.resource', \JonesRussell\NorthCloud\Admin\UserResource::class);
    return new $resourceClass;
});
```

In `boot()`, add after the existing `loadRoutesFrom` call:

```php
$this->loadRoutesFrom(__DIR__.'/../routes/users.php');
```

In `registerPublishableAssets()`, add:

```php
$this->publishes([
    __DIR__.'/../resources/js/pages/dashboard/users' => resource_path('js/pages/dashboard/users'),
], 'northcloud-user-views');

$this->publishes([
    __DIR__.'/../resources/js/components/admin/UsersTable.vue' => resource_path('js/components/admin/UsersTable.vue'),
    __DIR__.'/../resources/js/components/admin/UserForm.vue' => resource_path('js/components/admin/UserForm.vue'),
], 'northcloud-user-components');
```

**Step 4: Commit**

```bash
git add routes/users.php config/northcloud.php src/NorthCloudServiceProvider.php
git commit -m "feat: add user management routes, config, and service provider registration"
```

---

### Task 9: Feature Tests - User CRUD

**Files:**
- Create: `tests/Feature/Admin/UserManagementTest.php`

**Step 1: Write the tests**

```php
// tests/Feature/Admin/UserManagementTest.php
<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
});

// --- Authorization ---

it('redirects guests to login', function () {
    $this->get('/dashboard/users')->assertRedirect('/login');
});

it('returns 403 for non-admin users', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
    ]);

    $this->actingAs($user)->get('/dashboard/users')->assertForbidden();
});

// --- Index ---

it('lists users for admin', function () {
    $this->actingAs($this->admin)
        ->get('/dashboard/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/Index')
            ->has('users.data', 1)
            ->has('stats')
            ->has('filters')
            ->has('columns')
        );
});

it('filters users by search term', function () {
    User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    User::create(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->get('/dashboard/users?search=alice')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('filters users by admin status', function () {
    User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->get('/dashboard/users?admin_status=admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

// --- Create / Store ---

it('shows create form for admin', function () {
    $this->actingAs($this->admin)
        ->get('/dashboard/users/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/users/Create'));
});

it('creates a new user', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'new@test.com', 'is_admin' => false]);
});

it('validates required fields on create', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users', [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('validates email uniqueness on create', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users', [
            'name' => 'Duplicate',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['email']);
});

it('validates password minimum length', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users', [
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors(['password']);
});

it('hashes the password on create', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users', [
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $user = User::where('email', 'user@test.com')->first();
    expect($user->password)->not->toBe('password123');
    expect(\Illuminate\Support\Facades\Hash::check('password123', $user->password))->toBeTrue();
});

// --- Show ---

it('shows user details for admin', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->get("/dashboard/users/{$user->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/Show')
            ->has('user')
        );
});

// --- Edit / Update ---

it('shows edit form for admin', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->get("/dashboard/users/{$user->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/Edit')
            ->has('user')
            ->where('isSelf', false)
        );
});

it('marks edit form as self when editing own account', function () {
    $this->actingAs($this->admin)
        ->get("/dashboard/users/{$this->admin->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isSelf', true));
});

it('updates a user without changing password', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    $originalPassword = $user->password;

    $this->actingAs($this->admin)
        ->put("/dashboard/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'user@test.com',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->password)->toBe($originalPassword);
});

it('updates a user with new password', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('old'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->put("/dashboard/users/{$user->id}", [
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password))->toBeTrue();
});

// --- Delete ---

it('deletes a user', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->delete("/dashboard/users/{$user->id}")
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
```

**Step 2: Run tests**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Feature/Admin/UserManagementTest.php`
Expected: All tests PASS

**Step 3: Commit**

```bash
git add tests/Feature/Admin/UserManagementTest.php
git commit -m "test: add comprehensive feature tests for user management CRUD"
```

---

### Task 10: Feature Tests - Self-Protection & Bulk Actions

**Files:**
- Create: `tests/Feature/Admin/UserSelfProtectionTest.php`
- Create: `tests/Feature/Admin/UserBulkActionsTest.php`

**Step 1: Write self-protection tests**

```php
// tests/Feature/Admin/UserSelfProtectionTest.php
<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
});

it('prevents admin from deleting themselves', function () {
    $this->actingAs($this->admin)
        ->delete("/dashboard/users/{$this->admin->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

it('prevents admin from toggling their own admin status', function () {
    $this->actingAs($this->admin)
        ->post("/dashboard/users/{$this->admin->id}/toggle-admin")
        ->assertForbidden();

    $this->admin->refresh();
    expect($this->admin->is_admin)->toBeTrue();
});

it('silently filters out current user from bulk delete', function () {
    $other = User::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-delete', ['ids' => [$this->admin->id, $other->id]])
        ->assertRedirect(route('dashboard.users.index'));

    // Admin still exists, other was deleted
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    $this->assertDatabaseMissing('users', ['id' => $other->id]);
});

it('returns warning when bulk delete only contains current user', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-delete', ['ids' => [$this->admin->id]])
        ->assertRedirect(route('dashboard.users.index'))
        ->assertSessionHas('warning');
});

it('ignores is_admin change when updating own account', function () {
    $this->actingAs($this->admin)
        ->put("/dashboard/users/{$this->admin->id}", [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $this->admin->refresh();
    expect($this->admin->is_admin)->toBeTrue();
});
```

**Step 2: Write bulk actions tests**

```php
// tests/Feature/Admin/UserBulkActionsTest.php
<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Tests\Fixtures\User;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'is_admin' => true,
    ]);
});

it('bulk deletes selected users', function () {
    $user1 = User::create(['name' => 'U1', 'email' => 'u1@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    $user2 = User::create(['name' => 'U2', 'email' => 'u2@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-delete', ['ids' => [$user1->id, $user2->id]])
        ->assertRedirect(route('dashboard.users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $user1->id]);
    $this->assertDatabaseMissing('users', ['id' => $user2->id]);
});

it('validates ids are required for bulk delete', function () {
    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-delete', [])
        ->assertSessionHasErrors(['ids']);
});

it('bulk grants admin access', function () {
    $user1 = User::create(['name' => 'U1', 'email' => 'u1@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);
    $user2 = User::create(['name' => 'U2', 'email' => 'u2@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-toggle-admin', [
            'ids' => [$user1->id, $user2->id],
            'is_admin' => true,
        ])
        ->assertRedirect(route('dashboard.users.index'))
        ->assertSessionHas('success');

    expect($user1->fresh()->is_admin)->toBeTrue();
    expect($user2->fresh()->is_admin)->toBeTrue();
});

it('bulk revokes admin access', function () {
    $user1 = User::create(['name' => 'U1', 'email' => 'u1@test.com', 'password' => bcrypt('pw'), 'is_admin' => true]);

    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-toggle-admin', [
            'ids' => [$user1->id],
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    expect($user1->fresh()->is_admin)->toBeFalse();
});

it('toggles admin status for a single user', function () {
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->post("/dashboard/users/{$user->id}/toggle-admin")
        ->assertRedirect(route('dashboard.users.index'));

    expect($user->fresh()->is_admin)->toBeTrue();

    // Toggle back
    $this->actingAs($this->admin)
        ->post("/dashboard/users/{$user->id}/toggle-admin")
        ->assertRedirect(route('dashboard.users.index'));

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('filters current user from bulk toggle admin', function () {
    $other = User::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('pw'), 'is_admin' => false]);

    $this->actingAs($this->admin)
        ->post('/dashboard/users/bulk-toggle-admin', [
            'ids' => [$this->admin->id, $other->id],
            'is_admin' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    // Admin unchanged, other was updated
    expect($this->admin->fresh()->is_admin)->toBeTrue();
    expect($other->fresh()->is_admin)->toBeFalse();
});
```

**Step 2: Run all tests**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Feature/Admin/`
Expected: All tests PASS

**Step 3: Commit**

```bash
git add tests/Feature/Admin/UserSelfProtectionTest.php tests/Feature/Admin/UserBulkActionsTest.php
git commit -m "test: add self-protection and bulk action tests for user management"
```

---

### Task 11: Vue Component - UsersTable

**Files:**
- Create: `resources/js/components/admin/UsersTable.vue`

**Step 1: Create the component**

Model after `ArticlesTable.vue` at `resources/js/components/admin/ArticlesTable.vue`. Key differences:
- Columns: ID, Name, Email, Role badge, Created date
- Row actions: View, Edit, Toggle Admin, Delete
- Hide destructive actions when row is current user
- Replace article-specific column renderers with user-specific ones
- Accept `currentUserId` prop to determine self-protection

The component should use the same `Checkbox`, `Badge`, `Button` imports from `@/components/ui/`, same `ArrowDown`/`ArrowUp`/`Edit`/`Trash2` icons from `lucide-vue-next`, plus `Shield`/`ShieldOff` for admin toggle.

Interface: same `ColumnDefinition` and `PaginatedUsers` shape as articles, but with `User` instead of `Article`.

Emit events: `delete`, `update:selected`, `toggle-admin`, `sort`.

**Step 2: Commit**

```bash
git add resources/js/components/admin/UsersTable.vue
git commit -m "feat: add UsersTable admin component"
```

---

### Task 12: Vue Component - UserForm

**Files:**
- Create: `resources/js/components/admin/UserForm.vue`

**Step 1: Create the component**

Simpler than `ArticleForm.vue` — only needs text, email, password, and checkbox field types. No relation fields (belongs-to, belongs-to-many).

Props:
- `fields: FieldDefinition[]`
- `modelValue: Record<string, unknown>`
- `errors?: Record<string, string>`
- `isEdit?: boolean` — controls whether password is optional
- `isSelf?: boolean` — disables is_admin checkbox when editing self

Field rendering:
- `text` → `Input` with type="text"
- `email` → `Input` with type="email"
- `password` → `Input` with type="password" + password_confirmation field. Label shows "(optional)" when `isEdit` is true.
- `checkbox` → `Checkbox` component, disabled when `isSelf` and field name is `is_admin`

Uses same `Card`, `CardContent`, `Input`, `Label`, `Checkbox` from `@/components/ui/`.

**Step 2: Commit**

```bash
git add resources/js/components/admin/UserForm.vue
git commit -m "feat: add UserForm admin component"
```

---

### Task 13: Vue Page - Index

**Files:**
- Create: `resources/js/pages/dashboard/users/Index.vue`

**Step 1: Create the page**

Model after `resources/js/pages/dashboard/articles/Index.vue`. Key differences:
- Title: "Users" instead of "Articles"
- Stats: Total Users, Admins, Non-Admins (using `Users`, `ShieldCheck`, `User` icons from lucide)
- Uses `UsersTable` instead of `ArticlesTable`
- BulkActionBar: `mode="users"` — needs "Toggle Admin" and "Delete" actions (not publish/unpublish)
- No article-specific publish/unpublish handlers
- Add `handleToggleAdmin(user)` and `handleBulkToggleAdmin(isAdmin: boolean)`
- Pass `currentUserId` prop to `UsersTable`

For BulkActionBar, since the existing component only supports article modes, create inline bulk action buttons directly in the page template instead of modifying the shared component. Use a simple Card with buttons matching the BulkActionBar style.

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/users/Index.vue
git commit -m "feat: add users Index page with filters, table, and bulk actions"
```

---

### Task 14: Vue Page - Create

**Files:**
- Create: `resources/js/pages/dashboard/users/Create.vue`

**Step 1: Create the page**

Model after `resources/js/pages/dashboard/articles/Create.vue`. Differences:
- Uses `UserForm` instead of `ArticleForm`
- Pass `isEdit: false` to form
- No publish/draft buttons — single "Create User" submit button
- Breadcrumbs: Dashboard > Users > Create

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/users/Create.vue
git commit -m "feat: add users Create page"
```

---

### Task 15: Vue Page - Edit

**Files:**
- Create: `resources/js/pages/dashboard/users/Edit.vue`

**Step 1: Create the page**

Model after `resources/js/pages/dashboard/articles/Edit.vue`. Differences:
- Uses `UserForm` instead of `ArticleForm`
- Pass `isEdit: true` and `isSelf` prop to form
- No publish/unpublish buttons — single "Save Changes" button
- Show metadata card: Created, Updated, Admin status badge
- Delete button in header (hidden when `isSelf`)
- Show "You are editing your own account" warning when `isSelf`

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/users/Edit.vue
git commit -m "feat: add users Edit page with self-protection"
```

---

### Task 16: Vue Page - Show

**Files:**
- Create: `resources/js/pages/dashboard/users/Show.vue`

**Step 1: Create the page**

Model after `resources/js/pages/dashboard/articles/Show.vue`. Differences:
- Show user fields: Name, Email, Admin status (badge), Created, Updated
- Show 2FA status if `two_factor_confirmed_at` is available
- Edit button in header
- No article-specific content/URL/image sections

**Step 2: Commit**

```bash
git add resources/js/pages/dashboard/users/Show.vue
git commit -m "feat: add users Show page"
```

---

### Task 17: Run Full Test Suite & Fix Issues

**Step 1: Run all tests**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pest`
Expected: All tests PASS

**Step 2: Run pint**

Run: `cd /home/jones/dev/northcloud-laravel && vendor/bin/pint --dirty`

**Step 3: Fix any issues found**

**Step 4: Commit if changes were needed**

```bash
git add -A
git commit -m "fix: address test failures and code style issues"
```

---

### Task 18: Update Consuming App - movies-of-war

Apply the trait to the User model and publish the views.

**Files:**
- Modify: `/home/jones/dev/movies-of-war.com/app/Models/User.php` — add `use IsAdministrator;`
- Run: `ddev artisan vendor:publish --tag=northcloud-user-views`
- Run: `ddev artisan vendor:publish --tag=northcloud-user-components`

**Step 1: Add trait to User model**

Add `use JonesRussell\NorthCloud\Concerns\IsAdministrator;` import and `use IsAdministrator;` in the class body.

Remove the manual `'is_admin' => 'boolean'` cast if present (the trait handles it).

**Step 2: Publish views**

```bash
ddev artisan vendor:publish --tag=northcloud-user-views --force
ddev artisan vendor:publish --tag=northcloud-user-components --force
```

**Step 3: Run existing tests**

Run: `ddev artisan test --compact`
Expected: All existing tests PASS

**Step 4: Commit**

```bash
cd /home/jones/dev/movies-of-war.com
git add app/Models/User.php resources/js/pages/dashboard/users/ resources/js/components/admin/UsersTable.vue resources/js/components/admin/UserForm.vue
git commit -m "feat: integrate northcloud user management with IsAdministrator trait"
```

---

### Task 19: Update Consuming App - streetcode-laravel

Same as Task 18 but for streetcode.

**Files:**
- Modify: `/home/jones/dev/streetcode-laravel/app/Models/User.php` — add `use IsAdministrator;`

**Step 1-4:** Same steps as Task 18 but in the streetcode directory.

---

### Task 20: Manual Verification

**Step 1: Start dev server**

```bash
cd /home/jones/dev/movies-of-war.com && ddev composer dev
```

**Step 2: Verify in browser**

- Navigate to `/dashboard/users` as admin — should see users list
- Create a new user — verify it appears
- Edit a user — change name, verify update
- Toggle admin status — verify badge changes
- Try to delete/toggle-admin yourself — should be prevented
- Delete a non-admin user — verify removal
- Verify navigation shows "Users" link

**Step 3: Run full test suite one more time**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest
cd /home/jones/dev/movies-of-war.com && ddev artisan test --compact
```
