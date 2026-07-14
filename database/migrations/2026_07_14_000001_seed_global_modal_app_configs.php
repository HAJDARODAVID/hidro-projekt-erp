<?php

use App\Models\Application\AppConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private array $configs = [
        [
            'key'           => 'global_modal_header_name',
            'value'         => 'Module',
            'default_value' => 'Module',
            'label'         => 'Global Modal – Header Title',
            'description'   => 'Default title text shown in the global modal header bar.',
            'data_type'     => 'string',
            'is_public'     => false,
            'is_locked'     => false,
        ],
        [
            'key'           => 'global_modal_header_name_style',
            'value'         => 'font-weight: 600; font-size: 1rem;',
            'default_value' => 'font-weight: 600; font-size: 1rem;',
            'label'         => 'Global Modal – Header Title Style',
            'description'   => 'Inline CSS applied to the title text in the global modal header (e.g. font-weight, color, font-size).',
            'data_type'     => 'string',
            'is_public'     => false,
            'is_locked'     => false,
        ],
        [
            'key'           => 'global_modal_max_width',
            'value'         => '1140px',
            'default_value' => '1140px',
            'label'         => 'Global Modal – Max Width',
            'description'   => 'CSS max-width value for the global modal container (e.g. 960px, 1140px, 90vw).',
            'data_type'     => 'string',
            'is_public'     => false,
            'is_locked'     => false,
        ],
    ];

    public function up(): void
    {
        foreach ($this->configs as $config) {
            AppConfig::firstOrCreate(['key' => $config['key']], $config);
        }
    }

    public function down(): void
    {
        AppConfig::whereIn('key', array_column($this->configs, 'key'))->delete();
    }
};
