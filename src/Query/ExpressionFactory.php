<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\ValueExpression;

/**
 * Input normalization functions
 *
 * @internal
 *   This is meant to be used internally and is subject to breaking changes.
 */
final class ExpressionFactory
{
    /**
     * Normalize error upon null or '' expressions
     */
    private static function ensureNotNull($expression)
    {
        if (null === $expression || '' == $expression) {
            throw new QueryError("Expression cannot be null or empty");
        }
    }

    /**
     * Process callback, it might return anything.
     *
     * If null is returned, it considers that the callback took control.
     */
    public static function callable($expression, $context = null)
    {
        // Do not permit 'function_name' type callables, because values
        // are sometime strings which actually might be PHP valid function
        // names such as 'exp' for example.
        if (\is_callable($expression) && (\is_array($expression) || $expression instanceof \Closure)) {
            return \call_user_func($expression, $context);
        }

        return $expression;
    }

    /**
     * Alias of normalizeColumnExpression() which allows '*'
     */
    public static function output($expression, $context = null): Expression
    {
        if ('*' === $expression) {
            return new RawExpression('*');
        }

        return self::column($expression);
    }

    /**
     * Normalize column or column alias identifier
     */
    public static function column($expression, $context = null): Statement
    {
        self::ensureNotNull($expression);
        $expression = self::callable($expression, $context);

        if ($expression instanceof Expression) {
            return $expression;
        }

        if (\is_string($expression)) {
            return new ColumnExpression($expression);
        }

        throw new QueryError(\sprintf("column reference must be a string or an instance of %s", ColumnExpression::class));
    }

    /**
     * Normalize value expression
     */
    public static function value($expression, $context = null): Statement
    {
        $expression = self::callable($expression, $context);

        // We voluntarily type using Statement here instead of expression
        // to avoid false negatives and attempt conversion of an already
        // correct object.
        if ($expression instanceof Statement) {
            return $expression;
        }

        return new ValueExpression($expression);
    }

    /**
     * Normalize any expression
     *
     * If null is returned, this means that callback took control.
     */
    public static function raw($expression, array $arguments = [], $context = null): ?Statement
    {
        self::ensureNotNull($expression);
        $expression = self::callable($expression, $context);

        // Callback did not return anything, but had the chance to play with context.
        // If $context === $expression, this means that the user used a short arrow
        // syntax that implicitely returned the value, we consider it as null.
        if ($context && null === $expression || $context === $expression) {
            return null;
        }

        if ($expression instanceof Statement) {
            if ($arguments) {
                throw new QueryError(\sprintf("you cannot pass an %s instance and arguments along", Statement::class));
            }
            return $expression;
        }

        if (\is_scalar($expression)) {
            return new RawExpression($expression, $arguments);
        }

        throw new QueryError(\sprintf("raw expression must be a scalar or an instance of %s", Statement::class));
    }
}
