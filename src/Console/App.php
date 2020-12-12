<?php

declare (strict_types=1);

namespace Goat\Console;

use Goat\Console\Command\IntrospectCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

final class App
{
    public static function boostrap(): void
    {
        $input = new ArgvInput();
        // $debug = (\filter_var($_ENV['DEBUG'] ?? "0", FILTER_VALIDATE_BOOLEAN) ? true : false) && !$input->hasParameterOption('--no-debug', true);
        $application = new Application('goat-db', '3.0.0-alpha');

        $application->add(new IntrospectCommand());

        $application->run($input);
    }
}
