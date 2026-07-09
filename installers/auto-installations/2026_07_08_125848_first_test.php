<?php

use App\Models\User\User;

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
            'key' => 'first_test',
            'title' => 'First Test',
        ];
    }

    /**
     * Main installation handler
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('⚙️ Installer log description...');

        try {
            User::find(1)->update(['name' => 'Davidenko']);
            \Illuminate\Support\Facades\Log::info('✓ Installed successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('✗ Failed to install: ' . $e->getMessage());
            throw $e;
        }
    }
};
