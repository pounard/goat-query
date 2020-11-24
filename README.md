# Goat query builder

This is an SQL query builder built over a PHP to SQL and SQL to PHP type converter.

Working with `PDO` and `ext-pgsql` as of today (MySQL >= 5 and PostGreSQL >= 9 are officially supported).

Documentation is in the `./docs/` folder, generated using Sphinx.

# Quickstart

Install it:

```sh
composer require makinacorpus/goat-query
```

Create a connexion:

```php
$driver = new \Goat\Driver\ExtPgSQLDriver();
$driver->setConfiguration(
    \Goat\Driver\Configuration::fromString(
        'pgsql://username:password@hostname:5432/database?option1=value1&option2=value2'
    )
);
```

Use it:

```php
$runner = $driver->getRunner();
$platform = $runner->getPlatform();
$queryBuilder = $runner->getQueryBuilder();

if ($platform->supportsReturning()) {
    $result = $queryBuilder
        ->insertValues('users')
        ->columns(['id', 'name'])
        ->values([1, 'Jean'])
        ->values([1, 'Robert'])
        ->returning('*')
        ->setOption('class', \App\Domain\Model\User::class)
        ->execute()
    ;
} else {
    $queryBuilder
        ->insertValues('users')
        ->columns(['id', 'name'])
        ->values([1, 'Jean'])
        ->values([2, 'Robert'])
        ->execute()
    ;

    $result = $queryBuilder
        ->select('users')
        ->where('id', [1, 2])
        ->setOption('class', \App\Domain\Model\User::class)
        ->execute()
}

foreach ($result as $user) {
   \assert($user instanceof \App\Domain\Model\User);

    echo "Hello, ", $user->getName(), " !\n";
}
```

For advanced documentation, please see the `./docs/` folder.

# Changelog

## New in 3.x

### Expressions namespace changes

All `\Goat\Query\Expression*` classes are now moved to a sub-namespace
to improve code readability and maintainability, they also have disambiguated
names for better code compression: there was a possible confusion between `row`
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
   that explicitely needs escaping, useful only for when instanciating
   `ColumnExpression` and `TableExpression` instances.

All legacy classes have been kept, but are deprecated, and as such will raise
deprecation notices upon instanciation. You are urged to fix your code to use
new classes. Legacy classes will all be removed in 5.x.

### Expressions instanciation changes

All new expression classes static methods for instanciating them have been
droped and are replaced by the class constructors. Signatures did not change.

### Expressions everywhere

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

### Short arrow syntax

Since 2.x you could use callbacks in `Where::expression()` and
`Where::condition()` methods, as well as Select::where() and
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

### Easier AND and OR WHERE nested conditions writing

Four new shortcuts have been added for adding nested `WHERE` clauses with
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

### Improvement for writing [I]LIKE conditions

Ever since the project is stable, you could write `[I]LIKE` conditions using
the `ExpressionLike::like()`, `ExpressionLike::iLike()`,
`ExpressionLike::notLike()`, `ExpressionLike::notILike()` methods, now you can
pass the exact same methods arguments to `Where::like()`, `Where::notLike()`,
`Where::likeInsensitive()`, `Where::notLikeInsensitive()` methods. This makes
it more fluent to write, and also allow you to avoid the expression case
import/use statement.

### SELECT ... UNION ... queries

`SELECT` queries can now have `UNION`:

```php
$queryBuilder
    ->select('some_table')
    ->union(/* SelectQuery|Expression */)
```

You can `UNION` with any expression, constant table, raw expression or anything
else. You may call the `union()` method as many time as you wish to.

### Faster query formatter

`ArgumentBag`, `ArgumentList` and a few other classes you were not supposed to
directly used have been deleted or moved, and `Expression` instances don't have
a `getArguments()` method anymore.

In previous versions, the query formatter was traversing the query builder as
if it was an AST, writing SQL along the way. Then, and only then once finished,
arguments were fetched from query builder to send them to the driver. This
caused the AST to be traversed twice instead of once, and caused argument
ordering troubles: some backend specific SQL writer did not place the arguments
in the same order due to different SQL dialects co-existing, but arguments were
fetched in always the same order, causing a few SQL queries such as complex
`UPDATE` statements to fail on MySQL, for exemple.

