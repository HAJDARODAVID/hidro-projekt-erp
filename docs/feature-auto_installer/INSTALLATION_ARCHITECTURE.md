# Auto-Installation Service - Architecture & Flow

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT PROCESS                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
                    ┌─────────────────┐
                    │ Update Code     │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │ Run Migrations  │
                    └────────┬────────┘
                             ↓
              ┌──────────────────────────────┐
              │ CheckAutoInstallCommand      │
              │  (auto-install:check)        │
              └──────────┬───────────────────┘
                         ↓
        ┌────────────────────────────────────────┐
        │ AutoInstallationService                │
        │ • Scan storage/app/auto-installations/ │
        │ • Check database for existing records  │
        │ • Execute pending installations        │
        │ • Track results                        │
        └────────────┬──────────────────────────┘
                     ↓
        ┌────────────────────────────────┐
        │  Installation Files (*.php/*.json)    │
        │  - Execute handler              │
        │  - Log results                  │
        │  - Save to auto_installations   │
        └────────────┬────────────────────┘
                     ↓
        ┌────────────────────────────────┐
        │  auto_installations Table      │
        │  (Track installed files)       │
        └────────────────────────────────┘
```

## 📊 Database Schema

```
auto_installations
├── id (bigint, PK)
├── file_name (varchar, UNIQUE)        ← Installation file name
├── installation_type (varchar)        ← Type: app_config, seed_data, etc.
├── data (longtext, JSON)             ← Serialized installation data
├── success (boolean)                  ← true/false
├── error (longtext, nullable)         ← Error message if failed
├── installed_at (timestamp, nullable) ← When it was executed
├── created_at (timestamp)
└── updated_at (timestamp)
```

## 🔄 Execution Flow

```
┌─────────────────────┐
│ Check Pending Files │  Read storage/app/auto-installations/
└──────────┬──────────┘
           ↓
    ┌──────────────────┐
    │ For Each File    │
    └────────┬─────────┘
             ↓
    ┌──────────────────────┐
    │ Already Installed?   │ Check auto_installations table
    └────┬────────────┬────┘
         │ YES        │ NO
         ↓            ↓
      SKIP        ┌──────────────────┐
                  │ Validate File    │ Check extension
                  └────────┬─────────┘
                           ↓
                  ┌────────────────────┐
                  │ PHP or JSON?       │
                  └─┬──────────────┬──┘
                    │ PHP          │ JSON
                    ↓              ↓
            ┌──────────────┐  ┌─────────────┐
            │ Require File │  │ Parse JSON  │
            │ Get Object   │  │ Get Type    │
            └──────┬───────┘  └─────┬───────┘
                   ↓                ↓
            ┌──────────────┐  ┌─────────────┐
            │ Call         │  │ Route by    │
            │ handle()     │  │ Type:       │
            └──────┬───────┘  │ - app_conf  │
                   ↓          │ - seed_data │
            ┌──────────────┐  └─────┬───────┘
            │ Save Record  │        ↓
            │ success=true │  ┌─────────────┐
            └──────────────┘  │ Execute     │
                               │ Handler     │
                               └─────┬───────┘
                                     ↓
                              ┌──────────────┐
                              │ Save Record  │
                              │ success=true │
                              └──────────────┘
```

## 📁 Directory Structure

```
project-root/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── CheckAutoInstallCommand.php    ← Artisan command
│   │   └── Kernel.php
│   ├── Models/
│   │   └── AutoInstallation.php               ← Tracking model
│   └── Services/
│       └── AutoInstallationService.php        ← Main service
├── database/
│   └── migrations/
│       └── 2026_06_02_000001_create_auto_installations_table.php
├── storage/
│   └── app/
│       └── auto-installations/                ← Installation files here
│           ├── README.md
│           ├── 2026_06_02_app_config.json
│           ├── 2026_06_02_seed_roles.json
│           ├── 2026_06_02_install_custom_feature.php
│           └── 2026_06_02_setup_database_config.php
├── deploy.php                                 ← Deployment config
├── QUICK_START.md                            ← Quick reference
├── AUTO_INSTALLATION_GUIDE.md                ← Full documentation
└── INSTALLATION_ARCHITECTURE.md              ← This file
```

## 🔌 Installation File Types

### Type 1: app_config

```
Input (JSON):
{
  "type": "app_config",
  "data": {
    "feature_enabled": true,
    "cache_ttl": 3600
  }
}

Process:
  AutoInstallationService::handleAppConfig()
  ├── Loop through data entries
  └── Cache::forever('app_config_KEY', VALUE)

Output:
  \Cache::get('app_config_feature_enabled') → true
  \Cache::get('app_config_cache_ttl') → 3600
```

### Type 2: seed_data

```
Input (JSON):
{
  "type": "seed_data",
  "data": {
    "model": "Role",
    "records": [
      { "name": "admin" },
      { "name": "user" }
    ]
  }
}

Process:
  AutoInstallationService::handleSeedData()
  ├── Validate model exists
  ├── For each record
  └── Model::firstOrCreate(record)

Output:
  Role::where('name', 'admin')->first() → exists
  Role::where('name', 'user')->first() → exists
```

### Type 3: Custom (PHP)

```
Input (PHP):
<?php
return new class {
    public function handle(): void {
        // Custom logic
    }
    public function getType(): string { return 'custom'; }
    public function getData(): ?array { return ['data' => 'to_track']; }
};

Process:
  AutoInstallationService::executePHPInstallation()
  ├── Require PHP file
  ├── Get returned object
  ├── Call handle()
  └── Store result

Output:
  Custom installation executed with full control
```

## 🔒 Transaction Flow

```
AutoInstallationService::runPendingInstallations()
│
├── Get all files from storage/app/auto-installations/
├── Initialize results array
│
└── For each file:
    │
    ├── Get file name
    ├── Skip non-PHP/JSON files
    │
    ├── Check if already in database:
    │   ├── YES → Add to results['skipped']
    │   └── NO → Continue
    │
    ├── Try to execute:
    │   │
    │   ├── If PHP → executePHPInstallation()
    │   ├── If JSON → executeJSONInstallation()
    │   │
    │   ├── Create AutoInstallation record:
    │   │   ├── success = true
    │   │   ├── installed_at = now()
    │   │   └── error = null
    │   │
    │   └── Add to results['success']
    │
    └── Catch exceptions:
        ├── Create AutoInstallation record:
        │   ├── success = false
        │   └── error = exception message
        │
        └── Add to results['failed']

Return results array with counts
```

## 🎯 Typical Installation Workflow

### Development

```
1. Add new feature that needs setup
   └── Create storage/app/auto-installations/2026_06_02_my_feature.php

2. Test locally
   └── php artisan auto-install:check

3. Verify in database
   └── php artisan tinker
       App\Models\AutoInstallation::where('file_name', '...')->first();

4. Commit changes
   └── git add && git commit
```

### Deployment

```
1. Deploy code
   └── deploy script runs

2. Run migrations
   └── php artisan migrate

3. Auto-install hook runs
   └── php artisan auto-install:check

4. Installations execute once
   └── Tracked in auto_installations table

5. Never runs again
   └── File exists in table = skipped
```

## ⚡ Key Design Decisions

### 1. One-Time Execution

- **Why**: Each installation should only run once
- **How**: Check `auto_installations` table for file_name
- **Benefit**: Safe for multiple deployments

### 2. File-Based Configuration

- **Why**: Easy to version control with code
- **How**: Store PHP and JSON files in storage/app/auto-installations/
- **Benefit**: No manual intervention needed

### 3. Automatic Deployment Integration

- **Why**: Ensures installations run at right time
- **How**: Hook in deploy.php after migrations
- **Benefit**: Consistent deployment process

### 4. Separate Service & Command

- **Why**: Reusable logic, testable components
- **How**: Service handles logic, Command is CLI interface
- **Benefit**: Can use service in other contexts

### 5. JSON Support

- **Why**: Simple configurations don't need PHP code
- **How**: Parse JSON and route by type
- **Benefit**: Lower barrier to entry for simple tasks

## 🔍 Monitoring & Debugging

### Check Installation History

```php
// Successful installations
AutoInstallation::where('success', true)->get();

// Failed installations
AutoInstallation::where('success', false)->get();

// By installation type
AutoInstallation::where('installation_type', 'app_config')->get();

// Recent installations
AutoInstallation::orderBy('created_at', 'desc')->limit(10)->get();
```

### Re-run Installation (Testing Only)

```php
// Delete record to force re-run
AutoInstallation::where('file_name', '2026_06_02_app_config.json')->delete();

// Re-run command
// php artisan auto-install:check
```

## 📈 Performance Considerations

- **File I/O**: Minimal - only reads from disk once per deployment
- **Database**: Simple queries using file_name index
- **Execution**: Runs sequentially, typically < 1 second per file
- **Logging**: Uses Laravel's standard logging (configurable)

## 🔐 Security Considerations

- ✅ Files execute with application permissions
- ✅ Validated before execution (PHP syntax, JSON format)
- ⚠️ Never store secrets in files - use environment variables
- ⚠️ Restrict folder permissions: `storage/app/auto-installations/`
- ⚠️ Validate any external input within handlers
