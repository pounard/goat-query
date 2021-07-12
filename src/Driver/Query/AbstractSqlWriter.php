<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\Statement;
use Goat\Query\Expression\RawExpression;

/**
 * Query rewriting and placeholder generation logic.
 *
 * @todo Move this code into DefaultSqlWriter
 */
abstract class AbstractSqlWriter implements SqlWriter
{
    /**
     * Escape sequence matching magical regex.
     *
     * Order is important:
     *
     *   - ESCAPE will match all driver-specific string escape sequence,
     *     therefore will prevent any other matches from happening inside,
     *
     *   - "??" will always superseed "?*",
     *
     *   - "?::WORD" will superseed "?",
     *
     *   - any "::WORD" sequence, which is a valid SQL cast, will be left
     *     as-is and required no rewrite.
     *
     * After some thoughts, this needs serious optimisation.
     *
     * I believe that a real parser would be much more efficient, if it was
     * written in any language other than PHP, but right now, preg will
     * actually be a lot faster than we will ever be.
     *
     * This regex is huge, but contain no backward lookup, does not imply
     * any recursivity, it should be fast enough.
     */
    const PARAMETER_MATCH = '@
        ESCAPE
        (\?\?)|                 # Matches ??
        (\?((\:\:([\w]+))|))    # Matches ?[::WORD] (placeholders)
        @x';

    private string $matchParametersRegex;
    protected Escaper $escaper;

    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
        $this->buildParameterRegex();
    }

    /**
     * Uses the connection driven escape sequences to build the parameter
     * matching regex.
     */
    private function buildParameterRegex(): void
    {
        // Please see this really excellent Stack Overflow answer:
        //   https://stackoverflow.com/a/23589204
        $patterns = [];

        foreach ($this->escaper->getEscapeSequences() as $sequence) {
            $sequence = \preg_quote($sequence);
            $patterns[] = \sprintf("%s.+?%s", $sequence, $sequence);
        }

        if ($patterns) {
            $this->matchParametersRegex = \str_replace('ESCAPE', \sprintf("(%s)|", \implode("|", $patterns)), self::PARAMETER_MATCH);
        } else {
            $this->matchParametersRegex = \str_replace('ESCAPE', self::PARAMETER_MATCH);
        }
    }

    /**
     * Converts all typed placeholders in the query and replace them with the
     * correct CAST syntax, this will also convert the argument values if
     * necessary along the way.
     *
     * Matches the following things ANYTHING::TYPE where anything can be pretty
     * much anything except for a few SQL control chars, this will make the SQL
     * query writing very much easier for you.
     *
     * Please note that if a the same ANYTHING identifier is specified more than
     * once in the arguments array, with conflicting types specified, only the
     * first being found will do something.
     *
     * And finally, all found placeholders will be replaced by something we can
     * then match once again for placeholder rewrite.
     *
     * Once explicit cast conversion is done, it will attempt an automatic
     * replacement for all remaining values.
     *
     * @param string $formattedSQL
     *   Raw SQL from user input, or formatted SQL from the formatter
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    protected function parseExpression(RawExpression $expression, WriterContext $context): string
    {
        $asString = $expression->getString();
        $values = $expression->getArguments();

        if (!$values && false === \str_contains($asString, '?')) {
            // Performance shortcut for expressions containing no arguments.
            return $asString;
        }

        // See https://stackoverflow.com/a/3735908 for the  starting
        // sequence explaination, the rest should be comprehensible.
        $localIndex = -1;
        return \preg_replace_callback(
            $this->matchParametersRegex,
            function ($matches) use (&$localIndex, $values, $context) {
                $match  = $matches[0];

                if ('??' === $match) {
                    return $this->escaper->unescapePlaceholderChar();
                }
                if ('?' !== $match[0]) {
                    return $match;
                }

                $localIndex++;
                $value = $values[$localIndex] ?? null;

                if ($value instanceof Statement) {
                    // @todo prepare() Not in interface
                    return $this->format($value, $context);
                } else {
                    $index = $context->append($value, empty($matches[6]) ? null : $matches[6]);

                    return $this->escaper->writePlaceholder($index);
                }
            },
            $asString
        );
    }

    /**
     * {@inheritdoc}
     */
    final public function prepare($query, ?array $arguments = null, ?WriterContext $context = null): FormattedQuery
    {
        $preparedSQL = $identifier = null;
        $context = $context ?? new WriterContext();

        if (\is_string($query)) {
            $preparedSQL = $this->parseExpression(new RawExpression($query, $arguments ?? []), $context);
        } else if ($query instanceof Statement) {
            if ($query instanceof Query) {
                $identifier = $query->getIdentifier();
            }
            $preparedSQL = $this->format($query, $context);
        } else {
            throw new QueryError(\sprintf("query must be a bare string or an instance of %s", Statement::class));
        }

        return new FormattedQuery($preparedSQL, $identifier, $context->getArgumentBag());
    }
}
