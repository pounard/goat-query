<?php

declare(strict_types=1);

namespace Goat\Benchmark\Converter;

use Goat\Converter\Converter;
use Goat\Converter\ConverterContext;
use Goat\Converter\DefaultConverter;
use Goat\Converter\Driver\PgSQLArrayConverter;
use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Driver\Query\SqlWriter;
use Goat\Query\Query;
use Goat\Query\SelectQuery;
use Goat\Query\Expression\RawExpression;
use Goat\Runner\SessionConfiguration;
use Goat\Runner\Testing\NullEscaper;

/**
 * @BeforeMethods({"setUp"})
 */
final class SqlWriterBench
{
    private Converter $converter;
    private ConverterContext $context;
    private SqlWriter $writer;
    private Query $query;

    public function setUp(): void
    {
        $this->converter = new DefaultConverter();
        $this->context = new ConverterContext($this->converter, SessionConfiguration::empty());
        $this->converter->register(new PgSQLArrayConverter());

        $this->writer = new DefaultSqlWriter(new NullEscaper());

        $query = new SelectQuery('task', 't');
        $query->column('t.*');
        $query->column('n.type');
        $query->column(new RawExpression('count(n.id)'), 'comment_count');
        // Add and remove a column for fun
        $query->column('some_field', 'some_alias')->removeColumn('some_alias');
        $query->leftJoin('task_note', 'n.task_id = t.id', 'n');
        $query->groupBy('t.id');
        $query->groupBy('n.type');
        $query->orderBy('n.type');
        $query->orderBy(new RawExpression('count(n.nid)'), Query::ORDER_DESC);
        $query->range(7, 42);
        $where = $query->getWhere();
        $where->condition('t.user_id', 12);
        $where->condition('t.deadline', new RawExpression('now()'), '<');
        $having = $query->getHaving();
        $having->expression('count(n.nid) < ?', 3);

        $this->query = $query;
    }

    /**
     * @Revs(10000)
     * @Iterations(5)
     */
    public function benchArbitrary(): void
    {
        $this->writer->prepare($this->query);
    }
}
