<?php

declare(strict_types=1);

namespace Goat\Driver\Platform;

use Goat\Driver\ConfigurationError;
use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Platform\Query\PgSQLWriter;
use Goat\Driver\Platform\Transaction\PgSQLTransaction;
use Goat\Driver\Query\SqlWriter;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Schema\SchemaIntrospector;

class PgSQLPlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function supportsSchema(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning(): bool
    {
        return true;
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
        return true;
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
        return new PgSQLWriter($escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(Runner $runner, int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new PgSQLTransaction($runner, $isolationLevel);
    }
}
