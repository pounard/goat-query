<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Driver\Platform\Escaper\Escaper;

/**
 * Transparently handles runner specificities.
 */
final class RunnerConverter implements ConverterInterface
{
    /** @var ConverterInterface */
    private $decorated;

    /** @var Escaper */
    private $escaper;

    public function __construct(ConverterInterface $decorated, Escaper $escaper)
    {
        $this->decorated = $decorated;
        $this->escaper = $escaper;
    }

    public function guessType($value): string
    {
        return $this->decorated->guessType($value);
    }

    public function getPhpType(string $sqlType): ?string
    {
        return $this->decorated->getPhpType($sqlType);
    }

    public function toSQL(string $type, $value): ?string
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->escapeBlob($value);
        }
        return $this->decorated->toSQL($type, $value);
    }

    public function fromSQL(string $type, $value)
    {
        if ('bytea' === $type || 'blob' === $type) {
            return $this->escaper->unescapeBlob($value);
        }
        return $this->decorated->fromSQL($type, $value);
    }
}
