<?php

declare (strict_types=1);

namespace Goat\Console\Command;

use Goat\Driver\Driver;
use Goat\Driver\DriverFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Goat\Schema\ColumnMetadata;

final class IntrospectCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('introspect');
        $this->setDescription("Introspect database schema.");
        $this->addOption('uri', 'u', InputOption::VALUE_OPTIONAL, "Database URI, if none provided, will lookup the 'DATABASE_URI' environment variable.");
        $this->addOption('schema', 's', InputOption::VALUE_OPTIONAL, "List tables of schema, list schemas if unspecified.");
        $this->addOption('table', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "List columns of table(s), default schema is 'public' if not specified.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $this->getDatabaseDriver($input);
        $schema = $input->getOption('schema');
        $tables = $input->getOption('table');

        if ($tables) {
            if (!$schema) {
                if ($output->isVerbose()) {
                    $output->writeln("No schema specified, using 'public'. Use --schema=SCHEMA to query a specific schema.");
                }
                $schema = 'public';
            }
            $this->doListTableColumns($driver, $output, $schema, $tables);
        } else if ($schema) {
            $this->doListSchemaTables($driver, $output, $schema);
        } else {
            $this->doListSchemas($driver, $output);
        }

        return self::SUCCESS;
    }

    private function doListSchemas(Driver $driver, OutputInterface $output): void
    {
        $runner = $driver->getRunner();
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        if ($output->isVerbose()) {
            $output->writeln('<comment>' . "Listing all schemas in database." . '</comment>');
        }

        foreach ($schemaIntrospector->listSchemas() as $schema) {
            $output->writeln($schema);
        }
    }

    private function doListSchemaTables(Driver $driver, OutputInterface $output, string $schema): void
    {
        $runner = $driver->getRunner();
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        if ($output->isVerbose()) {
            $output->writeln('<comment>' . \sprintf("Listing tables from schema '%s'.", $schema) . '</comment>');
        }

        foreach ($schemaIntrospector->listTables($schema) as $table) {
            $output->writeln($table);
        }
    }

    private function doListTableColumns(Driver $driver, OutputInterface $output, string $schema, array $tables): void
    {
        $runner = $driver->getRunner();
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        foreach ($tables as $name) {
            if (!$schemaIntrospector->tableExists($schema, $name)) {
                $output->writeln('<error>' . \sprintf("Table does not exists: '%s'", $name) . '</error>');
                continue;
            }

            $output->writeln('<comment>' . \sprintf("Table '%s'", $name) . '</comment>');

            $outputTable = new Table($output);
            $outputTable->setHeaders([
                'column',
                'type',
                'nullable',
                'pkey',
            ]);

            $table = $schemaIntrospector->fetchTableMetadata($schema, $name);
            foreach ($table->getColumns() as $column) {
                \assert($column instanceof ColumnMetadata);

                $outputTable->addRow([
                    $column->getName(),
                    $column->getTable(),
                    $column->isNullable() ? 'yes' : 'no',
                    '',
                ]);
            }

            $outputTable->render();
        }
    }

    private function getDatabaseDriver(InputInterface $input): Driver
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
