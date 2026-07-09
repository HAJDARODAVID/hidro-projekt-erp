<?php

namespace App\Console\Commands;

use App\Services\AutoInstallationService;
use Illuminate\Console\Command;

class CheckAutoInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-install:check {--details : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and run pending auto-installations from installers/auto-installations';

    protected AutoInstallationService $autoInstallService;

    public function __construct(AutoInstallationService $autoInstallService)
    {
        parent::__construct();
        $this->autoInstallService = $autoInstallService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking for pending auto-installations...');
        $this->newLine();

        try {
            $results = $this->autoInstallService->runPendingInstallations();

            // Display results
            $this->displayResults($results);

            $totalSuccess = count($results['success']);
            $totalFailed = count($results['failed']);
            $totalSkipped = count($results['skipped']);

            if ($totalSuccess === 0 && $totalFailed === 0) {
                $this->info('✓ No new installations found or all already installed.');
                return Command::SUCCESS;
            }

            if ($totalFailed > 0) {
                $this->error("❌ $totalFailed installation(s) failed!");
                return Command::FAILURE;
            }

            $this->info("✓ Auto-installation check completed successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during auto-installation check: ' . $e->getMessage());
            if ($this->option('details')) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Display the results of the installation check
     */
    protected function displayResults(array $results): void
    {
        if (!empty($results['success'])) {
            $this->newLine();
            $this->info('✅ Successfully installed:');
            foreach ($results['success'] as $file) {
                $this->line("   • $file");
            }
        }

        if (!empty($results['failed'])) {
            $this->newLine();
            $this->error('❌ Failed installations:');
            foreach ($results['failed'] as $failure) {
                $this->line("   • {$failure['file']}: {$failure['error']}");
            }
        }

        if (!empty($results['skipped'])) {
            $this->newLine();
            $this->info('⏭️  Already installed (skipped):');
            foreach ($results['skipped'] as $file) {
                $this->line("   • $file");
            }
        }

        $this->newLine();
    }
}
