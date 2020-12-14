<?php

declare (strict_types=1);

namespace Goat\Console;

use Goat\Console\Command\GraphvizCommand;
use Goat\Console\Command\InspectCommand;
use Goat\Console\Command\PgSQLStatCommand;
use Goat\Driver\Driver;
use Goat\Driver\DriverFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class App
{
    public static function boostrap(): void
    {
        $input = new ArgvInput();
        $application = new Application('goat-db', '3.0.0-alpha');

        $application->add(new GraphvizCommand());
        $application->add(new InspectCommand());
        $application->add(new PgSQLStatCommand());

        $application->run($input);
    }

    public static function configureCommand(Command $command): void
    {
        $command->addOption('uri', 'u', InputOption::VALUE_OPTIONAL, "Database URI, if none provided, will lookup the 'DATABASE_URI' environment variable.");
        $command->addOption('schema', 's', InputOption::VALUE_OPTIONAL, "Default schema to work with.");
    }

    public static function getDatabaseDriver(InputInterface $input): Driver
    {
        $databaseUri = $input->getOption('uri');
        if (!$databaseUri) {
            $databaseUri = \getenv('DATABASE_URI');
        }

        if (!$databaseUri) {
            throw new InvalidArgumentException(\sprintf("Either one of --uri=URI option or the 'DATABASE_URI=URI' environment variable must be specified."));
        }

        return DriverFactory::fromUri($databaseUri);
    }
}
