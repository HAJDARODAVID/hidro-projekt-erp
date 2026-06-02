# Auto-Installation Service - Quick Start Guide

## 📋 What Was Created

A complete auto-installation system that automatically detects and executes installation files during deployment.

## 🗂️ Files Created

### Core Service & Command

- `app/Services/AutoInstallationService.php` - Main service handling installations
- `app/Console/Commands/CheckAutoInstallCommand.php` - Artisan command
- `app/Models/AutoInstallation.php` - Tracking model

### Database

- `database/migrations/2026_06_02_000001_create_auto_installations_table.php` - Creates tracking table

### Configuration

- `deploy.php` - Updated with auto-install hook
- `AUTO_INSTALLATION_GUIDE.md` - Comprehensive documentation

### Installation Folder

- `storage/app/auto-installations/` - Where you place installation files
    - `README.md` - Usage instructions
    - `2026_06_02_install_custom_feature.php` - PHP installation example
    - `2026_06_02_setup_database_config.php` - Another PHP example
    - `2026_06_02_app_config.json` - JSON config example
    - `2026_06_02_seed_roles.json` - JSON seed data example

## ⚡ Quick Start

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Test the Command

```bash
# Manual test
php artisan auto-install:check

# With verbose output
php artisan auto-install:check --verbose
```

### 3. Create Your First Installation File

**Option A: Simple JSON Config**

Create: `storage/app/auto-installations/2026_06_02_my_config.json`

```json
{
    "type": "app_config",
    "data": {
        "feature_enabled": true,
        "api_version": "2.0"
    }
}
```

**Option B: Custom PHP Installation**

Create: `storage/app/auto-installations/2026_06_02_my_setup.php`

```php
<?php
return new class {
    public function handle(): void {
        \Log::info('My installation running');
        \Cache::forever('my_setting', 'value');
    }
    public function getType(): string { return 'custom'; }
    public function getData(): ?array { return null; }
};
```

### 4. Run Installation

```bash
php artisan auto-install:check
```

Expected output:

```
🔍 Checking for pending auto-installations...

✅ Successfully installed:
   • 2026_06_02_my_config.json

✓ Auto-installation check completed successfully!
```

## 🔍 Check Installation Status

```bash
php artisan tinker

# View all installations
>>> App\Models\AutoInstallation::all();

# View successful installations
>>> App\Models\AutoInstallation::where('success', true)->get();

# View failed installations
>>> App\Models\AutoInstallation::where('success', false)->get();

# Check specific file
>>> App\Models\AutoInstallation::where('file_name', '2026_06_02_my_config.json')->first();
```

## 📝 Installation File Types

### Type 1: app_config (JSON)

Stores key-value configurations in cache automatically.

```json
{
    "type": "app_config",
    "data": {
        "setting_key": "value",
        "another_key": true
    }
}
```

Access: `\Cache::get('app_config_setting_key')`

### Type 2: seed_data (JSON)

Creates initial database records using `firstOrCreate()`.

```json
{
    "type": "seed_data",
    "data": {
        "model": "Role",
        "records": [
            { "name": "admin", "display_name": "Admin" },
            { "name": "user", "display_name": "User" }
        ]
    }
}
```

### Type 3: Custom (PHP)

Full control over installation logic.

```php
<?php
return new class {
    public function handle(): void {
        // Your logic here
    }
    public function getType(): string { return 'custom_type'; }
    public function getData(): ?array { return ['data' => 'to_track']; }
};
```

## 🚀 Deployment Integration

The system is already configured in `deploy.php`. It runs automatically after database migrations:

```php
after('artisan:migrate', 'artisan:auto-install');
```

## 📚 Use Cases

### 1. Setup Application Configuration

```json
{
    "type": "app_config",
    "data": {
        "maintenance_mode": false,
        "cache_ttl": 3600,
        "max_upload_size": 52428800
    }
}
```

### 2. Initialize System Permissions

```php
<?php
return new class {
    public function handle(): void {
        $permissions = ['view_reports', 'create_project', 'edit_config'];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }
    public function getType(): string { return 'permissions'; }
    public function getData(): ?array { return ['count' => 3]; }
};
```

### 3. Create Initial Roles

```json
{
    "type": "seed_data",
    "data": {
        "model": "Role",
        "records": [
            { "id": 1, "name": "admin" },
            { "id": 2, "name": "manager" },
            { "id": 3, "name": "employee" }
        ]
    }
}
```

### 4. Setup Feature Flags

```json
{
    "type": "app_config",
    "data": {
        "feature_new_dashboard": true,
        "feature_analytics": false,
        "feature_export_excel": true
    }
}
```

## ✅ Key Features

- ✅ One-time execution (tracked in database)
- ✅ Automatic deployment integration
- ✅ Support for PHP and JSON files
- ✅ Comprehensive error logging
- ✅ Easy status checking
- ✅ Manual/automatic execution modes
- ✅ Rollback capability (delete from DB to re-run)

## ⚠️ Important Notes

1. **File names are unique** - Each file name can only be installed once
2. **Use naming convention** - `YYYY_MM_DD_description.ext`
3. **Don't modify executed files** - Creating a new one is safer
4. **Test locally first** - Before deploying to production
5. **Never store secrets** - Use environment variables

## 🔧 Troubleshooting

**Installation not running?**

```bash
# Check if file exists
ls storage/app/auto-installations/

# Check if already installed
php artisan tinker
>>> App\Models\AutoInstallation::where('file_name', 'your_file.json')->first();

# Delete record to re-run (for testing only)
>>> App\Models\AutoInstallation::where('file_name', 'your_file.json')->delete();
```

**Installation failed?**

```bash
php artisan tinker
>>> App\Models\AutoInstallation::where('success', false)->first();
# Check the 'error' column for details
```

**See detailed output**

```bash
php artisan auto-install:check --verbose
```

## 📖 Full Documentation

See `AUTO_INSTALLATION_GUIDE.md` for complete information.

## 🎯 Example Workflow

1. Create a new feature that needs initial setup
2. Create installation file in `storage/app/auto-installations/`
3. Name it using convention: `2026_06_02_feature_name.php`
4. Test locally: `php artisan auto-install:check`
5. Commit the file with your code
6. Deploy - it runs automatically after migrations
7. Verify: `php artisan tinker` → `App\Models\AutoInstallation::all()`

---

**Ready to use! Start creating installation files in `storage/app/auto-installations/`**
