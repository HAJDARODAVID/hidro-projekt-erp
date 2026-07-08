<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config
set('application', 'deployer');

// Project repository
set('repository', 'git@github.com:HAJDARODAVID/hidro-projekt-erp.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', [
    'storage',
    'bootstrap',
]);

// Hosts
host('Production')
    ->setHostname('165.245.223.177')
    ->setRemoteUser('deployer')
    ->setIdentityFile('~/.ssh/id_rsa')
    ->set('deploy_path', '/var/www/hidro-projekt-erp')
    ->set('branch', 'master')
    ->set('ssh_multiplexing', false);


// Hooks

after('deploy:failed', 'deploy:unlock');

task('deploy:build', function () {
    run('cd {{release_path}} && npm install && npm run build');
});

task('artisan:auto-install', function () {
    run('cd {{release_path}} && php artisan auto-install:check');
})->desc('Run auto-installation checks');

after('deploy:update_code', 'deploy:build');
after('artisan:migrate', 'artisan:auto-install');
