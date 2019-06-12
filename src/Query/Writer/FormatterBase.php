<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Converter\ConverterInterface;
use Goat\Query\ArgumentBag;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\Statement;

/**
 * Query rewriting and placeholder generation logic.
 */
abstract class FormatterBase implements FormatterInterface
{
    /**
     * Escape sequence matching magical regex.
     *
     * Order is important:
     *
     *   - ESCAPE will match all driver-specific string escape sequence,
     *     therefore will prevent any other matches from happening inside,
     *
     *   - "?::WORD" will superseed "?"
     *
     *   - any "::WORD" sequence, which is a valid SQL cast, will be left
     *     as-is and required no rewrite, but will superseed ":NAME"
     *     placeholders.
     *
     * I belive that a real parser would be much more efficient, if it was
     * written in any language other than PHP, but right now, preg will
     * actually be a lot faster than we will ever be.
     *
     * This regex is huge, but contain no backward lookup, does not imply
     * any recursivity, it should be very fast.
     */
    const PARAMETER_MATCH = '@
        ESCAPE
        (\?\:\:([\w]+))|        # Matches ?::WORD placeholders
        (\?)|                   # Matches ?
        (\:\:[\w\."]+)|         # Matches valid ::WORD cast
        (\:[\w]+\:\:([\w]+))|   # Matches :NAME::WORD placeholders
        (\:[\w]+)               # Matches :NAME placeholders
        @x';

    private $matchParametersRegex;
    protected $escaper;
    protected $converter;

    /**
     * Default constructor, it may be override by drivers, but don't forget that
     * it is MANDATORY to call setEscaper().
     */
    public function __construct(EscaperInterface $escaper)
    {
        $this->setEscaper($escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function setEscaper(EscaperInterface $escaper): void
    {
        $this->escaper = $escaper;
        $this->buildParameterRegex();
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    /**
     * Allows the driver to proceed to different type cast.
     *
     * For example, MySQL as types that are not identified by the same string
     * when casted and in other conditions, for example to cast an "int" you
     * need to write CAST(? AS SIGNED INTEGER), if you omit the SIGNED cast
     * won't work and MySQL will raise errors. This method exists primarily
     * for the formatter to fix those edge cases.
     *
     * @param string $type
     *   The internal type carried by converters
     *
     * @return string
     *   The real type the server will understand
     */
    protected function getCastType(string $type): string
    {
        return $type;
    }

    /**
     * Write cast clause
     *
     * @param string $placeholder
     *   Placeholder for the value
     * @param string $type
     *   SQL datatype
     *
     * @return string
     *
     * @codeCoverageIgnore
     *   You should probably override this method.
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // This is supposedly SQL-92 standard compliant, but can be overriden
        return \sprintf("cast(%s as %s)", $placeholder, $type);
    }

    /**
     * Uses the connection driven escape sequences to build the parameter
     * matching regex.
     */
    final private function buildParameterRegex(): void
    {
        // Please see this really excellent Stack Overflow answer:
        //   https://stackoverflow.com/a/23589204
        $patterns = [];

        foreach ($this->escaper->getEscapeSequences() as $sequence) {
            $sequence = \preg_quote($sequence);
            $patterns[] = \sprintf("%s.+%s", $sequence, $sequence);
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
     * @param ArgumentBag $parameters
     *   If the original query was from the query builder, this holds the
     * @param mixed[] $overrides
     *   Parameters overrides
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    final private function rewriteQueryAndParameters(string $formattedSQL, ArgumentBag $arguments): FormattedQuery
    {
        $index = 0;
        $typeMap = [];

        // See https://stackoverflow.com/a/3735908 for the  starting
        // sequence explaination, the rest should be comprehensible.
        $preparedSQL = \preg_replace_callback(
            $this->matchParametersRegex,
            function ($matches) use (&$index, $arguments, &$typeMap) {

                // Excludes the following:
                //   - strings that don't start with ? (placeholders),
                //   - strings that don't start with : (escape sequences)
                //   - strings that start with : but with a second : (valid pgsql cast)
                $length = \strlen($matched = $matches[0]);
                if ('?' !== ($first = $matched[0]) && ($length < 2 || ':' !== $first || ':' === $matched[1])) {
                    return $matches[0];
                }

                $placeholder = $this->escaper->writePlaceholder($index);

                // Do not attempt to match unknonwn types from here, just let
                // them pass outside of the \preg_replace_callback() call.
                if ((($type = $matches[3]) || ($type = $matches[7] ?? null) || ($type = $arguments->getTypeAt($index)))) {
                    if ($this->converter && ($cast = $this->converter->cast($type))) {
                        $placeholder = $this->writeCast($placeholder, $this->getCastType($cast));
                    }
                    $typeMap[$index] = $type;
                }

                ++$index;

                return $placeholder;
            },
            $formattedSQL
        );

        if ($index !== $arguments->count()) {
            throw new QueryError(\sprintf("Invalid parameter number bound"));
        }

        return (new FormattedQuery(
            $preparedSQL, $arguments->withTypes($typeMap)
        ))->setConverter($this->converter);
    }

    /**
     * Rewrite query by adding type cast information and correct placeholders
     *
     * @param string|Statement $query
     * @param mixed[]|ArgumentBag $parameters
     *
     * @return FormattedQuery
     */
    final public function prepare($query, $parameters = []): FormattedQuery
    {
        if (!\is_string($query)) {
            if (!$query instanceof Statement) {
                throw new QueryError(\sprintf("query must be a bare string or an instance of %s", Query::class));
            }
            $arguments = $query->getArguments()->merge($parameters ?? []);
            $query = $this->format($query);
        } else if ($parameters instanceof ArgumentBag) {
            $arguments = $parameters;
        } else {
            $arguments = new ArgumentBag();
            $arguments->appendArray($parameters ?? []);
            $arguments->lock();
        }

        return $this->rewriteQueryAndParameters($query, $arguments);
    }
}
