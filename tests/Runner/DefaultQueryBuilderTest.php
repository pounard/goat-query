<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Runner\DefaultQueryBuilder;
use PHPUnit\Framework\TestCase;

class DefaultQueryBuilderTest extends TestCase
{
    public function testBasics()
    {
        $builder = new DefaultQueryBuilder();

        $this->assertInstanceOf(SelectQuery::class, $query = $builder->select('some_table', 'some_alias'));
        $this->assertSame('some_table', ($relation = $query->getRelation())->getName());
        $this->assertSame('some_alias', $relation->getAlias());

        $this->assertInstanceOf(DeleteQuery::class, $query = $builder->delete('some_table', 'some_alias'));
        $this->assertSame('some_table', ($relation = $query->getRelation())->getName());
        $this->assertSame('some_alias', $relation->getAlias());

        $this->assertInstanceOf(InsertQueryQuery::class, $query = $builder->insertQuery('some_table'));
        $this->assertSame('some_table', $query->getRelation()->getName());

        $this->assertInstanceOf(InsertValuesQuery::class, $query = $builder->insertValues('some_table'));
        $this->assertSame('some_table', $query->getRelation()->getName());

        $this->assertInstanceOf(UpdateQuery::class, $query = $builder->update('some_table', 'some_alias'));
        $this->assertSame('some_table', ($relation = $query->getRelation())->getName());
        $this->assertSame('some_alias', $relation->getAlias());
    }
}
