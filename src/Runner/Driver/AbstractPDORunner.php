<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIterator;

/**
 * PDO runner basics. In theory, you have to implement those three methods
 * to make it work, they cannot be implemented generically because PDO has
 * no method to escaped identifiers, and formatter will depend upon the
 * underlaying database driver:
 *
 * protected function createFormatter(): FormatterInterface;
 * public function getEscapeSequences(): array;
 * public function escapeIdentifier(string $string): string;
 */
abstract class AbstractPDORunner extends AbstractRunner
{
    private $connection;
    private $prepared = [];

    /**
     * Default constructor
     */
    public function __construct(\PDO $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function isResultMetadataSlow(): bool
    {
        return true;
    }

    /**
     * Get PDO instance, connect if not connected
     */
    final protected function getPdo(): \PDO
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs): ResultIterator
    {
        return new PDOResultIterator(...$constructorArgs);
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

        $args = [];
        $rawSQL = '';

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);

            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            return $this->createResultIterator($prepared->getIdentifier(), $options, $statement);

        } catch (QueryError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($rawSQL, $arguments, $e);
        } catch (\Exception $e) {
            throw new DriverError($rawSQL, $arguments, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null) : int
    {
        $args = [];
        $rawSQL = '';

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);

            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            return $statement->rowCount();

        } catch (QueryError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($rawSQL, $arguments, $e);
        } catch (\Exception $e) {
            throw new DriverError($rawSQL, $arguments, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null): string
    {
        $rawSQL = '';

        try {
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();

            if (null === $identifier) {
                $identifier = \md5($rawSQL);
            }
            // @merge argument types from query

            $this->prepared[$identifier] = [
                $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]),
                $prepared,
            ];

            return $identifier;

        } catch (QueryError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($rawSQL, [], $e);
        } catch (\Exception $e) {
            throw new DriverError($rawSQL, [], $e);
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

        list($statement, $prepared) = $this->prepared[$identifier];

        try {
            /** @var \Goat\Query\Writer\FormattedQuery $prepared */
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);
            $statement->execute($args);

            return $this->createResultIterator($identifier, $options, $statement);

        } catch (QueryError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new DriverError($identifier, [], $e);
        } catch (\Exception $e) {
            throw new DriverError($identifier, [], $e);
        }
    }

    /**
     * Ensures that the identifier does not contain any ? sign, this is due to
     * the fact that PDO has a real bug out there: where it does gracefull
     * detects that ? in string literals are not parameters, it fails when
     * it ? is in an MySQL or PostgreSQL identifier literal, as well as sometime
     * it fails when it is in a PostgreSQL string constant (enclosed with $$).
     *
     * What this function does is simply throwing exceptions when there is any
     * number of ? sign in the identifier.
     *
     * For more documentation, you may read this informative Stack Overflow
     * thread, where the question is raised about ? in identifiers:
     *   https://stackoverflow.com/q/12092907
     *
     * Also note that there's an actual PDO bug opened, but I guess it will
     * never be fixed, it's too much of an edge case:
     *   https://bugs.php.net/bug.php?id=71628
     *
     * And yet I have absolutely no idea why, but using the pdo_pgsql driver
     * it does work gracefully, I guess this is because it considers that
     * strings enclosed by using double quote (") are string literals, and
     * this is the right way of escaping identifiers for PosgresSQL so this
     * passes silently and works gracefully.
    */
    protected function checkIdentifier(string $string): void
    {
        if (false !== \strpos($string, '?')) {
            throw new QueryError("PDO can't support '?' sign within identifiers, please read https://stackoverflow.com/q/12092907");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index): string
    {
        return '?';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string): string
    {
        return $this->connection->quote($string, \PDO::PARAM_STR);
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
        return $this->connection->quote($word /*, \PDO::PARAM_LOB */);
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource): ?string
    {
        // I have no idea why, but all of the sudden, PDO pgsql driver started
        // to send resources instead of data...
        if (\is_resource($resource)) {
            return \stream_get_contents($resource);
        }
        return $resource;
    }
}
