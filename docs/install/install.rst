Getting started
===============

Installation
^^^^^^^^^^^^

.. code-block:: sh

   composer req makinacorpus/goat-query

Standalone setup
^^^^^^^^^^^^^^^^

1. choose your database driver in the driver support table:

    * **MySQL** / **PDO**: ``\Goat\Driver\PDODriver``
    * **PostgreSQL** / **PDO**: ``\Goat\Driver\PDODriver``
    * **PostgreSQL** / **ext_pgsql**: ``\Goat\Driver\ExtPgSQLDriver``

   .. note::

      If you use **PostgreSQL** we **highly recommend** using the ``ext_pgsql``
      driver which uses PHP core's ``pgsql`` extension: it's **much faster**
      than all the others.

2. instanciate it:

   .. code-block:: php

      <?php

      use Goat\Driver\Configuration;
      use Goat\Driver\PDODriver;

      $runner = new PDODriver();
      $runner->setConfiguration(new Configuration([
          'charset' => 'UTF8',
          'database' => 'my_database',
          'driver' => 'pqsql', // 'mysql' is supported as well
          'host' => 'database.example.com',
          'password' => 'this is a secret',
          'port' => 5432,
          'socket' => null,
          'username' => 'my_username',
      ], [
          'arbitrary_driver_option' => 42,
      ]));

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
