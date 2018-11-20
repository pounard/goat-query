<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Hydrator\HydratorMap;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

abstract class AbstractRunner implements Runner, EscaperInterface
{
    private $hydratorMap;
    private $queryBuilder;
    protected $converter;
    protected $dsn;
    protected $escaper;
    protected $formatter;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->formatter = $this->createFormatter();
        $this->formatter->setEscaper($this);
        $this->setConverter(new DefaultConverter());
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder ?? ($this->queryBuilder = new DefaultQueryBuilder($this));
    }

    /**
     * Set converter
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = $converter;
        $this->formatter->setConverter($converter);
    }

    /**
     * Get escaper
     */
    final protected function getEscaper(): EscaperInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings): string
    {
        if (!$strings) {
            throw new QueryError("cannot not format an empty identifier list");
        }
        if (!\is_array($strings)) {
            $strings = [$strings];
        }

        return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydratorMap(HydratorMap $hydratorMap): void
    {
        $this->hydratorMap = $hydratorMap;
    }

    /**
     * {@inheritdoc}
     */
    final public function getHydratorMap(): HydratorMap
    {
        if (!$this->hydratorMap) {
            throw new \BadMethodCallException("There is no hydrator configured");
        }

        return $this->hydratorMap;
    }

    /**
     * Create SQL formatter
     *
     * @return FormatterInterface
     */
    abstract protected function createFormatter(): FormatterInterface;

    /**
     * Do create iterator
     *
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     */
    abstract protected function doCreateResultIterator(...$constructorArgs) : ResultIterator;

    /**
     * Create the result iterator instance
     *
     * @param string[] $options
     *   Query options
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIterator
     */
    final protected function createResultIterator($options = null, ...$constructorArgs): ResultIterator
    {
        $result = $this->doCreateResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);

        if ($options) {
            if (\is_string($options)) {
                $options = ['class' => $options];
            } else if (!\is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->getHydratorMap()->get($options['class']));
        }

        return $result;
    }
}
