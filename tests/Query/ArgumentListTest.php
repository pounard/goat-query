<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Converter\ConverterInterface;
use Goat\Query\ArgumentList;
use Goat\Query\QueryError;
use PHPUnit\Framework\TestCase;

class ArgumentListTest extends TestCase
{
    public function testAddParameterWithoutAnything()
    {
        $argumentList = new ArgumentList();
        $argumentList->addParameter();
        $this->assertSame(1, $argumentList->count());
        $this->assertSame(null, $argumentList->getTypeAt(0));
    }

    public function testAddParameterWithNameRaiseErrorOnDuplicate()
    {
        $argumentList = new ArgumentList();
        $argumentList->addParameter(null, 'foo');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/name is already in use/');
        $argumentList->addParameter(null, 'foo');
    }

    public function testGetNameIndex()
    {
        $argumentList = new ArgumentList();
        $argumentList->addParameter('int');
        $argumentList->addParameter('bigint', 'foo');
        $argumentList->addParameter('smallint');

        $this->assertSame(1, $argumentList->getNameIndex('foo'));

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/bar argument name/');
        $argumentList->getNameIndex('bar');
    }

    public function testWithTypesOfRaiseErrorOnSizeMismatch()
    {
        $argumentList = new ArgumentList();
        $argumentList->addParameter('int');
        $argumentList->addParameter('bigint');

        $other = new ArgumentList();
        $other->addParameter();

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/Length mismatch, awaiting 2 arguments, got 1/');
        $argumentList->withTypesOf($other);
    }

    public function testWithTypesIsNotOverridenByVoidTypes()
    {
        $argumentList = new ArgumentList();
        $argumentList->addParameter('int');
        $argumentList->addParameter('bigint');
        $argumentList->addParameter('smallint');
        $argumentList->addParameter('hugeint');

        $other = new ArgumentList();
        $other->addParameter();
        $other->addParameter(ConverterInterface::TYPE_UNKNOWN);
        $other->addParameter('');
        $other->addParameter('hugechar');

        $this->assertSame(
            ['int', 'bigint', 'smallint', 'hugechar'],
            $argumentList->withTypesOf($other)->getTypeMap()
        );
    }
}
