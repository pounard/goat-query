<?php

declare(strict_types=1);

namespace Goat\Driver\Platform;

use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Query\SqlWriter;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Schema\SchemaIntrospector;

interface Platform
{
    /**
     * Get server version.
     */
    public function getServerVersion(): ?string;

    /**
     * Does this platform supports schemas.
     */
    public function supportsSchema(): bool;

    /**
     * Does this platform supports SQL standard RETURNING clause
     */
    public function supportsReturning(): bool;

    /**
     * Does this platform supports transaction savepoints
     */
    public function supportsTransactionSavepoints(): bool;

    /**
     * Does this platform supports deferring constraints
     */
    public function supportsDeferingConstraints(): bool;

    /**
     * Get schema introspector.
     */
    public function getSchemaIntrospector(): SchemaIntrospector;

    /**
     * Get escaper.
     */
    public function getEscaper(): Escaper;

    /**
     * Get SQL query writer.
     */
    public function getSqlWriter(): SqlWriter;

    /**
     * Create a non started yet transaction object.
     *
     * @todo I'm not sure this is the right place it needs the runner.
     */
    public function createTransaction(Runner $runner, int $isolationLevel = Transaction::REPEATABLE_READ): Transaction;
}
