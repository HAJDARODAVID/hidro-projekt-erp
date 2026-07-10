# Auto-Installation Service Documentation

## Overview

The Auto-Installation Service automatically detects and executes installation files during deployment. It's perfect for:

- Setting up new app configurations
- Seeding initial data
- Running custom setup logic
- Managing feature rollouts without migrations

## How It Works

1. **Detection**: On deployment, the `auto-install:check` command scans `storage/app/auto-installations/` folder
2. **Tracking**: Each executed file is recorded in the `auto_installations` database table
3. **Execution**: Only new files (not previously installed) are executed
4. **Logging**: All operations are logged for audit trails

## Installation

### 1. Run Migration

```bash
php artisan migrate
```

This creates the `auto_installations` table to track executed installations.

### 2. Set Up Auto-Installation Folder

The folder is automatically created at: `storage/app/auto-installations/`

If you need to create it manually:

```bash
mkdir storage/app/auto-installations
```

### 3. Add to Deployment Script

Update your `deploy.php` or deployment configuration:

```php
// In deploy.php or your deployment script
task('auto:install', function () {
    run('cd {{release_path}} && php artisan auto-install:check');
});

// Call it as part of the deployment
before('deploy:success', 'auto:install');
// Or after migrations:
after('database:migrate', 'auto:install');
```

## Usage

### Run Manual Check

```bash
php artisan auto-install:check
```

### With Verbose Output

```bash
php artisan auto-install:check --verbose
```

## Creating Installation Files

### Option 1: PHP-Based Installation

Create a file in `storage/app/auto-installations/` with a descriptive name:

**File**: `storage/app/auto-installations/2026_06_02_setup_payment_gateway.php`

```php
<?php

return new class {
    public function handle(): void
    {
        // Your installation logic
        \Log::info('Setting up payment gateway...');

        // Store configuration
        \Cache::forever('payment_gateway_provider', 'stripe');
        \Cache::forever('payment_gateway_key', env('PAYMENT_GATEWAY_KEY'));

        // Or update database
        // DB::table('settings')->updateOrInsert(
        //     ['key' => 'payment_provider'],
        //     ['value' => 'stripe']
        // );
    }

    public function getType(): string
    {
        return 'payment_setup';
    }

    public function getData(): ?array
    {
        return ['provider' => 'stripe'];
    }
};
```

### Option 2: JSON-Based Installation (app_config)

**File**: `storage/app/auto-installations/2026_06_02_app_settings.json`

```json
{
    "type": "app_config",
    "description": "Application configuration",
    "data": {
        "app_environment": "production",
        "api_rate_limit": 1000,
        "cache_enabled": true,
        "features": {
            "new_dashboard": true,
            "advanced_analytics": false
        }
    }
}
```

These values are stored in cache as `app_config_[key]`.

### Option 3: JSON-Based Installation (seed_data)

**File**: `storage/app/auto-installations/2026_06_02_seed_departments.json`

```json
{
    "type": "seed_data",
    "description": "Seed department records",
    "data": {
        "model": "Department",
        "records": [
            {
                "name": "Engineering",
                "budget": 50000,
                "manager_id": 1
            },
            {
                "name": "Sales",
                "budget": 30000,
                "manager_id": 2
            },
            {
                "name": "HR",
                "budget": 20000,
                "manager_id": 3
            }
        ]
    }
}
```

The service uses `firstOrCreate()` with all provided fields as the lookup criteria.

## File Naming Convention

Use descriptive names with timestamps to avoid conflicts:

```
2026_06_02_feature_name.php
2026_06_02_feature_name.json
YYYY_MM_DD_description.ext
```

## Database Table Schema

```
auto_installations
├── id (primary key)
├── file_name (unique)
├── installation_type (varchar)
├── data (text - JSON serialized)
├── success (boolean)
├── error (text - null if successful)
├── installed_at (timestamp - null if failed)
├── created_at
└── updated_at
```

## Service Methods

### AutoInstallationService

