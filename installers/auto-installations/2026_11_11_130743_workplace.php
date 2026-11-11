<?php

use App\Models\Employees\Worker;
use App\Models\Employees\Workplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'key' => 'workplace',
            'title' => 'Workplace',
        ];
    }

    /**
     * Main installation handler
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('⚙️ Installer log description...');

        $array = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 2,
            5 => 3,
            6 => 2,
            7 => 2,
            8 => 3,
            9 => 4,
            10 => 5,
            11 => 6,
            12 => 2,
            13 => 2,
            14 => 2,
            15 => 2,
            16 => 2,
            17 => 3,
            18 => 2,
            19 => 2,
            20 => 2,
            21 => 2,
            22 => 7,
            23 => 3,
            24 => 2,
            25 => 2,
            26 => 2,
            29 => 8,
            31 => 9,
            32 => 2,
            33 => 2,
            34 => 2,
            38 => 2,
            39 => 2,
            40 => 2,
            41 => 2,
            42 => 2,
            43 => 0,
            44 => 2,
            45 => 2,
            46 => 2,
            47 => 3,
            48 => 2,
            49 => 2,
            50 => 2,
            51 => 2,
            52 => 2,
            53 => 2,
            54 => 2,
            55 => 2,
            56 => 2,
            57 => 2,
            58 => 2,
            59 => 2,
            60 => 2,
            61 => 2,
            62 => 2,
            63 => 2,
            64 => 2,
            65 => 2,
            66 => 2,
            67 => 2,
            68 => 2,
            69 => 2,
        ];

        try {
            /**0 = no workplace (e.g. 43 ERP DEV); an ID that doesn't exist is skipped (the foreign key would refuse it) */
            $workplaceIds = Workplace::pluck('id')->all();

            DB::transaction(function () use ($array, $workplaceIds) {
                foreach (Worker::whereIn('id', array_keys($array))->get() as $worker) {
                    $workplaceId = $array[$worker->id] ?: NULL;
                    if ($workplaceId !== NULL && !in_array($workplaceId, $workplaceIds, TRUE)) {
                        Log::warning("Workplace installer: worker {$worker->id} skipped, workplace $workplaceId doesn't exist.");
                        continue;
                    }
                    $worker->workplace_id = $workplaceId;
                    $worker->save();
                }
            });
            Log::info('✓ Installed successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('✗ Failed to install: ' . $e->getMessage());
            throw $e;
        }
    }
};
