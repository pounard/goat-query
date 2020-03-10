<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

use Goat\Query\QueryError;

trait PDOEscaperTrait /* implements Escaper */
{
    /** @var \PDO */
    private $connection;

    /** @var bool */
    private $doCheckIdentifiers = false;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->doCheckIdentifiers = $this->areIdentifiersSafe();
    }

    /**
     * Can identifiers contain '?'.
     */
    abstract protected function areIdentifiersSafe(): bool;

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
        if ($this->doCheckIdentifiers) {
            $this->checkIdentifier($string);
        }
        return $this->connection->quote($string, \PDO::PARAM_STR);
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
