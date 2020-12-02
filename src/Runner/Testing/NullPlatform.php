<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Driver\Platform\Platform;
use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Driver\Query\SqlWriter;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Schema\SchemaIntrospector;

class NullPlatform implements Platform
{
    /** @var Escaper */
    private $escaper;

    /** @var SqlWriter */
    private $writer;

    public function __construct()
    {
        $this->escaper = new NullEscaper();
        $this->writer = new DefaultSqlWriter($this->escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion(): ?string
    {
        return null;
    }

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
     * Get escaper.
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlWriter(): SqlWriter
    {
        return $this->writer;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchemaIntrospector(Runner $runner): SchemaIntrospector
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(Runner $runner, int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }
}
