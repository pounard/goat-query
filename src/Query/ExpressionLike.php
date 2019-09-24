<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a condition/comparison expression
 */
final class ExpressionLike implements Expression
{
    const DEFAULT_WIDCARD = '?';

    private $column;
    private $operator;
    private $pattern;
    private $value;
    private $wildcard;

    /**
     * Default constructor
     */
    private function __construct()
    {
    }

    /**
     * Create instance
     *
     * @param string|Expression $column
     *   Column, or expression that can be compared against, anything will do
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, use ? for value replacement
     * @param ?string $value
     *   Any value to replace within pattern
     * @param ?string $wildcard
     *   Wilcard if different, default is ?
     */
    public static function like($column, string $pattern, ?string $value = null, ?string $wildcard = null): self
    {
        $column = ExpressionFactory::column($column);
        $wildcard = $wildcard ?? self::DEFAULT_WIDCARD;

        if ($value && false === \strpos($pattern, $wildcard)) {
            throw new QueryError("you provided a value but wildcard could not be found in pattern");
        }

        $ret = new self;
        $ret->column = $column;
        $ret->operator = Where::LIKE;
        $ret->pattern = $pattern;
        $ret->value = $value;
        $ret->wildcard = $wildcard;

        return $ret;
    }

    /**
     * Create not like instance
     *
     * @see ExpressionLike::like()
     *   For parameters documentation.
     */
    public static function notLike($column, string $pattern, ?string $value = null, ?string $wildcard = null): self
    {
        $ret = self::like($column, $pattern, $value, $wildcard);
        $ret->operator = Where::NOT_LIKE;

        return $ret;
    }

    /**
     * Create case insensitive like instance
     *
     * @see ExpressionLike::like()
     *   For parameters documentation.
     */
    public static function iLike($column, string $pattern, ?string $value = null, ?string $wildcard = null): self
    {
        $ret = self::like($column, $pattern, $value, $wildcard);
        $ret->operator = Where::ILIKE;

        return $ret;
    }

    /**
     * Create case insensitive not like instance
     *
     * @see ExpressionLike::like()
     *   For parameters documentation.
     */
    public static function notILike($column, string $pattern, ?string $value = null, ?string $wildcard = null): self
    {
        $ret = self::like($column, $pattern, $value, $wildcard);
        $ret->operator = Where::NOT_ILIKE;

        return $ret;
    }

    /**
     * Get relation
     */
    public function getColumn(): Expression
    {
        return $this->column;
    }

    /**
     * Is there a value
     */
    public function hasValue(): bool
    {
        return null !== $this->value;
    }

    /**
     * Get user-provided value
     */
    public function getUnsaveValue(): ?string
    {
        return $this->value;
    }

    /**
     * Proceed to value replacement and get pattern
     */
    public function getPattern(?string $escapedValue = null): string
    {
        if (null === $escapedValue) {
            return $this->pattern;
        }

        return \str_replace($this->wildcard, $escapedValue, $this->pattern);
    }

    /**
     * Get schema
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return new ArgumentBag();
    }
}
