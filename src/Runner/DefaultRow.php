<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterContext;
use Goat\Runner\Metadata\ResultMetadata;

/**
 * Single row ow fetched from a result iterator.
 *
 * @todo
 *  - should we keep a computed (with explicit php type) value cache?
 *  - is it really useful to keep the auto values cache?
 */
final class DefaultRow implements Row
{
    private array $rawValues;
    private ConverterContext $converterContext;
    private ResultMetadata $resultMetadata;

    /** @var array<string,mixed> */
    private ?array $autoValues = null;

    public function __construct(
        array $rawValues,
        ConverterContext $converterContext,
        ResultMetadata $resultMetadata
    ) {
        $this->converterContext = $converterContext;
        $this->rawValues = $rawValues;
        $this->resultMetadata = $resultMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, ?string $phpType = null)
    {
        if (\is_int($name)) {
            $name = $this->resultMetadata->getColumnName($name);
        } else if (!$this->resultMetadata->columnExists($name)) {
            throw new InvalidDataAccessError(\sprintf("Column '%s' does not exist in result.", $name));
        }

        $value = $this->rawValues[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (null === $phpType && null !== $this->autoValues) {
            return $this->autoValues[$name] ?? null;
        }

        $context = $this->getConverterContext();

        return $context->getConverter()->fromSQL(
            $value,
            $this->resultMetadata->getColumnType($name),
            $phpType,
            $this->converterContext
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has($name): bool
    {
        if (\is_int($name)) {
            try {
                $name = $this->resultMetadata->getColumnName($name);
            } catch (InvalidDataAccessError $e) {
                return false;
            }
        }

        return $this->resultMetadata->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function raw($name)
    {
        if (\is_int($name)) {
            $name = $this->resultMetadata->getColumnName($name);
        } else if (!$this->resultMetadata->columnExists($name)) {
            throw new InvalidDataAccessError(\sprintf("Column '%s' does not exist in result.", $name));
        }

        return $this->rawValues[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResultMetadata(): ResultMetadata
    {
        return $this->resultMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getConverterContext(): ConverterContext
    {
        return $this->converterContext;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->rawValues;
    }

    /**
     * {@inheritdoc}
     */
    public function toHydratedArray(): array
    {
        return $this->autoValues ?? ($this->autoValues = $this->autoHydrate());
    }

    /**
     * Hydrate raw values automatically by guessing expected types.
     */
    private function autoHydrate(): array
    {
        $ret = [];

        $converter = $this->converterContext->getConverter();

        foreach ($this->rawValues as $name => $value) {
            $name = (string) $name; // Column name can be an integer (eg. SELECT 1 ...).
            if (null !== $value) {
                $ret[$name] = $converter->fromSQL(
                    $value,
                    $this->resultMetadata->getColumnType($name),
                    null, // Automatic PHP type guessing.
                    $this->converterContext
                );
            } else {
                $ret[$name] = null;
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", Row::class), E_USER_DEPRECATED);

        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", Row::class), E_USER_DEPRECATED);

        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new InvalidDataAccessError("Result rows are immutable.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new InvalidDataAccessError("Result rows are immutable.");
    }
}
