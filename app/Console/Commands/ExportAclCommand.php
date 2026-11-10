<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Application\Acl\AclSyncService;

class ExportAclCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the ACL permissions and roles into an auto-installer of its own, so the next deploy syncs them to the server';

    public function handle(): int
    {
        $export = AclSyncService::exportToFile();

        foreach ($export['warnings'] as $warning) {
            $this->warn($warning);
        }

        $this->info('Exported ' . $export['permissions'] . ' permissions and ' . $export['roles'] . ' roles (' . $export['role_permissions'] . ' role permissions).');
        $this->line('Path: ' . AclSyncService::filePath());
        $this->line('User roles and user overrides are not exported, they are set on each server.');
        $this->line('Review the git diff of the file, then commit it. The next deploy installs it (auto-install:check).');

        return self::SUCCESS;
    }
}
