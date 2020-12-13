<?php

declare (strict_types=1);

namespace Goat\Console\Command;

use Goat\Console\App;
use Goat\Driver\Driver;
use Goat\Schema\ColumnMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class InspectCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('inspect');
        $this->setAliases(['i']);
        $this->setDescription("Introspect database schema.");
        $this->addOption('table', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "List columns of table(s), default schema is 'public' if not specified.");

        App::configureCommand($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = App::getDatabaseDriver($input);

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
            $primaryKey = $table->getPrimaryKey();

            foreach ($table->getColumns() as $column) {
                \assert($column instanceof ColumnMetadata);

                $outputTable->addRow([
                    $column->getName(),
                    $column->getType(),
                    $column->isNullable() ? 'yes' : 'no',
                    $primaryKey->contains($column->getName()) ? 'yes' : '',
                ]);
            }

            $outputTable->render();
        }
    }
}
