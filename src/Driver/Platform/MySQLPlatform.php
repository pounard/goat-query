<?php

declare(strict_types=1);

namespace Goat\Driver\Platform;

use Goat\Driver\ConfigurationError;
use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Platform\Query\MySQL8Writer;
use Goat\Driver\Platform\Query\MySQLWriter;
use Goat\Driver\Platform\Transaction\MySQLTransaction;
use Goat\Driver\Query\SqlWriter;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Schema\SchemaIntrospector;

class MySQLPlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function supportsSchema(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransactionSavepoints(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchemaIntrospector(Runner $runner): SchemaIntrospector
    {
        throw new ConfigurationError("Schema introspector is not implemented yet.");
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(Runner $runner, int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new MySQLTransaction($runner, $isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSqlWriter(Escaper $escaper): SqlWriter
    {
        $serverVersion = $this->getServerVersion();

        if (0 < \version_compare('8', $serverVersion)) {
            return new MySQLWriter($escaper);
        }

        return new MySQL8Writer($escaper);
    }
}
