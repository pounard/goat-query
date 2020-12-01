# New in 3.x

## Expressions namespace changes

All `\Goat\Query\Expression*` classes are now moved to a sub-namespace
to improve code readability and maintainability, they also have disambiguated
names for better code readability: there was a possible confusion between `row`
and `raw` words for example.

All classes that have been moved:

 - `\Goat\Query\ExpressionColumn` is now `\Goat\Query\Expression\ColumnExpression`,
 - `\Goat\Query\ExpressionConstantTable` is now `\Goat\Query\Expression\ConstantTableExpression`,
 - `\Goat\Query\ExpressionLike` is now `\Goat\Query\Expression\LikeExpression`,
 - `\Goat\Query\ExpressionRaw` is now `\Goat\Query\Expression\RawExpression`,
 - `\Goat\Query\ExpressionRelation` is now `\Goat\Query\Expression\TableExpression`,
 - `\Goat\Query\ExpressionRow` is now `\Goat\Query\Expression\ConstantRowExpression`,
 - `\Goat\Query\ExpressionValue` is now `\Goat\Query\Expression\ValueExpression`,
 - `\Goat\Query\Value` is now `\Goat\Query\Expression\ValueExpression`.

New classes also have been added:

 - `\Goat\Query\Expression\BetweenExpression` that expresses a `BETWEEN`
   comparison,
 - `\Goat\Query\Expression\ComparisonExpression` that expresses any comparison
   with 2 operands and an operator,
 - `\Goat\Query\Expression\IdentifierExpression` that expresses an identifier
   that explicitely needs escaping, useful only for when instantiating
   `ColumnExpression` and `TableExpression` instances.

All legacy classes have been kept, but are deprecated, and as such will raise
deprecation notices upon instanciation. You are urged to fix your code to use
new classes. Legacy classes will all be removed in 5.x.

## Expressions instanciation changes

All new expression classes static methods for instantiating them have been
dropped and are replaced by the class constructors. Signatures did not change.

For example, using the `TableExpression` before:

```php
use \Goat\Query\ExpressionRelation;

$table = ExpressionRelation::create('my_table', 'my_alias', 'my_schema');
```

After:

```php
use \Goat\Query\Expression\TableExpression;

$table = new TableExpression('my_table', 'my_alias', 'my_schema');
```

## Expressions everywhere

When using the query builder, every parameter or every method can now be an
`\Goat\Query\Expression` instance. SQL formatter will gracefully detect and
format them accordingly, allowing you to very easily write unsupported SQL
statements, such as:

```php
$queryBuilder
    ->select(
        new ExpressionConstantTable([
            [1, 2, 3],
            [4, 5, 6]
        ]),
        'some_alias'
    )
;
```

Which will be gracefully written in SQL as (with correctly
escaped and propagated arguments):

```sql
SELECT * FROM
(
    VALUES
    (1, 2, 3),
    (4, 5, 6)
) AS "some_alias"
```

Possibilities are infinite, any expression can be placed anywhere.

## Short arrow syntax

Since 2.x you could use callbacks in `Where::expression()` and
`Where::condition()` methods, as well as `Select::where()` and
`Select::whereExpression()`, but due to the way it was handled
internally, it was not possible to use short arrow syntax. Now you can.

The following code:

```php
$queryBuilder
    ->select('some_table')
    ->whereExpression(
        function (Where $where) {
            $or = $where->or();
            $or->condition('a', 1);
            $or->condition('a', 2);
        }
    )
    ->havingExpression(
        function (Where $where) {
            $or = $where->or();
            $or->condition('a', 1);
            $or->condition('a', 2);
        }
    )
;
```

Can now be written as:

```php
$queryBuilder
    ->select('some_table')
    ->where(
        fn (Where $where) => $where
            ->or()
            ->condition('a', 1)
            ->condition('a', 2)
    })
    ->having(
        fn (Where $where) => $where
            ->or()
            ->condition('a', 1)
            ->condition('a', 2)
    })
;
```

## Easier AND and OR WHERE nested conditions writing

Four new shortcuts have been added for nesting `WHERE` clauses with
`OR` or `AND` operators:

```php
$queryBuilder
    ->select('some_table')
    ->whereOr(
        fn (Where $where) => $where
            ->condition('a', 1)
            ->condition('a', 2)
        }
    )
    ->whereAnd(
        fn (Where $where) => $where
            ->condition('a', 1)
            ->condition('b', 2)
        }
    )
    ->havingOr(
        fn (Where $where) => $where
            ->condition('a', 1)
            ->condition('a', 2)
        }
    )
    ->havingAnd(
        fn (Where $where) => $where
            ->condition('a', 1)
            ->condition('b', 2)
        }
    )
;
```

