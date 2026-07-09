<?php

return new class {
    public function getType(): string
    {
        return 'app_config';
    }

    public function getData(): array
    {
        return [
            'feature_flag' => 'new_dashboard',
        ];
    }

    public function handle(): void
    {
        \Cache::forever('app_config_feature_flag', 'new_dashboard');
    }
};
