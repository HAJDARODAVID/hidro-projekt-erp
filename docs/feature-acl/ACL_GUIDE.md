# Access Control (ACL) — Manual

The new ACL decides **who may do what** in the application. It is managed in the
App control center under **`/app/acl`**, and replaces the old ACL in `/adm/acl`.
The two run side by side until every part of the app uses the new one.

This manual has two parts:

- **Part 1 — Using the ACL screen**: for the people who set up roles and give users access.
- **Part 2 — Using the ACL in code**: for developers who protect routes, buttons and actions.

---

## Contents

1. [Concepts](#1-concepts)
2. [How access is decided](#2-how-access-is-decided)
3. [Setup](#3-setup)
4. [Part 1 — Using the ACL screen](#part-1--using-the-acl-screen)
   - [4. Roles tab](#4-roles-tab)
   - [5. Permissions tab](#5-permissions-tab)
   - [6. User access tab](#6-user-access-tab)
   - [7. Import: rebuilding the ACL from the sync file](#7-import-rebuilding-the-acl-from-the-sync-file)
   - [8. Common tasks](#8-common-tasks)
5. [Part 2 — Using the ACL in code](#part-2--using-the-acl-in-code)
   - [9. Protecting routes](#9-protecting-routes)
   - [10. Checking in Blade](#10-checking-in-blade)
   - [11. Checking in PHP and Livewire](#11-checking-in-livewire-components-and-php)
   - [12. Changing the ACL from code](#12-changing-the-acl-from-code)
   - [13. Naming permissions](#13-naming-permissions)
   - [14. Caching](#14-caching)
6. [Reference](#reference)
   - [15. Database tables](#15-database-tables)
   - [16. Files](#16-files)
   - [17. Old ACL vs new ACL](#17-old-acl-vs-new-acl)
   - [18. Limitations and things to know](#18-limitations-and-things-to-know)
   - [19. Troubleshooting](#19-troubleshooting)

---

## 1. Concepts

| Term | What it is | Example |
|---|---|---|
| **Permission** | One thing a user may be allowed to do. Identified by a unique **key**. | `payroll.view`, `employees.worker-info.edit` |
| **Role** | A named set of permissions. Users get permissions by getting roles. | `HR_MANAGER` with `employees.view`, `payroll.view` |
| **User role** | A role given to a user. A user can have any number of roles. | Ana → `HR_MANAGER`, `SITE_LEAD` |
| **Override** | A per-user exception for one permission: **Allow** or **Deny**. | Ana → `payroll.edit` = Deny |
| **Bound route** | A permission can guard one module route. Users without the permission can't open it and don't see its tab. | `payroll.view` guards `getPayrollModule` |
| **Super admin** | A user of type super admin. They may do everything, and the ACL never limits them. | — |

A permission may also belong to an **app module** (from `/app/modules`). This only groups
permissions in the screens and has no effect on access.

---

## 2. How access is decided

For a given user:

```
effective permissions =  permissions of the user's ACTIVE roles
                       + permissions the user is ALLOWED by an override
                       − permissions the user is DENIED by an override
```

Rules in plain words:

1. **Super admins pass every check.** A user is a super admin if `users.type = 4` (old type)
   **or** `users.user_type = 'S-ADM'` (new type).
2. A user has a permission if **any** of their active roles has it.
3. A role that is switched off (**Active** off) gives nothing, and its users lose what it gave them.
4. An **Allow** override gives the permission even if no role has it.
5. A **Deny** override takes the permission away **even if a role gives it**. Deny always wins.
6. **Inherit** (no override) means: follow the roles.
7. A route **without** a bound permission is open to every logged-in user.

### Examples

| Roles give | Override | Result |
|---|---|---|
| ✔ | Inherit | ✔ access |
| ✘ | Inherit | ✘ no access |
| ✘ | Allow | ✔ access |
| ✔ | Deny | ✘ no access |
| ✘ | Deny | ✘ no access |
| ✔ (but role switched off) | Inherit | ✘ no access |
| any | any, user is super admin | ✔ access |

### When does a change apply?

**On the user's next request** (next page load or next click). Users don't have to log out
or go back to the home page, because every change in the ACL clears the cached access of all
users (see [Caching](#14-caching)).

---

## 3. Setup

One-time steps for each environment (local, dev, production):

### 3.1 Run the migration

```bash
php artisan migrate
```

This creates the tables `acl_permissions`, `acl_roles`, `acl_role_permission`, `acl_user_role`
and `acl_user_permission`.

### 3.2 Clear the route cache

If routes are cached (`bootstrap/cache/routes-v*.php` exists), the new `/app/acl` route
is not visible until the cache is rebuilt:

```bash
php artisan route:clear      # local
php artisan route:cache      # production, if you cache routes there
```

### 3.3 Show the ACL tab in the App control center

The tabs of the App control center come from the database (module routes), not from code.

1. Open **`/app/modules`**.
2. Select the **app-settings** module (controller `ApplicationController`).
3. Add a route with route name **`getAclModule`** and a title such as *Access control*.
4. Set it **active**.

The page now shows as a tab next to *Modules* and *Translator*. It also works without the tab
by opening `/app/acl` directly.

### 3.4 (Optional) Fill the ACL

- **From the sync file:** on dev/production the deploy installs it, or use the **sync** button.
  To make an environment match the file exactly, use **Import** (see [section 7](#7-import-rebuilding-the-acl-from-the-sync-file)).
- **From the old ACL:** run `ManageAclService::importFromLegacy()` once (see
  [Importing the old ACL](#importing-the-old-acl-code-only)).

### Who can open the ACL screen?

Everything under `/app` is limited to **super admins** (`s-admin-access-only` middleware on
`ApplicationController`).

---

# Part 1 — Using the ACL screen

Open **App control center → Access control** (`/app/acl`). The page has three tabs
(**Roles**, **Permissions**, **User access**). At the top right there are two buttons:
**export** (on local) or **sync** (on other environments), and **Import**, which rebuilds the
ACL from the sync file.
The selected tab is kept in the URL (`?tab=roles`), so a link opens the same tab.

A good order for a fresh setup is **Permissions → Roles → User access**.

## 4. Roles tab

The left side lists all roles. The right side shows the selected role.

### Create a role

1. At the top of the list, type the **Role name**, e.g. *HR manager*.
2. The **KEY** fills in from the name (`HR_MANAGER`). You can change it.
   Keys are uppercase letters, numbers and underscores. Other characters are turned into `_`.
3. Click the green **+** button.

The new role is selected right away. The role key **can't be changed** later, because code and
imports may rely on it.

### The role list

Each role shows its name, key, and two counters:

- 🔑 number of permissions in the role
- 👥 number of users with the role

A role that is switched off is shown ~~crossed out~~.

Use **Search** to filter by name or key. The trash icon clears the search.

### Edit a role

Select the role. Then:

- **Name** and **Description** save when you leave the field.
- **Active**: turn it off to take the role out of use without deleting it. Users keep the
  role assigned, but get nothing from it until you turn it back on.
- **Delete** (red trash): removes the role and takes it from every user. Users lose the
  permissions they only had through this role. You'll be asked to confirm.

### Give permissions to a role

Below the role fields is every permission, grouped by **module** (permissions without a
module are under **General**, at the end).

- Tick or untick a permission. **It saves immediately.**
- **all** / **none** next to a module name ticks or unticks every permission of that module.
- **Search** filters the list by permission key or name.

Click a selected role again to deselect it.

## 5. Permissions tab

### Create a permission

Fill in the **New permission** card and click **Save**:

| Field | Required | Notes |
|---|---|---|
| **Key** | yes | Unique, lowercase: `module.page.action`. See [Naming permissions](#13-naming-permissions). Can't be changed later. |
| **Name** | yes | What admins see, e.g. *View payroll*. |
| **Description** | no | Longer explanation. |
| **Module** | no | Groups the permission in the lists. If you pick a route and leave the module empty, the route's module is used. |
| **Guards route** | no | Bind the permission to a module route. See below. |

### Binding a permission to a route

When a permission **guards a route**:

- a user **without** the permission is sent back to the home page when they open that route, and
- the route's **tab is hidden** for them in the module header.

A route can be guarded by **one** permission only. If you pick a route that is already
bound, you'll get an error naming the permission that holds it.

The route list shows `Module / Tab title (route_name)`, and comes from the module routes
registered in `/app/modules`. Only routes registered there can be guarded from the screen.
Code can protect any route (see [Protecting routes](#9-protecting-routes)).

> **Careful:** binding a route takes access away from everyone who doesn't hold the
> permission, right away. Give the permission to the right roles **before** you bind the route,
> or do both quickly one after the other.

### Edit and delete permissions

The table lists all permissions:

- **Name** and **Description** save when you leave the field.
- **Module** and **Guards route** save when you change the selection. Choose **—** to clear them.
- **Roles** shows how many roles hold the permission.
- **Delete** (red trash) removes the permission from every role and every user. If it guarded
  a route, that route is **open to everyone** again. You'll be asked to confirm.

Filter with the **module drop-down** (*All modules*, *General* = no module, or one module)
and the **Search** field.

## 6. User access tab

The left side lists all users (search by first name, last name or e-mail). Inactive users
are greyed out. Select a user to see their access on the right.

If the user is a **super admin**, a blue notice says so. They may do everything, and the
settings below don't limit them.

### Roles

Every role is shown as a checkbox. Tick to give the user the role, untick to take it away.
**It saves immediately.** Roles that are switched off are shown ~~crossed out~~. You can
still assign them, but they give nothing while off.

### Permissions

A table of every permission, grouped by module:

| Column | Meaning |
|---|---|
| **Permission** | Name and key. |
| **From roles** | Which of the user's active roles give this permission (— = none). |
| **Override** | **Inherit**: follow the roles. **Allow**: always give it. **Deny**: never give it, whatever the roles say. Saves immediately. |
| **Access** | The final result: ✔ green = the user has it, ✘ red = they don't. |

Use **Search** to find a permission by key or name.

> Use overrides for **exceptions**. If several users need the same overrides, make a role
> for them instead. Roles are easier to keep track of.

## 7. Import: rebuilding the ACL from the sync file

The **Import** button (top right) rebuilds the ACL from the last ACL sync file
(`installers/auto-installations/app_sync_acl.json`, see
[the ACL sync file](#shipping-permissions-with-a-feature-the-acl-sync-file)). Afterwards the
permissions, roles and role permissions are **exactly** what the file holds:

| | What happens |
|---|---|
| Permission / role in the file | Created, or updated (matched by ID, then key). A role's **Active** is taken from the file. |
| Permission / role **not** in the file | **Deleted**, with its role permissions, user roles and user overrides. |
| Permissions of a role | Set to exactly the file's list. Extra ones are removed. |
| User roles and overrides of what stays | Kept. They're not in the file. |

Things to know:

- **It deletes.** You'll be asked to confirm. Export first if you want to keep what you have.
- It runs in one database transaction: if anything fails, nothing changes.
- The button is disabled when there's no sync file. A file with no permissions and no roles is
  refused, so an empty file can't wipe the ACL.
- It works on every environment. On dev/production, the **sync** button next to it is the
  safe version: it only creates, updates and adds, and never deletes.
- When it finishes, a message shows what was created, updated and deleted, plus any warnings
  (e.g. a route that doesn't exist on this server).

### Importing the old ACL (code only)

The one-time copy of the old ACL into the new one no longer has a button. It's still available
from code, e.g. in `php artisan tinker`:

```php
App\Services\Application\Acl\ManageAclService::importFromLegacy();
```

It copies the old ACL like this:

| Old (`/adm/acl`) | → | New |
|---|---|---|
| `resources` (e.g. `can-assign-roles`) | → | permissions with the **same key** (lowercased). Name = `desc_hr`, or the key if empty. |
| `role_groups` (e.g. `HR_MANAGER`) | → | roles. Key = name in uppercase with `_`, name = `name_hr`, or the name if empty. |
| `role_resources` | → | role permissions |
| `user_roles` | → | user roles |
| `special_privileges` | → | user **Allow** overrides |
| `app_module_routes.resource_id` | → | the matching permission **guards** that route (only if the permission and the route are both still free) |

Things to know:

- **Safe to run more than once.** Roles and permissions are matched by key and reused, and
  existing assignments are kept. Nothing is ever deleted or overwritten.
- It runs in one database transaction: if anything fails, nothing is imported.
- When it finishes, a message shows how many of each thing were created.
- **The old ACL keeps working.** The import doesn't change the old tables, and later
  changes in the old ACL are **not** copied over automatically. Run the import again to
  pick them up.
- Old resource keys keep their old format (`can-assign-roles`, `menu-item-…`), even
  where it doesn't follow the [naming rules](#13-naming-permissions). That's fine: the
  keys the old code uses still match.
- Importing route bindings **can lock users out** of routes, exactly like binding by hand.
  Check the **User access** tab for a few users after importing.

## 8. Common tasks

**Give a new employee access to HR**
User access → select the user → tick the **HR** role. Done.

**Take one permission away from one person, but keep their role**
User access → select the user → find the permission → Override = **Deny**.

**Give one person a single extra permission**
User access → select the user → find the permission → Override = **Allow**.

**Lock a page to some users**
1. Permissions → create e.g. `finance.payroll.view`, **Guards route** = the payroll route.
   (Or create it without the route first, then set the route as the last step.)
2. Roles → tick the permission on every role that should see the page.
3. Check a few users in **User access**.

**Temporarily take a role out of use**
Roles → select the role → turn **Active** off. Turn it on again later. Assignments are kept.

**Find out why someone can or can't do something**
User access → select the user → search the permission → see **From roles**, **Override**
and **Access**. If **Access** is ✔ but the user still can't do it, the check in that part
of the app probably still uses the old ACL (see [Troubleshooting](#19-troubleshooting)).

---

# Part 2 — Using the ACL in code

## 9. Protecting routes

### Automatically: bound routes

All **v3 routes** (the `Route::prefix('/')->middleware(['auth', 'acl'])` group in
`routes/web.php`) go through the `acl` middleware. With no parameters, it looks up the
permission bound to the current route name in the ACL screen:

- no permission bound → open
- permission bound → the user needs it

So you protect a route **without changing code**. Register it as a module route in
`/app/modules`, then bind a permission to it in **Permissions**.

### Explicitly: in the route file

For routes outside the v3 group, or to require a permission in code:

```php
// The user needs payroll.view
Route::get('/payroll', 'getPayrollModule')
    ->middleware('acl:payroll.view');

// The user needs AT LEAST ONE of them
Route::get('/report', 'index')
    ->middleware('acl:report.view,report.export');

// Laravel's own middleware works too (via the Gate, see below)
Route::get('/payroll', 'getPayrollModule')
    ->middleware('can:payroll.view');
```

On a controller, the existing `middlewareAttributes()` can be used:

```php
public function moduleConfig()
{
    $this->setMainTitle('Payroll')
        ->setTabLinks()
        ->middlewareAttributes('acl:payroll.view');
}
```

### What happens when access is denied

- Normal page request → redirect to `home`, with an `x-message` header naming the route.
- Request that expects JSON → HTTP **403**.

## 10. Checking in Blade

Every permission key is also a Laravel Gate ability, so the standard directives work:

```blade
@can('payroll.edit')
    <x-ui.btn type="suc.sm" icon="save" action="save" />
@endcan

@cannot('payroll.edit')
    <i>{{ translator('Read only') }}</i>
@endcannot

@canany(['report.view', 'report.export'])
    ...
@endcanany
```

Unlike the old `@hasRights`, `@can` is checked **every time the view is shown**, for the
user who is looking at it. It respects super admins.

> Hiding a button is not security. Always check again in the action that runs when the
> button is clicked (see the next section).

## 11. Checking in Livewire components and PHP

```php
use App\Services\Application\Acl\AclService;

// Logged-in user
AclService::current()->can('payroll.edit');                       // bool
AclService::current()->canAny('report.view', 'report.export');    // bool
AclService::current()->canRoute('getPayrollModule');              // bool: may open this route?
AclService::current()->isSuperAdmin();                            // bool
AclService::current()->permissions();                             // ['payroll.view', ...] from roles + overrides (for super admins too, but can() lets them through regardless)

// Another user (model or ID)
AclService::forUser($user)->can('payroll.edit');
AclService::forUser(15)->can('payroll.edit');

// Laravel style, via the Gate
auth()->user()->can('payroll.edit');
Gate::allows('payroll.edit');
$this->authorize('payroll.edit');   // in a controller: throws 403 when not allowed
```

### Protecting Livewire actions

**Route middleware only protects page loads.** Livewire actions (clicks, `wire:model`
updates) go to Livewire's own endpoint and **don't** pass the route's middleware again.
Check the permission in the action itself:

```php
public function save()
{
    if (!AclService::current()->can('payroll.edit')) {
        return $this->showException(translator('You are not allowed to do this!'));
    }
    // ...
}
```

or with Laravel's helper, which throws a 403:

```php
public function save()
{
    $this->authorize('payroll.edit');
    // ...
}
```

### Tabs that need a permission (`setTabs()` / `<x-ui.nav-tabs />`)

Components with `TabTrait` (every `LivewireController`) can mark a tab with a permission.
Pass an array instead of a title:

```php
$this->setTabs([
    'basic-info'   => translator('Basic info'),
    'payroll-info' => ['title' => translator('Payroll'), 'permission' => 'employees.worker-info.payroll'],
]);
```

- Users without the permission don't get the tab: it isn't rendered and can't be selected.
- `$tabs` is `#[Locked]`, and `selectTab()` / `updatedActiveTab()` only accept tabs in
  `$tabs`, so a hidden tab can't be opened from the browser either.
- If you rebuild the tabs yourself, run them through `$this->filterTabsByPermission($tabs)`.
- `<x-ui.nav-tabs />` doesn't change: it still gets `[key => title]`.
- Hiding the tab is enough for the tab's content in **this** component. If the same child
  component is used elsewhere (e.g. `worker-payroll-info` in the payroll modal), it isn't
  protected there by this.

### Gate: what is and isn't handled

The ACL hooks into the Gate with `Gate::before()` in `AppServiceProvider`:

- The ability is a **known permission key** and the user has it → allowed.
- Known key, user doesn't have it → not allowed (unless another gate or policy allows it).
- **Unknown key** → the ACL stays out, and other gates and policies decide as usual.

A typo in a key therefore fails closed: `@can('payrol.view')` is simply false.

## 12. Changing the ACL from code

**Always** go through `ManageAclService` (never write to the `acl_*` tables directly), so
the cache is cleared and the rules are checked. Validation problems are thrown as
`App\Exceptions\ErrorMessage`.

```php
use App\Services\Application\Acl\ManageAclService;

// Permissions
$p = ManageAclService::createPermission('payroll.view', 'View payroll', 'Open the payroll module', $moduleId, $routeId);
ManageAclService::updatePermission($p->id, ['name' => 'See payroll', 'route_id' => NULL]);
ManageAclService::deletePermission($p->id);

// Roles
$r = ManageAclService::createRole('HR_MANAGER', 'HR manager');
ManageAclService::updateRole($r->id, ['active' => FALSE]);
ManageAclService::setRolePermission($r->id, $p->id, TRUE);   // FALSE takes it away
ManageAclService::deleteRole($r->id);

// Users
ManageAclService::setUserRole($userId, $r->id, TRUE);         // FALSE takes it away
ManageAclService::setUserPermission($userId, $p->id, TRUE);   // Allow
ManageAclService::setUserPermission($userId, $p->id, FALSE);  // Deny
ManageAclService::setUserPermission($userId, $p->id, NULL);   // Inherit (remove override)

// Old ACL import
$stats = ManageAclService::importFromLegacy();

// Permissions grouped by module, for building lists
ManageAclService::permissionCatalog('payroll');   // optional search
```

### Shipping permissions with a feature: the ACL sync file

The ACL has a sync file of its own, **`installers/auto-installations/app_sync_acl.json`**
(`AclSyncService`, type `acl_sync`). It's built like the modules sync, but it's a separate file.

**Workflow**

1. On **local**, create or change the permissions and roles in `/app/acl`.
2. Export them: the **export** button (box with an arrow up) in the ACL header, or
   `php artisan acl:export`.
3. Review the git diff of `app_sync_acl.json` and commit it.
4. The next deploy installs it (`auto-install:check`). It runs again whenever the file's data changes.
   On dev/production you can also install it right away with the **sync** button (circle arrows).

**What's in the file:** permissions (key, name, description, module, guarded route), roles
(key, name, description, active) and the permissions of each role.

**What's not:** user roles and user overrides. Users differ between databases, so who gets
which role is decided on each server.

**Import rules**

- Permissions and roles are matched by **ID**, then by **key**. A row matched by key takes
  over the ID from the file, so the next sync matches it by ID.
- Name, description, module and guarded route come from the file.
- A role's **Active** switch is only set when the role is created. Switching a role on or off
  on the server isn't undone by the next sync.
- Role permissions are only **added**. Permissions given to a role on the server are kept,
  and a permission removed from a role on local is **not** removed on the server. Remove it
  there by hand.
- Nothing is ever deleted.
- Modules and routes are looked up by ID, then by module name and route name. If they don't
  exist on the server, the permission is saved without them and the sync shows a warning.
- If the file gives a route to a permission while another permission guards it on the server,
  the file wins and the other binding is removed (with a warning).
- The file name sorts after `app_modules_sync.json`, so on a deploy the modules and routes are
  installed first and the routes the permissions guard already exist.

**Without the sync file**, you can still create a permission in a migration or an
auto-installation file:

```php
use App\Models\Application\Acl\AclPermission;
use App\Services\Application\Acl\ManageAclService;

if (!AclPermission::where('key', 'payroll.export')->exists()) {
    ManageAclService::createPermission('payroll.export', 'Export payroll');
}
```

## 13. Naming permissions

**Format:** lowercase letters and numbers, in parts separated by `.`, `-` or `_`.
The pattern is `ManageAclService::PERMISSION_KEY_PATTERN`.

**Convention:** `<module>.<page>.<action>`, using the same names as the app module and route:

| Key | Meaning |
|---|---|
| `employees.view` | open the employees module |
| `employees.worker-info.edit` | change worker info |
| `employees.worker-info.user-rights` | change a worker's user rights |
| `finance.payroll.view` | open payroll |
| `finance.payroll.lock` | lock a payroll |
| `report.construction-sites.export` | export the construction sites report |

Common actions: `view`, `create`, `edit`, `delete`, `export`, `approve`, `lock`.

Guidelines:

- One permission per **thing someone decides about**. Don't make a permission per button
  if the buttons always go together.
- Permission keys can't be renamed in the screen. Pick them carefully.
- **Role keys:** uppercase with `_`, e.g. `HR_MANAGER`, `SITE_LEAD`.

## 14. Caching

- The permissions of each user, the list of known keys and the route → permission map are
  cached forever in the default cache store (`CACHE_DRIVER`).
- Every change made through `ManageAclService` calls `AclService::flush()`. This raises a
  global version number (`acl.version`), so every cached result is out of date at once and is
  rebuilt on the next request.
- If you ever change the `acl_*` tables directly (SQL, seeder without the service), call
  `AclService::flush()` afterwards, or run `php artisan cache:clear`.
- Old cache entries aren't deleted, they're just never read again. With the file or Redis
  cache this is harmless. A `cache:clear` now and then cleans them up.

---

# Reference

## 15. Database tables

| Table | Columns | Notes |
|---|---|---|
| `acl_permissions` | `id` (uuid), `key` (unique), `name`, `description`, `module_id` → `app_modules.id`, `route_id` → `app_module_routes.id` (unique), timestamps | A route can be guarded by one permission only. |
| `acl_roles` | `id` (uuid), `key` (unique), `name`, `description`, `active`, timestamps | |
| `acl_role_permission` | `role_id`, `permission_id` (primary key: both) | Deleted when the role or permission is deleted. |
| `acl_user_role` | `user_id` → `users.id`, `role_id` (primary key: both) | Deleted when the role is deleted. |
| `acl_user_permission` | `user_id`, `permission_id`, `allow` (1 = Allow, 0 = Deny), timestamps (primary key: user + permission) | Deleted when the permission is deleted. No row = Inherit. |

`module_id`, `route_id` and `user_id` have no foreign keys. If a module, route or user is
deleted, the ACL rows stay but no longer do anything.

## 16. Files

| File | Purpose |
|---|---|
| `database/migrations/2026_11_10_000001_create_acl_tables.php` | Tables |
| `app/Models/Application/Acl/AclPermission.php`, `AclRole.php` | Models |
| `app/Services/Application/Acl/AclService.php` | **Reading**: may this user do X? Caching, super admin. |
| `app/Services/Application/Acl/ManageAclService.php` | **Writing**: all changes, validation, old ACL import, permission catalog |
| `app/Services/Application/Acl/AclSyncService.php` | ACL sync file: export / import of permissions and roles between environments |
| `app/Console/Commands/ExportAclCommand.php` | `php artisan acl:export` |
| `installers/auto-installations/app_sync_acl.json` | The ACL sync file (generated, commit it) |
| `app/Http/Middleware/AclAccess.php` | `acl` route middleware (registered in `app/Http/Kernel.php`) |
| `app/Providers/AppServiceProvider.php` | `Gate::before` hook (permission keys as Gate abilities) |
| `app/Services/Application/GetModuleRoutesForTabLinks.php` | Hides the tabs of guarded routes |
| `app/Http/Controllers/ApplicationController.php` → `getAclModule()` | `/app/acl` page (route name `getAclModule`) |
| `app/Livewire/Modules/AppSettings/Acl.php` | Screen container: tabs and import button |
| `app/Livewire/Modules/AppSettings/Components/AclRoles.php` | Roles tab |
| `app/Livewire/Modules/AppSettings/Components/AclPermissions.php` | Permissions tab |
| `app/Livewire/Modules/AppSettings/Components/AclUsers.php` | User access tab |
| `resources/views/livewire/modules/app-settings/acl.blade.php` and `components/acl-*.blade.php` | Views |

## 17. Old ACL vs new ACL

| | Old ACL (`/adm/acl`) | New ACL (`/app/acl`) |
|---|---|---|
| When changes apply | Only after the user opens the home page (`inv_update` + session reload) | Next request |
| Where rights are stored at runtime | Session `user_rights` | Cache, shared, cleared on change |
| Blade check | `@hasRights` (evaluated **when the view compiles**, needs `view:clear`, ignores super admin) | `@can` (evaluated every time, for the current user) |
| Route protection | None (menu items are only hidden) | `acl` middleware, bound routes, `acl:` / `can:` |
| Deny a right to one user | Not possible | Deny override |
| Switch a role off | Not possible (delete only) | **Active** switch |
| Super admin | `type == 4` in some places only | `type == 4` or `user_type == 'S-ADM'`, everywhere |
| PHP check | `app('user_rights')->hasRight('x')` | `AclService::current()->can('x')` or `$user->can('x')` |

### Moving code from the old ACL to the new

| Old | New |
|---|---|
| `@hasRights('x') … @endHasRights` | `@can('x') … @endcan` |
| `app('user_rights')->hasRight('x')` | `AclService::current()->can('x')` |
| `(new UserRightsHelper)->hasRight('x')` | `AclService::current()->can('x')` |
| `in_array('x', Session::get('user_rights'))` | `AclService::current()->can('x')` |
| writing `user_roles` + `inv_update` | `ManageAclService::setUserRole(...)` |
| `module_item_routes.resource_id` (menu hiding) | permission **guards route** |

Import the old ACL first, so the old keys (e.g. `can-assign-roles`) exist as permissions and
the same keys keep working in the new checks.

**Already on the new ACL:** *Employees → Worker info*: the **Payroll** tab
(`employees.worker-info.payroll`), the **User rights** tab (`employees.worker-info.user-rights`,
assigns new ACL roles), editing the basic info (`employees.worker-info.edit`) and creating
workers with the "new worker" modal (`employees.worker-info.create`).
*Employees → Workplaces*: creating workplaces (`employees.workplaces.create`), changing and deleting
them (`employees.workplaces.edit`). Without them the inputs are shown disabled.
*Finance → Payroll*: changing the payroll values (`finance.payroll.edit`).

**Still on the old ACL** (as of this writing): the old menu (`MenuService`), the old HR
worker tabs and the `/adm/acl` screen.

## 18. Limitations and things to know

- **Livewire actions aren't covered by route middleware.** Check permissions inside the
  actions (see [section 11](#protecting-livewire-actions)).
- Only routes registered as **module routes** in `/app/modules` can be guarded from the
  screen. Other routes need `acl:` / `can:` in code.
- A route can be guarded by **one** permission. For "A or B", use `acl:a,b` in code.
- Keys of roles and permissions can't be changed after creation. To rename one, create a new
  permission, move the roles over, and delete the old one.
- The ACL sync file only adds role permissions and never deletes. Removals have to be
  done on each server (see [the ACL sync file](#shipping-permissions-with-a-feature-the-acl-sync-file)).
- The `acl` middleware is applied to the whole v3 group. Before the migration is run, it lets
  everything through (it treats missing tables as "nothing is guarded").
- The import never deletes. Something removed in the old ACL stays in the new one until you
  remove it there.

## 19. Troubleshooting

**`/app/acl` gives 404**
Routes are cached. Run `php artisan route:clear` (and `route:cache` again if you cache them).

**The ACL tab doesn't show in the App control center**
Add the `getAclModule` route to the app-settings module in `/app/modules` and make it
active (see [3.3](#33-show-the-acl-tab-in-the-app-control-center)).

**"Table acl_… doesn't exist"**
Run `php artisan migrate`.

**User Access shows ✔ but the user still can't do it**
That part of the app still checks the old ACL (session `user_rights`, `@hasRights`,
`hasRight()`). Either give the right in the old ACL too, or move that check to the new ACL
([section 17](#moving-code-from-the-old-acl-to-the-new)).

**User Access shows ✘ but the user can still do it**
- The user is a super admin (blue notice), or
- that page or action isn't protected yet: no bound route, no `acl:` middleware, no check
  in the Livewire action.

**Everyone suddenly lost access to a page**
A permission was bound to its route, and nobody (except super admins) holds it. Either
give the permission to the right roles, or clear **Guards route** on the permission.

**Changed the tables by hand and nothing changed**
The results are cached. Run `AclService::flush()` (e.g. in `php artisan tinker`) or
`php artisan cache:clear`.

**"The route is already bound to the permission: …"**
A route can have one guarding permission. Clear it on the other permission first, or use
`acl:a,b` in code if both should open it.
