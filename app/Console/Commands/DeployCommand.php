<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run Deployer (vendor/bin/dep, config in deploy.php) through artisan.
 *
 * The dep script is started with the same PHP that runs artisan, so it also works where calling
 * "vendor/bin/dep" / "dep.bat" directly fails (Windows, PATH, the PHP version of the shell).
 * Artisan's own verbosity is passed on: php artisan deploy -vvv  ->  dep deploy Production -vvv
 *
 *   php artisan deploy                       deploy to Production (asks first)
 *   php artisan deploy -vvv                  ... with Deployer's debug output
 *   php artisan deploy --force               ... without asking
 *   php artisan deploy deploy:unlock         another Deployer task (e.g. after a failed deploy)
 *   php artisan deploy --branch=feature-x    another branch than the one in deploy.php
 *   php artisan deploy --dep=--revision=abc  any other option for dep (repeatable)
 */
class DeployCommand extends Command
{
    protected $signature = 'deploy
                            {task=deploy : Deployer task, e.g. deploy, deploy:unlock, rollback}
                            {selector=Production : Host or selector from deploy.php}
                            {--branch= : Branch to deploy (default: the one set in deploy.php)}
                            {--dep=* : Extra raw option(s) for dep, e.g. --dep=--revision=abc123}
                            {--force : Do not ask for confirmation}';

    protected $description = 'Run Deployer (vendor/bin/dep) with the PHP of artisan; -v / -vv / -vvv are passed on to dep';

    /**Tasks that change the server and need the confirmation */
    private const CHANGING_TASKS = ['deploy', 'rollback'];

    public function handle(): int
    {
        $dep = base_path('vendor/bin/dep');
        if (!is_file($dep)) {
            $this->error('Deployer not found: vendor/bin/dep. Run "composer install" first.');
            return self::FAILURE;
        }
        if (!is_file(base_path('deploy.php'))) {
            $this->error('deploy.php not found in ' . base_path());
            return self::FAILURE;
        }

        $task = (string) $this->argument('task');
        $selector = (string) $this->argument('selector');

        $command = [PHP_BINARY, $dep, $task, $selector];
        if ($this->option('branch')) $command[] = '--branch=' . $this->option('branch');
        foreach ((array) $this->option('dep') as $option) {
            if (trim((string) $option) !== '') $command[] = (string) $option;
        }
        if ($verbosity = $this->verbosityFlag()) $command[] = $verbosity;
        if ($this->option('no-interaction')) $command[] = '--no-interaction';
        if ($this->output->isDecorated()) $command[] = '--ansi';

        $this->line('<fg=gray>> ' . implode(' ', array_map(fn ($part) => str_contains($part, ' ') ? '"' . $part . '"' : $part, $command)) . '</>');

        if (in_array($task, self::CHANGING_TASKS, TRUE) && !$this->option('force')
            && !$this->confirm("Run \"$task\" on $selector" . ($this->option('branch') ? ' (branch ' . $this->option('branch') . ')' : '') . '?', FALSE)) {
            $this->warn('Cancelled.');
            return self::FAILURE;
        }

        $process = new Process($command, base_path(), NULL, NULL, NULL);

        /**Linux / macOS: a real terminal (colors, ssh / git prompts). Windows has no TTY: stream the output. */
        if (Process::isTtySupported()) {
            try {
                $process->setTty(TRUE);
            } catch (\Throwable $th) {
                // no TTY after all (e.g. CI), stream below
            }
        }

        $exitCode = $process->run(function ($type, $buffer) {
            $type === Process::ERR ? $this->output->getErrorStyle()->write($buffer) : $this->output->write($buffer);
        });

        if ($exitCode !== 0) {
            $this->newLine();
            $this->error("dep $task failed (exit code $exitCode).");
            if (!$this->output->isDebug()) $this->line('Run it again with <comment>-vvv</comment> to see what Deployer does.');
            if ($task === 'deploy') $this->line('If the deploy stays locked: <comment>php artisan deploy deploy:unlock</comment>');
        }

        return $exitCode;
    }

    /**
     * Artisan's verbosity as a dep flag: -v, -vv, -vvv (or -q).
     */
    private function verbosityFlag(): ?string
    {
        return match ($this->output->getVerbosity()) {
            OutputInterface::VERBOSITY_DEBUG        => '-vvv',
            OutputInterface::VERBOSITY_VERY_VERBOSE => '-vv',
            OutputInterface::VERBOSITY_VERBOSE      => '-v',
            OutputInterface::VERBOSITY_QUIET        => '-q',
            default                                 => NULL,
        };
    }
}
