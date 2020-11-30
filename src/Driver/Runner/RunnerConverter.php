<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
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
     * @deprecated
     */
    public function getClientTimeZone(): string
    {
        return $this->decorated->getClientTimeZone();
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function setClientTimeZone(?string $clientTimeZone = null): void
    {
        $this->decorated->setClientTimeZone($clientTimeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value): string
    {
        return $this->decorated->guessType($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(string $sqlType): ?string
    {
        return $this->decorated->getPhpType($sqlType);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value): ?string
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->escapeBlob($value);
        }
        return $this->decorated->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->unescapeBlob($value);
        }
        return $this->decorated->fromSQL($type, $value);
    }
}
