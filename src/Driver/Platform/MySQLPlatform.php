<?php

declare(strict_types=1);

namespace Goat\Driver\Platform;

use Goat\Driver\ConfigurationError;
use Goat\Driver\Platform\Escaper\Escaper;
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
     * Get schema introspector
     */
    public function getSchemaIntrospector(): SchemaIntrospector
    {
        throw new ConfigurationError("Schema introspector is not implemented yet.");
    }

    /**
     * {@inheritdoc}
     */
    protected function createSqlWriter(Escaper $escaper): SqlWriter
    {
        return new MySQLWriter($escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(Runner $runner, int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new MySQLTransaction($runner, $isolationLevel);
    }
}
