<?php

declare (strict_types=1);

namespace Goat\Console\Command;

use Goat\Console\App;
use Goat\Schema\Analytics\PgSQLStatisticsAggregator;
use Goat\Schema\Analytics\PgSQLTableStatistics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PgSQLStatCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pg:stat');
        $this->setAliases(['pgs']);
        $this->setDescription("Get statistics on database tables (PostgreSQL only).");
        $this->addOption('table', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "List columns of table(s), default schema is 'public' if not specified.");

        App::configureCommand($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = App::getDatabaseDriver($input);
        $runner = $driver->getRunner();

        $schema = $input->getOption('schema') ?? 'public';

        if ($tables = $input->getOption('table')) {
            throw new \Exception("Not implemented yet.");
        }

        $aggregator = new PgSQLStatisticsAggregator($runner);
        $values = $aggregator->fetchSchemaStatistics($schema);

        $outputTable = new Table($output);
        $outputTable->setHeaders([
            'table',
            // Sizes.
            's/table',
            's/index',
            's/total',
            'rows',
            'live',
            // Seq VS idx scans.
            'idx/sn',
            'idx/rd',
            'seq/sn',
            'seq/rd',
        ]);

        foreach ($values as $table) {
            \assert($table instanceof PgSQLTableStatistics);

            $outputTable->addRow([
                $table->table,
                // Sizes.
                self::right(self::getHumanFilesize($table->sizeTable)),
                self::right(self::getHumanFilesize($table->sizeIndex)),
                self::right(self::getHumanFilesize($table->sizeTotal)),
                self::right(self::getHumanCount($table->rowCount)),
                self::right(self::getHumanCount($table->stateLive)),
                // Seq VS idx scans.
                self::right(self::getHumanCount($table->readIndexScans)),
                self::right(self::getHumanCount($table->readIndexTupFetches)),
                self::right(self::getHumanCount($table->readSeqScans)),
                self::right(self::getHumanCount($table->readSeqTupReads)),
            ]);
        }

        $outputTable->render();

        $output->writeln([
            "Regarding row count, all are estimates:",
            " - 'rows' is computed during VACUUM or ANALYZE, found in pg_class table,",
            " - 'live' is the estimated row count from pg stat collector.",
        ]);

        return self::SUCCESS;
    }

    private static function right($value): TableCell
    {
        return new TableCell((string) $value, [
            'style' => new TableCellStyle([
                'align' => 'right',
            ]),
        ]);
    }

    /**
     * From size in bytes to human readable size string.
     *
     * @see https://stackoverflow.com/questions/15188033/human-readable-file-size
     */
    public static function getHumanCount(?int $bytes, int $dec = 2): ?string
    {
        if (null === $bytes) {
            return null;
        }

        $size = [' ', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $factor = \floor((\strlen((string) $bytes) - 1) / 3);

        return \sprintf("%.{$dec}f", $bytes / \pow(1000, $factor)) . ' ' . $size[$factor];
    }

    /**
     * From size in bytes to human readable size string.
     *
     * @see https://stackoverflow.com/questions/15188033/human-readable-file-size
     */
    public static function getHumanFilesize(int $bytes, int $dec = 2): ?string
    {
        $size = ['B ', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = \floor((\strlen((string) $bytes) - 1) / 3);

        return \sprintf("%.{$dec}f", $bytes / \pow(1024, $factor)) . ' ' . $size[$factor];
    }
}
