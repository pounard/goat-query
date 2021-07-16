<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\Converter;
use Goat\Converter\ConverterContext;
use Goat\Driver\Platform\Escaper\Escaper;

/**
 * Transparently handles blob/bytea conversion.
 *
 * Driver specific implementation should extend this class.
 */
class RunnerConverter implements Converter
{
    private Converter $decorated;
    private Escaper $escaper;

    public function __construct(Converter $decorated, Escaper $escaper)
    {
        $this->decorated = $decorated;
        $this->escaper = $escaper;
        $this->initialize($decorated);
    }

    /**
     * Allow extenders to initialize this instance.
     *
     * @todo This is wrong because implementors do register custom converters
     *   on the decorated instance, the end result is that since it's shared
     *   between runners, if you have different runner implementations running
     *   at the same time, they will all inherit from each other's specific
     *   converter, and it may invoke converter's mayhem.
     */
    protected function initialize(Converter $decorated): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(/* mixed */ $value, ?string $sqlType, ?ConverterContext $context = null): ?string
    {
        if ('bytea' === $sqlType || 'blob' === $sqlType) {
            return $this->escaper->escapeBlob($value);
        }

        return $this->decorated->toSQL($value, $sqlType, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL($value, ?string $sqlType, ?string $phpType, ?ConverterContext $context = null)
    {
        if ('bytea' === $sqlType || 'blob' === $sqlType) {
            return $this->escaper->unescapeBlob($value);
        }

        return $this->decorated->fromSQL($value, $sqlType, $phpType, $context);
    }
}
