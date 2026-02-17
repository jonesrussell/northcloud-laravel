# User Management for Admin Dashboard

## Problem

Admin users across sites using northcloud-laravel (movies-of-war, streetcode, etc.) need to manage users through the dashboard. Currently, user management is CLI-only via artisan commands (`user:make-admin`, `user:change-password`).

## Approach

Mirror the existing article admin pattern (UserResource + UserController + Vue pages) in northcloud-laravel so all consuming sites get user management automatically.

## Architecture

```
IsAdministrator Trait (consuming app's User model)
    provides: isAdmin(), scopeAdmin(), scopeNonAdmin(), auto-casts
        |
UserResource (field/filter/column/validation definitions)
        |
UserController (CRUD + bulk actions + toggle-admin)
        |
Vue Pages (Index, Create, Edit, Show) -- publishable
        |
Vue Components (UsersTable, UserForm) -- publishable
```

User model resolved via `config('auth.providers.users.model')`.

## IsAdministrator Trait

Applied to consuming app's User model. Provides:

- `isAdmin(): bool` -- replaces raw `$user->is_admin` checks
- `scopeAdmin(Builder $query): Builder`
- `scopeNonAdmin(Builder $query): Builder`
- `initializeIsAdministrator()` -- auto-merges `is_admin => 'boolean'` cast

The `EnsureUserIsAdmin` middleware updated to use `$user->isAdmin()`.

## UserResource

### Fields

| Field    | Type     | Create          | Edit                   |
|----------|----------|-----------------|------------------------|
| name     | text     | required        | required               |
| email    | email    | required,unique | required,unique(ignore) |
| password | password | required,min:8  | optional (if changing) |
| is_admin | checkbox | optional        | optional               |

### Filters

- Search (name or email)
- Admin status (All / Admin / Non-Admin)

### Table Columns

ID (sortable), Name (sortable), Email (sortable), Admin (badge), Created (sortable)

## UserController

### Routes

| Method        | Route                                    | Notes                      |
|---------------|------------------------------------------|----------------------------|
| index         | GET /dashboard/users                     | List, filter, paginate     |
| create        | GET /dashboard/users/create              | Create form                |
| store         | POST /dashboard/users                    | Hash password              |
| show          | GET /dashboard/users/{user}              | View details               |
| edit          | GET /dashboard/users/{user}/edit         | Edit form                  |
| update        | PUT /dashboard/users/{user}              | Optional password change   |
| destroy       | DELETE /dashboard/users/{user}           | Cannot delete self         |
| toggleAdmin   | POST /dashboard/users/{user}/toggle-admin| Cannot toggle self         |
| bulkDelete    | POST /dashboard/users/bulk-delete        | Filters out current user   |
| bulkToggleAdmin| POST /dashboard/users/bulk-toggle-admin | Filters out current user   |

### Self-Protection Rules

- Cannot delete own account via dashboard
- Cannot toggle own admin status
- Bulk operations filter out current user's ID
- Returns 403 with message when self-action attempted

### Extension Hooks

- `indexQuery(): Builder` -- override in consuming apps
- `afterStore(Model $user, Request $request): void`
- `afterUpdate(Model $user, Request $request): void`

## Configuration

New `northcloud.users` block in `config/northcloud.php`:

```php
'users' => [
    'enabled' => true,
    'middleware' => ['web', 'auth', 'northcloud-admin'],
    'prefix' => 'dashboard/users',
    'name_prefix' => 'dashboard.users.',
    'resource' => UserResource::class,
    'controller' => UserController::class,
    'views' => ['prefix' => 'dashboard/users'],
],
```

Navigation item "Users" added to `northcloud.navigation.items`.

## Frontend

### Vue Pages (publishable to `resources/js/pages/dashboard/users/`)

**Index.vue** -- Stats row (Total, Admins, Non-Admins), FiltersBar, BulkActionBar, UsersTable, Pagination, DeleteConfirmDialog.

**Create.vue** -- UserForm: name, email, password + confirm, is_admin checkbox.

**Edit.vue** -- UserForm: name, email, optional password, is_admin toggle. Disables admin toggle when editing self.

**Show.vue** -- Read-only: name, email, admin status, created/updated, 2FA status.

### Vue Components (publishable to `resources/js/components/admin/`)

**UsersTable.vue** -- Checkbox selection, sortable columns, row actions (View, Edit, Toggle Admin, Delete). Hides destructive actions for current user's row.

**UserForm.vue** -- Renders fields from UserResource. Password with confirmation. Uses Inertia `<Form>` component.

### Reused Components (no changes needed)

- `FiltersBar.vue` -- driven by filter definitions
- `StatCard.vue` -- generic
- `DeleteConfirmDialog.vue` -- generic

### BulkActionBar Extension

Add support for custom actions (toggle-admin) without modifying the existing article-focused actions.

## Testing (Pest)

### Feature Tests

**UserManagementTest:**
- Admin can list, create, view, update, delete users
- Filtering by search and admin status works
- Pagination works
- Non-admin gets 403
- Guest redirected to login
- Email uniqueness enforced
- Password validation (min:8, confirmed)

**UserSelfProtectionTest:**
- Admin cannot delete self
- Admin cannot toggle own admin
- Bulk delete/toggle filters out current user
- Returns appropriate error messages

**UserBulkActionsTest:**
- Bulk delete with valid IDs
- Bulk toggle-admin works
- Empty IDs validation error

### Unit Tests

**IsAdministratorTraitTest:**
- `isAdmin()` returns correct boolean
- Scopes filter correctly
- Cast auto-applied

## Migrations

No new migrations. The `is_admin` column migration already exists in the package.

## Not In Scope

- Role-based access control (RBAC) -- staying with `is_admin` boolean
- Soft deletes for users -- deletion is permanent with confirmation
- User activity logs
- User impersonation