```php
// Run all pending installations
$results = $service->runPendingInstallations();
// Returns: ['success' => [], 'failed' => [], 'skipped' => []]

// Get installation history
$history = $service->getInstallationHistory($limit = 50);

// Get successful installations
$successful = $service->getSuccessfulInstallations()->get();

// Get failed installations
$failed = $service->getFailedInstallations()->get();

// Check if file was installed
$isInstalled = $service->isInstalled('2026_06_02_app_config.json');

// Get installation path
$path = $service->getInstallationPath();
```

## Examples

### Example 1: Setup Email Configuration

**File**: `storage/app/auto-installations/2026_06_02_setup_email.php`

```php
<?php

return new class {
    public function handle(): void
    {
        \Cache::forever('email_provider', env('MAIL_DRIVER'));
        \Cache::forever('email_from', env('MAIL_FROM_ADDRESS'));

        \Log::info('Email configuration installed');
    }

    public function getType(): string
    {
        return 'email_setup';
    }

    public function getData(): ?array
    {
        return [
            'driver' => env('MAIL_DRIVER'),
            'from' => env('MAIL_FROM_ADDRESS'),
        ];
    }
};
```

### Example 2: Create Initial Permissions

**File**: `storage/app/auto-installations/2026_06_02_create_permissions.php`

```php
<?php

return new class {
    public function handle(): void
    {
        $permissions = [
            'view_reports',
            'create_project',
            'edit_employee',
            'delete_project',
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        \Log::info('Permissions created successfully');
    }

    public function getType(): string
    {
        return 'permissions_setup';
    }

    public function getData(): ?array
    {
        return ['permissions_count' => 4];
    }
};
```

### Example 3: Initialize System Parameters

**File**: `storage/app/auto-installations/2026_06_02_system_params.json`

```json
{
    "type": "seed_data",
    "data": {
        "model": "SystemParameter",
        "records": [
            {
                "key": "company_name",
                "value": "HidroProjekt"
            },
            {
                "key": "fiscal_year_start",
                "value": "2026-01-01"
            },
            {
                "key": "currency",
                "value": "EUR"
            }
        ]
    }
}
```

## Troubleshooting

### Installation File Not Executing

1. **File is in wrong location**: Ensure the file is in `storage/app/auto-installations/`
2. **File extension is wrong**: Use `.php` or `.json` only
3. **Already installed**: Check `auto_installations` table - if file exists with `success=true`, it won't run again
4. **PHP file issues**: Ensure PHP files return an object with `handle()` method

### Check Installation Status

```bash
# View all installations
php artisan tinker
>>> App\Models\AutoInstallation::all();

# Check specific file
>>> App\Models\AutoInstallation::where('file_name', '2026_06_02_app_config.json')->first();

# View failed installations
>>> App\Models\AutoInstallation::where('success', false)->get();
```

### Reset Installation (For Testing)

```bash
# Delete record to re-run installation
php artisan tinker
>>> App\Models\AutoInstallation::where('file_name', 'your_file.json')->delete();
```

## Best Practices

1. ✅ Use descriptive file names with timestamps
2. ✅ Add comprehensive error handling in PHP installations
3. ✅ Test installations locally before deployment
4. ✅ Keep data files small and focused
5. ✅ Log important actions for debugging
6. ✅ Use `firstOrCreate()` for seed data to avoid duplicates
7. ✅ Document the purpose of each installation file
8. ✅ Version your installations alongside your code

## Deployment Integration

### GitHub Actions Example

```yaml
- name: Run Auto-Installations
  run: php artisan auto-install:check --verbose
```

### Laravel Deployment

In `deploy.php`:

```php
task('artisan:auto-install', function () {
    cd('{{release_path}}');
    run('php artisan auto-install:check');
})->desc('Run auto-installation checks');

after('artisan:migrate', 'artisan:auto-install');
```

## Security Considerations

- ⚠️ Installation files execute with full application permissions
- ⚠️ Never store sensitive credentials in installation files
- ⚠️ Use environment variables for sensitive data
- ⚠️ Validate and sanitize any external input
- ⚠️ Restrict folder access: `storage/app/auto-installations/` should not be web-accessible
