<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Burial project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Burial\Console\Command;

use SolidWorx\Burial\Burial;
use SolidWorx\Burial\Tomb;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Run extends Command
{
    public const COMMAND = 'run';

    protected static $defaultName = self::COMMAND;

    protected function configure()
    {
        $this->addArgument('socket', InputArgument::REQUIRED, 'The Tombs socket')
            ->addArgument('project_dir', InputArgument::OPTIONAL, 'The path to your project', getcwd())
            ->addOption('production-path', 'p', InputOption::VALUE_REQUIRED, 'The path to your project on production. This is used to strip the path from the files returned from Tombs. If this option is not supplied, the %project_dir% argument will be used')
            ->addOption('ignore-dir', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore a directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = rtrim($input->getArgument('project_dir'), '/');
        $path = rtrim($input->getOption('production-path') ?? $projectDir, '/');
        $ignoreDirs = $input->getOption('ignore-dir');

        $progressOutput = $output->section();
        $fileOutput = $output->section();

        $burial = new Burial($projectDir);

        $tombs = explode("\n", $this->getTombs(...$this->parseSocket($input->getArgument('socket'))));

        $progress = new ProgressBar($progressOutput);
        $progress->start();

        foreach ($tombs as $tomb) {
            if ('' === $tomb) {
                continue;
            }

            $tomb = Tomb::fromJson($tomb);

            if ('phar:' === substr($tomb->file, 0, 5)) {
                // Skip PHAR files
                continue;
            }

            $tomb->file = str_replace($path.'/', '', $tomb->file);

            if ('vendor' === substr($tomb->file, 0, 6)) {
                // Skip vendor files
                continue;
            }

            foreach ($ignoreDirs as $dir) {
                if (0 === strpos($tomb->file, $dir)) {
                    continue 2;
                }
            }

            try {
                $fileOutput->overwrite("Processing {$tomb->file} ($tomb->function)");
                $burial->bury($tomb);
            } catch (\Throwable $e) {
                $progress->clear();
                $fileOutput->clear();

                (new SymfonyStyle($input, $output))->error("Unable to process {$tomb->file}: {$e->getMessage()}");

                return 255;
            }
            $progress->advance();
        }

        $progress->clear();
        $fileOutput->clear();

        $output->writeln('<info>Done</info>');

        return 0;
    }

    private function getTombs(string $ip, ?int $port): string
    {
        $socket = fsockopen($ip, $port);
        if (!$socket) {
            throw new \RuntimeException("cannot open {$ip}:{$port}");
        }

        $content = stream_get_contents($socket);

        fclose($socket);

        return $content;
    }

    private function parseSocket(string $socket): array
    {
        if ('unix' === substr($socket, 0, 4)) {
            return [$socket, null];
        }

        $parts = parse_url($socket);

        return [$parts['host'], $parts['port']];
    }
}
