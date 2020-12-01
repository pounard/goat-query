<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterContext;
use Goat\Converter\ConverterInterface;
use Goat\Converter\ValueConverterRegistry;
use Goat\Driver\Platform\Escaper\Escaper;

/**
 * Transparently handles blob/bytea conversion.
 */
final class RunnerConverter implements ConverterInterface
{
    private ConverterInterface $decorated;
    private Escaper $escaper;

    public function __construct(ConverterInterface $decorated, Escaper $escaper)
    {
        $this->decorated = $decorated;
        $this->escaper = $escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function setValueConverterRegistry(ValueConverterRegistry $valueConverterRegistry): void
    {
        $this->decorated->setValueConverterRegistry($valueConverterRegistry);
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool
    {
        return $this->decorated->isTypeSupported($type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->escapeBlob($value);
        }

        return $this->decorated->toSQL($type, $value, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value, ConverterContext $context)
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->unescapeBlob($value);
        }

        return $this->decorated->fromSQL($type, $value, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterContext $context): string
    {
        return $this->decorated->guessType($value, $context);
    }
}
