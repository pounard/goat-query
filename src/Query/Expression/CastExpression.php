<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\Statement;

/**
 * Cast expression is the same as a value expression, but type is mandatory.
 *
 * It will propagate the cast to generated SQL, and thus enforce the SQL to
 * proceed to the CAST explicitely.
 *
 * You may also provide a value type, for the PHP side, in order for the
 * converter to proceed to a different conversion than the SQL cast.
 */
class CastExpression implements Expression
{
    private string $sqlCastToType;
    private ?string $valueType;
    /** @var mixed|Expression */
    private $value;

    public function __construct($value, string $castToType, ?string $valueType = null)
    {
        $this->sqlCastToType = $castToType;

        if ($value instanceof Statement) {
            $this->value = $value;
            if ($valueType) {
                // @todo Raise warning: value type will be ignored
            }
        } else {
            $this->value = new ValueExpression($value, $valueType);
        }
    }

    /**
     * Get value.
     *
     * @return Expression
     *   This can return a value representation, or any expression.
     */
    public function getValue(): Expression
    {
        return $this->value;
    }

    /**
     * Get value type.
     */
    public function getCastToType(): string
    {
        return $this->sqlCastToType;
    }

    /**
     * Get value type, if none user-provided, SQL cast to type will be
     * returned instead.
     */
    public function getType(): string
    {
        return $this->valueType ?? $this->valueType;
    }
}