Which will result in the following SQL:

```sql
SELECT * FROM "some_table"
WHERE
    ("a" = 1 OR "a" = 2)
    AND ("a" = 1 AND "b" = 2)
HAVING
    ("a" = 1 OR "a" = 2)
    AND ("a" = 1 AND "b" = 2)
```

## Improvement for writing [I]LIKE conditions

Ever since the project is stable, you could write `[I]LIKE` conditions using
the `ExpressionLike::like()`, `ExpressionLike::iLike()`,
`ExpressionLike::notLike()`, `ExpressionLike::notILike()` methods, now you can
pass the exact same methods arguments to `Where::like()`, `Where::notLike()`,
`Where::likeInsensitive()`, `Where::notLikeInsensitive()` methods. This makes
it more fluent to write, and also allow you to avoid the expression case
import/use statement.

Before you would write:

```php
use \Goat\Query\ExpressionLike;

$queryBuilder
    ->select('some_table')
    ->whereExpression(
        ExpressionLike::iLike('some_column', '?%', 'text_to_search')
    )
;
```

And now you can write:

```php
$queryBuilder
    ->select('some_table')
    ->where(
        fn (Where $where) => $where->likeInsensitive('some_column', '?%', 'text_to_search')
    )
;
```

Which will both result in the following SQL:

```sql
SELECT *
FROM "some_table"
WHERE
    "some_column" ILIKE 'text_to_search%'
```

## SELECT ... UNION ... queries

`SELECT` queries can now have `UNION`:

```php
$queryBuilder
    ->select('some_table')
    ->union(/* SelectQuery|Expression */)
```

You can `UNION` with any expression, constant table, raw expression or anything
else. You may call the `union()` method as many time as you wish to.

## Date handling

Date and time handling has been fully rewritten to be more consistent with
SQL-92 time zone handling:

 - you can, and you should specify a *client time zone* in driver configuration,
   if you fail to do so, it will fallback on PHP default time zone,

 - client time zone will always be set at the session level using SQL standard
   ``SET TIME ZONE 'Foo';`` statement when connecting, non standard SQL dialects
   will be handled by their respective drivers,

 - when using ``timestamp with time zone`` SQL-92 standard type, time offset
   will be honored when returning dates and times from the SQL server,

 - when using ``timestamp without time zone`` SQL-92 standard type, more
   commonly known as simply ``datetime``, the converter will always consider
   that returned date strings are always converted to the client time zone,
   and thus will not be converted in any way,

 - when a date and time is returned from the SQL server with a different time
   zone than the configured client time zone, the PHP date time object time
   zone will be converter the client time zone one, respecting time offset
   and not altering the underlaying UTC date.

## Converter changes

``\Goat\Converter\ConverterContext`` class has been added, and carry session and
runtime information for converters to act upon.

``\Goat\Converter\ValueConverterInterface`` and ``\Goat\Converter\ConverterInterface``
classes now share a common base interface, all methods now require a new
``\Goat\Converter\ConverterContext`` parameter.

Since the ``\Goat\Converter\ValueConverterInterface`` changed, all existing
custom converters must be adapted to the new interface. Behavior remain the
same, only the new context parameter has been added, and ``getPhpType()``
method has been dropped.

## Faster query formatter

`ArgumentBag`, `ArgumentList` and a few other classes you were not supposed to
directly use have been deleted or moved, and `Expression` instances don't have
a `getArguments()` method anymore.

In previous versions, the query formatter was traversing the query builder as
if it was an AST, writing SQL along the way. Then, and only then once finished,
arguments were fetched from the query builder using the same kind of traversal
algorithm then sent the driver.

This caused the AST to be traversed twice instead of once, and caused argument
ordering troubles: backend specific SQL writers will not always order clauses
in the same order due to different SQL dialects existing, but arguments were
always fetched in the same order, causing a few SQL queries such as complex
`UPDATE` statements to fail on MySQL, for exemple.

A few other minor performance tweaks were applied in the query builder. Let's be
honest, what makes your code slow is the query execution time, not the query
building time, but that's still good to see some performance improvements.

Generally speaking, the `SqlWriter` interface and implementations are much
more robust and readable now.

## Named parameters feature dropped

Feature that allowed you to use placeholders such as `:foo` instead of anonymous
generic `?` placeholder was dropped. There was no reported use of it, and it
was potentially error-prone when both when both syntax were used in the same
query.

Note that it also make the query formatting a tiny bit faster, even thought
there's no chance you'll ever notice it.

## Testing improvements

A docker environement using docker-composer is provided for running unit and
functional tests. You may or may not use it. Authoritative way for running test
remains PHPUnit, configured via environment variables. Please read
``TESTING.md`` file for more information.
