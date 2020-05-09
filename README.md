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

# Roadamp

 - 2.0 - will include MERGE query support, functional testing, driver and platform
   segregation, as well as many fixes, and deprecated some 1.x methods,
 - 2.1 - will include many minor features additions,
 - 2.2 - may include a schema introspector,
 - 3.0 - will raise requirements to PHP 7.4 and drop most of 1.x deprecations.

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

# History

Originating and extracted from https://github.com/pounard/goat

# Testing with APCU

Please set the "apc.enable_cli" variable to 1 in CLI.
