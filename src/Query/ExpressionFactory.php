<?php

declare(strict_types=1);

namespace Goat\Query;

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
        if (\is_callable($expression)) {
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
            return new ExpressionRaw('*');
        }

        return self::column($expression);
    }

    /**
     * Normalize column or column alias identifier
     */
    public static function column($expression, $context = null): Expression
    {
        self::ensureNotNull($expression);
        $expression = self::callable($expression, $context);

        if ($expression instanceof Expression) {
            return $expression;
        }

        if (\is_string($expression)) {
            return new ExpressionColumn($expression);
        }

        throw new QueryError(\sprintf("column reference must be a string or an instance of %s", ExpressionColumn::class));
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

        return ExpressionValue::create($expression);
    }

    /**
     * Normalize any expression
     *
     * If null is returned, this means that callback took control.
     */
    public static function raw($expression, array $arguments = [], $context = null): ?Expression
    {
        self::ensureNotNull($expression);
        $expression = self::callable($expression, $context);

        // Callback did not return anything, but had the chance to play with context
        if ($context && null === $expression) {
            return null;
        }

        if ($expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(\sprintf("you cannot pass an %s instance and arguments along", Expression::class));
            }
            return $expression;
        }

        if (\is_scalar($expression)) {
            return ExpressionRaw::create($expression, $arguments);
        }

        throw new QueryError(\sprintf("raw expression must be a scalar or an instance of %s", Expression::class));
    }
}