<?php

declare(strict_types=1);

namespace Goat\Benchmark\Converter;

use Goat\Converter\ConverterContext;
use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Converter\ValueConverterRegistry;
use Goat\Converter\Driver\PgSQLArrayConverter;
use Goat\Runner\SessionConfiguration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @BeforeMethods({"setUp"})
 */
final class ConversionBench
{
    private ConverterInterface $converter;
    private ConverterContext $context;

    private UuidInterface $dataRamseyUuid;
    private string $dataRamseyUuidAsString;

    private array $dataArray;
    private string $dataArrayAsString;

    public function setUp(): void
    {
        $this->converter = new DefaultConverter();
        $this->context = new ConverterContext($this->converter, SessionConfiguration::empty());

        $valueConverterRegistry = new ValueConverterRegistry();
        $valueConverterRegistry->register(new PgSQLArrayConverter());
        $this->converter->setValueConverterRegistry($valueConverterRegistry);

        $this->dataRamseyUuid = Uuid::fromString('a9336bfe-1a3b-4d14-a2da-38b819da0e96');
        $this->dataRamseyUuidAsString = 'a9336bfe-1a3b-4d14-a2da-38b819da0e96';

        $this->dataArray = ["foo", "bar", "l''och"];
        $this->dataArrayAsString = "{foo,bar,l''och}";
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchIntFromSql(): void
    {
        $this->converter->fromSQL('int8', '152485788', $this->context);
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchIntToSql(): void
    {
        $this->converter->toSQL('int8', 152485788, $this->context);
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchRamseyUuidFromSql(): void
    {
        $this->converter->fromSQL('uuid', $this->dataRamseyUuidAsString, $this->context);
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchRamseyUuidToSql(): void
    {
        $this->converter->toSQL('uuid', $this->dataRamseyUuid, $this->context);
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchArrayFromSql(): void
    {
        $this->converter->fromSQL('varchar[]', $this->dataArrayAsString, $this->context);
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchArrayToSql(): void
    {
        $this->converter->toSQL('varchar[]', $this->dataArray, $this->context);
    }
}
