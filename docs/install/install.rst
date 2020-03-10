Getting started
===============

Installation
^^^^^^^^^^^^

.. code-block:: sh

   composer require makinacorpus/goat-query

Standalone setup
^^^^^^^^^^^^^^^^

1. choose your database driver in the driver support table:

    * **MySQL** / **PDO**: ``\Goat\Driver\PDODriver``
    * **PostgreSQL** / **PDO**: ``\Goat\Driver\PDODriver``
    * **PostgreSQL** / **ext-pgsql**: ``\Goat\Driver\ExtPgSQLDriver``

   .. note::

      If you use **PostgreSQL** we **highly recommend** using the ``ext-pgsql``
      driver which uses PHP core's ``pgsql`` extension: it's **much faster**
      than all the others.

2. instanciate it:

   With PDO, via a TCP connection:

   .. code-block:: php

      <?php

      use Goat\Driver\Configuration;
      use Goat\Driver\PDODriver;

      $driver = new PDODriver();
      $driver->setConfiguration(new Configuration([
          'charset' => 'UTF8',
          'database' => 'my_database',
          'driver' => 'pqsql', // 'mysql' is supported as well
          'host' => 'database.example.com',
          'password' => 'this is a secret',
          'port' => 5432,
          'username' => 'my_username',
      ], [
          'arbitrary_driver_option' => 42,
      ]));
      $runner = $driver->getRunner();

   With PDO via a unix socket:

   .. code-block:: php

      <?php

      use Goat\Driver\Configuration;
      use Goat\Driver\PDODriver;

      $driver = new PDODriver();
      $driver->setConfiguration(new Configuration([
          'charset' => 'UTF8',
          'database' => 'my_database',
          'driver' => 'pqsql', // 'mysql' is supported as well
          'password' => 'this is a secret',
          'socket' => null,
          'username' => 'my_username',
      ], [
          'arbitrary_driver_option' => 42,
      ]));
      $runner = $driver->getRunner();

   Or with ext-pgsql driver:

   .. code-block:: php

      <?php

      use Goat\Driver\Configuration;
      use Goat\Driver\ExtPgSQLDriver;

      $driver = new ExtPgSQLDriver();
      $driver->setConfiguration(new Configuration([
          'charset' => 'UTF8',
          'database' => 'my_database',
          'host' => 'database.example.com',
          'password' => 'this is a secret',
          'port' => 5432,
          'username' => 'my_username',
      ], [
          'arbitrary_driver_option' => 42,
      ]));
      $runner = $driver->getRunner();

   @todo document creation by URL.

3. initialize the data converter and object hydrator:

   .. code-block:: php

      <?php

      use Goat\Hydrator\HydratorMap;

      $runner->setHydratorMap(new HydratorMap('/tmp/goat-hydrators'));

4. play with it:

   .. code-block:: php

      <?php

      echo "Hello, ", $runner->execute("select 'World'")->fetchField(), "!\n";

Symfony setup
^^^^^^^^^^^^^

Refer to the bundle documentation.
