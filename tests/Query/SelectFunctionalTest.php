<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Converter\Tests\WithConverterTestTrait;
use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Query\Query;
use Goat\Query\SelectQuery;
use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Runner\Testing\NullEscaper;
use PHPUnit\Framework\TestCase;

final class SelectFunctionalTest extends TestCase
{
    use BuilderTestTrait;
    use WithConverterTestTrait;

    public function testSimpleQuery(): void
    {
        $formatter = new DefaultSqlWriter(new NullEscaper());

        $referenceArguments = ['12', '3'];
        $reference = <<<EOT
            select "t".*, "n"."type", count(n.id) as "comment_count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on (n.task_id = t.id)
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            order by
                "n"."type" asc,
                count(n.nid) desc
            limit 7 offset 42
            having
                count(n.nid) < ?
            EOT;
                    $countReference = <<<EOT
            select count(*) as "count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on (n.task_id = t.id)
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            EOT;

        // Compact way
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

        $formatted = $formatter->prepare($query);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $countQuery = $query->getCountQuery();
        $formatted = $formatter->prepare($countQuery);
        self::assertSameSql($countReference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $clonedQuery = clone $query;
        $formatted = $formatter->prepare($query);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        // We have to reset the reference because using a more buildish way we
        // do set precise where conditions on join conditions, and field names
        // get escaped
        $reference = <<<EOT
            select "t".*, "n"."type", count(n.id) as "comment_count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on ("n"."task_id" = "t"."id")
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            order by
                "n"."type" asc,
                count(n.nid) desc
            limit 7 offset 42
            having
                count(n.nid) < ?
            EOT;
                    $countReference = <<<EOT
            select count(*) as "count"
            from "task" as "t"
            left outer join "task_note" as "n"
                on ("n"."task_id" = "t"."id")
            where
                "t"."user_id" = ?
                and "t"."deadline" < now()
            group
                by "t"."id", "n"."type"
            having
                count(n.nid) < ?
            EOT;

        // Builder way
        $query = (new SelectQuery('task', 't'))
            ->column('t.*')
            ->column('n.type')
            ->columnExpression('count(n.id)', 'comment_count')
            ->groupBy('t.id')
            ->groupBy('n.type')
            ->orderBy('n.type')
            ->orderByExpression('count(n.nid)', Query::ORDER_DESC)
            ->range(7, 42)
        ;
        $query
            ->leftJoinWhere('task_note', 'n')
            ->condition('n.task_id', new ColumnExpression('t.id'))
        ;
        $where = $query->getWhere()
            ->condition('t.user_id', 12)
            ->condition('t.deadline', new RawExpression('now()'), '<')
        ;
        $having = $query->getHaving()
            ->expression('count(n.nid) < ?', 3)
        ;

        $formatted = $formatter->prepare($query);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $countQuery = $query->getCountQuery();
        $formatted = $formatter->prepare($countQuery);
        self::assertSameSql($countReference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $clonedQuery = clone $query;
        $formatted = $formatter->prepare($clonedQuery);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        // Same without alias
        $reference = <<<EOT
            select "task".*, "task_note"."type", count(task_note.id) as "comment_count"
            from "task"
            left outer join "task_note"
                on (task_note.task_id = task.id)
            where
                "task"."user_id" = ?
                and task.deadline < now()
            group by
                "task"."id", "task_note"."type"
            order by
                "task_note"."type" asc,
                count(task_note.nid) desc
            limit 7 offset 42
            having
                count(task_note.nid) < ?
            EOT;
                    $countReference = <<<EOT
            select count(*) as "count"
            from "task"
            left outer join "task_note"
                on (task_note.task_id = task.id)
            where
                "task"."user_id" = ?
                and task.deadline < now()
            group by
                "task"."id", "task_note"."type"
            having
                count(task_note.nid) < ?
            EOT;

        // Most basic way
        $query = (new SelectQuery('task'))
            ->column('task.*')
            ->column('task_note.type')
            ->columnExpression('count(task_note.id)', 'comment_count')
            ->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note')
            ->groupBy('task.id')
            ->groupBy('task_note.type')
            ->orderBy('task_note.type')
            ->orderByExpression('count(task_note.nid)', Query::ORDER_DESC)
            ->range(7, 42)
            ->where('task.user_id', 12)
            ->whereExpression('task.deadline < now()')
            ->havingExpression('count(task_note.nid) < ?', 3)
        ;

        $formatted = $formatter->prepare($query);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $countQuery = $query->getCountQuery();
        $formatted = $formatter->prepare($countQuery);
        self::assertSameSql($countReference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));

        $clonedQuery = clone $query;
        $formatted = $formatter->prepare($clonedQuery);
        self::assertSameSql($reference, $formatted->toString());
        self::assertSame($referenceArguments, $formatted->prepareArgumentsWith(self::context()));
    }

    public function testWith(): void
    {
        $reference = <<<EOT
            with "test1" as (
                select "a" from "sometable"
            ), "test2" as (
                select "foo" from "someothertable"
            )
            select count(*)
            from "test1"
            inner join "test2"
                on (test1.a = test2.foo)
            EOT;

        // Most basic way
        $query = (new SelectQuery('test1'))
            ->columnExpression('count(*)')
            ->join('test2', 'test1.a = test2.foo')
        ;

        $firstWith = (new SelectQuery('sometable'))->column('a');
        $query->with('test1', $firstWith);
        $secondWith = $query->createWith('test2', 'someothertable');
        $secondWith->column('foo');

        self::assertSameSql($reference, self::format($query));
    }

    public function testWhereInSelect(): void
    {
        $reference = <<<EOT
            select "foo"
            from "test1"
            where
              "a" in (
                select "b"
                from "test2"
              )
            EOT;

        // Most basic way
        $query = (new SelectQuery('test1'))
            ->column('foo')
            ->where('a',
                (new SelectQuery('test2'))
                  ->column('b')
            )
        ;

        self::assertSameSql($reference, self::format($query));
    }
}
