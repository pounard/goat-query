<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\Impl\PgSQLFormatter;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\DatabaseError;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIterator;
use Goat\Runner\Transaction;

/**
 * ext_pgsql connection implementation
 */
class ExtPgSQLRunner extends AbstractRunner
{
    use ExtPgSQLErrorTrait;

    /** @var resource */
    private $connection;

    /** @var string[] */
    private $prepared = [];

    /**
     * Constructor
     *
     * @param resource $resource
     *   pgsql extension connection resource.
     */
    public function __construct($connection)
    {
        parent::__construct();

        if (!\is_resource($connection)) {
            throw new QueryError(\sprintf("First parameter must be a resource, %s given", \gettype($connection)));
        }
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'pgsql';
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
    public function supportsDeferingConstraints(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(new PgSQLConverter($converter));
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter(): FormatterInterface
    {
        return new PgSQLFormatter($this->getEscaper());
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '"',  // Identifier escape character
            '\'', // String literal escape character
            '$$', // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     *
    protected function fetchDatabaseInfo() : array
    {
        $conn = $this->getConn();
        $resource = @\pg_query($conn, "select version();");

        if (false === $resource) {
            $this->driverError($conn);
        }

        $row = @\pg_fetch_array($resource);
        if (false === $row) {
            $this->resultError($resource);
        }

        // Example string to parse:
        //   PostgreSQL 9.2.9 on x86_64-unknown-linux-gnu, compiled by gcc (GCC) 4.4.7 20120313 (Red Hat 4.4.7-4), 64-bit
        $string = \reset($row);
        $pieces = \explode(', ', $string);
        $server = \explode(' ', $pieces[0]);

        return [
            'name'    => $server[0],
            'version' => $server[1],
            'arch'    => $pieces[2],
            'os'      => $server[3],
            'build'   => $pieces[1],
        ];
    }
     */

    /**
     * {@inheritdoc}
     *
    public function close()
    {
        if ($this->conn) {
            @\pg_close($this->conn);
        }
    }
     */

    /**
     * Get connection resource
     *
     * @return resource
     */
    protected function getConnection()
    {
        // @todo keeping this for allow initalizer injection later
        if (!$this->connection) {
            // $this->connection = \pg_connect($this->dsn->formatPgSQL(), PGSQL_CONNECT_FORCE_NEW);

            //if (false === $this->conn) {
                throw new QueryError(\sprintf("Error connecting to the database with parameters '%s'.", $this->dsn->formatFull()));
            //}

            /*
            if ($this->configuration) {
                $this->sendConfiguration($this->configuration);
            }
             */
        }

        return $this->connection;
    }

    /**
     * {@inheritdoc}
     *
    public function setClientEncoding(string $encoding)
    {
        // https://www.postgresql.org/docs/9.3/static/multibyte.html#AEN34087
        // @todo investigate differences between versions

        throw new NotImplementedError();

        // @todo this cannot work
        $this
            ->getConn()
            ->query(
                \sprintf(
                    "SET CLIENT_ENCODING TO %s",
                    $this->getEscaper()->escapeLiteral($encoding)
                )
            )
        ;
    }
     */

    /**
     * Send PDO configuration
     *
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getConn();

        foreach ($configuration as $key => $value) {
            $pdo->query(\sprintf(
                "SET %s TO %s",
                $this->getEscaper()->escapeIdentifier($key),
                $this->getEscaper()->escapeLiteral($value)
            ));
        }

        return $this;
    }
     */

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new PgSQLTransaction($this, $isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs) : ResultIterator
    {
        return new ExtPgSQLResultIterator(...$constructorArgs);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query, $arguments = null, $options = null): ResultIterator
    {
        if ($query instanceof Query) {
            if (!$query->willReturnRows()) {
                $affectedRowCount = $this->perform($query, $arguments, $options);

                return new EmptyResultIterator($affectedRowCount);
            }
        }

        $rawSQL = '';
        $connection = $this->getConnection();

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);

            $resource = @\pg_query_params($connection, $rawSQL, $args);

            if (false === $resource) {
                $this->driverError($connection, $rawSQL);
            }

            return $this->createResultIterator($prepared->getIdentifier(), $options, $resource);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DriverError($rawSQL, $args, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null): int
    {
        $rawSQL = '';
        $connection = $this->getConnection();

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);

            $resource = @\pg_query_params($connection, $rawSQL, $args);

            if (false === $resource) {
                $this->driverError($connection, $rawSQL);
            }

            $rowCount = @\pg_affected_rows($resource);
            if (false === $rowCount) {
                $this->resultError($resource);
            }

            // No need to keep any result into memory.
            @\pg_free_result($resource);

            return $rowCount;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DriverError($rawSQL, $args, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, ?string $identifier = null): string
    {
        $rawSQL = '';
        $connection = $this->getConnection();

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();

            if (null === $identifier) {
                $identifier = \md5($rawSQL);
            }
            // @merge argument types from query

            if (false === @\pg_prepare($connection, $identifier, $rawSQL)) {
                $this->driverError($connection);
            }

            $this->prepared[$identifier] = $prepared;

            return $identifier;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DriverError($rawSQL, null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(\sprintf("'%s': query was not prepared", $identifier));
        }

        $prepared = $this->prepared[$identifier];
        $connection = $this->getConnection();

        try {
            /** @var \Goat\Query\Writer\FormattedQuery $prepared */
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);

            $resource = @\pg_execute($connection, $identifier, $args);

            if (false === $resource) {
                $this->driverError($connection, $identifier);
            }

            return $this->createResultIterator($identifier, $options, $resource);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DriverError($identifier, $args, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index) : string
    {
        return '$' . ($index + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        // @todo This should be tested for performance.
        // return '"' . \str_replace('"', '""', $string) . '"';

        if ('' === $string) {
            return '';
        }

        $escaped = @\pg_escape_identifier($this->connection, $string);
        if (false === $escaped) {
            $this->driverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string): string
    {
        if ('' === $string) {
            return '';
        }

        $escaped = @\pg_escape_literal($this->connection, $string);
        if (false === $escaped) {
            $this->driverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string): string
    {
        return \addcslashes($string, '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word): string
    {
        if ('' === $word) {
            return '';
        }

        $escaped = @\pg_escape_bytea($this->connection, $word);
        if (false === $escaped) {
            $this->driverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource): ?string
    {
        if ('' === $resource || null === $resource) {
            return $resource;
        }

        $unescaped = @\pg_unescape_bytea($resource);
        if (false === $unescaped) {
            $this->driverError($this->connection);
        }

        return $unescaped;
    }
}
