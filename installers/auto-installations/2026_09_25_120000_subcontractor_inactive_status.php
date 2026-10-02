<?php

use Illuminate\Support\Facades\DB;

return new class {

    /**
     * Installation type identifier
     */
    public function getType(): string
    {
        return 'basic';
    }

    /**
     * Data to store for audit trail
     */
    public function getData(): array
    {
        return [
            'key'   => 'subcontractor_inactive_status',
            'title' => 'Subcontractor status: inactive -1 -> 0 (-1 is now deleted)',
        ];
    }

    /**
     * The subcontractor (cooperators) statuses changed from 1 = active, -1 = inactive
     * to 1 = active, 0 = inactive, -1 = deleted.
     * Every existing -1 was an inactive subcontractor, so it is moved to 0.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('⚙️ Updating the inactive subcontractor status...');

        try {
            $count = DB::table('cooperators')->where('status', -1)->update(['status' => 0]);

            \Illuminate\Support\Facades\Log::info('✓ Inactive subcontractor status updated (' . $count . ' rows)');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('✗ Failed to update the inactive subcontractor status: ' . $e->getMessage());
            throw $e;
        }
    }
};