A few other minor performance tweaks were applied in the query builder. Let's be
honest, what makes your code slow is the query execution time, not the query
building time, but that's still good to see some performance improvements.

Generally speaking, the `SqlWriter` interface and implementations are much
more robust and readable now.

### Named parameters feature dropped

Feature that allowed you to use placeholders such as `:foo` instead of anonymous
generic `?` placeholder was dropped. There was no reported use of it, and it
was potentially error-prone when both when both syntax were used in the same
query.

Note that it also make the query formatting a tiny bit faster, even thought
there's no chance you'll ever notice it.

## New in 2.x

Needs documentation:

 - fully rewrote driver implementation,
 - merge queries,
 - force pgsql connections to always be new,
 - much faster and much more precise instrumentation, now always on,
 - standalone bundle in a separate package.

# Roadamp

 - 2.0 - bumps requirement to PHP 7.4,
 - 2.0 - will include MERGE query support, functional testing, driver and platform
   segregation, as well as many fixes, and deprecated some 1.x methods,
 - 2.1 - will include many minor features additions,
 - 2.2 - may include a schema introspector,
 - 3.0 - will drop 1.x deprecations.

# Driver organisation

`Driver` instance is responsible of (in order):

 - connecting to the database,
 - send configuration,
 - inspect backend variant and version to build platform.

It gets connexion option and configures it, then creates the platform.

`Platform` contains SQL version-specific code, such as query formatter,
schema introspector, and other things the user cannot configure, and which may
vary depending upon the SQL server version. It handles everything the user
cannot have hands onto, but SQL server has.

`Runner` is the only runtime object the user needs:

 - public facade for executing SQL queries,
 - holds the converter (which can be injected and may contain user code),
 - creates and holds the query builder,
 - manages transactions.

It contains user configuration and runtime. The runner knows nothing about SQL
itself, it just holds a connexion, send requests, and handles iterators and
transactions.

In other words:

 - drivers connects,
 - platform handles SQL dialect,
 - runner executes,
 - a single runner implementation can use different plaform implementations,
   real reason why both implementations are actually separate.

# Framework integration

 - Symfony bundle in https://github.com/makinacorpus/php-goat/

# Upgrade

## Upgrade from 2.x to 3.x

 - 3.x deprecated all `\Goat\Query\Expression*` classes. Their backward
   compatible equivalent still exists, in order to make your code resilient,
   please use their new implementations in `\Goat\Query\Expression\*Expression`.

 - 3.x ships a complete `\Goat\Driver\Query\SqlWriter` interface and
   implementations rewrite. New code is faster, easier to read and has much
   less dependencies, driver developers or users using it directly must adapt
   their code.

 - 3.x removes the `\Goat\Query\ArgumentBag`, `\Goat\Query\ArgumentList`,
   `\Goat\Query\Value`, `\Goat\Query\ValueRepresentation` classes and
   interfaces, people using those must adapt their code.

 - Generally speaking, this will be the last version providing backward
   compatible deprecated code, following deprecation notices and the `@deprecated`
   PHP documentation annotaton to fix your existing code.

 - For most users, upgrade will be transparent and will not cause any trouble.

## Upgrade from 1.x to 2.x

 - 2.x introduced a single user facing change: the Symfony bundle was
   originally provided by the
   [makinacorpus/goat](https://packagist.org/packages/makinacorpus/goat)
   package, it is now bundled as the standalone
   [makinacorpus/goat-query-bundle](https://packagist.org/packages/makinacorpus/goat-query-bundle)
   package.

 - 2.x changed internal runners implementation and introduces a new
   `\Goat\Driver\` namespace, which focuses on low-level driver implementations,
   driver developpers will need to convert their code to the new API.

This also introduce a dependency conflict between `makinacorpus/goat` version
prior to `3.0.0` version, if you were using it, you need to upgrade.

Query builder, database runner and result iterator end-user API did not change.

# History

Originating and extracted from https://github.com/pounard/goat

# Testing with APCU

Please set the "apc.enable_cli" variable to 1 in CLI.
