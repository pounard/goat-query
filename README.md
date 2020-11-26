# Goat query builder

This is an SQL query builder built over a PHP to SQL and SQL to PHP type converter.

Working with `PDO` and `ext-pgsql`, with officially supported drivers:

 - MySQL 5.7 using `PDO`,
 - MySQL 8.x using `PDO`,
 - PostgreSQL >= 9.5 (until latest) using `PDO`,
 - PostgreSQL >= 9.5 (until latest) using `ext-pgsql` (recommended driver),
 - With a few hacks, any RDBMS speaking `SQL-92` standard using `PDO`.

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
