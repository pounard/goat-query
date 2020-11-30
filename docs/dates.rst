Date type handling
##################

Dates are always tricky to work with, even when you don't care about it, you
might experience time zone related conversion.

Introduction
============

PHP and dates
^^^^^^^^^^^^^

PHP does handle time zones very well, but if you do not give him any time zone
when instanciating a ``\DateTime[Immutable]`` object, it will automatically
use the system wide configured value, using the ``date.timezone`` value which
is set up in the ``php.ini`` configuration file.

This means that a time zone is always be set on each ```\DateTime`` object.

PostgreSQL and dates
^^^^^^^^^^^^^^^^^^^^

.. note::

   This documentation uses PostgreSQL as an example, but should be valid to
   many RDBMS supporting time zones.

PostgreSQL will allow you to set two different ``timestamp`` type variants
for your table columns:

 - ``timestamp without time zone`` : this type doesn't handle time zone, as
   such information is not stored, it's an absolute date, you may consider
   as being *UTC* per default, fact is they just don't belong to any
   time zone.

 - ``timestamp with time zone`` : this type doesn't store the time zone offset
   but proceeds to date conversion to *UTC* as described below.

For when ``with time zone`` dates are inserted date, PostgreSQL will attempt to
convert the timestamp to *UTC* from the user time zone which is:

 - if offset is specified in the SQL date string as such:
   ``2020-11-26 15:04:08.872984+01`` then the offset will be considered,

 - otherwise it will consider the timestamp to be in the client set time zone,
   which can be set at the session level using ``SET TIME ZONE 'Europe/Paris';``
   for example,

 - if no client time zone was set, it will use default configured in the
   PostgreSQL main configuration, or fallback on system exposed time zone if
   none.

All dates will then be stored in *UTC* and offset will dropped.

When querying you date, the time zone lookup rules are the same, and it will
convert the stored *UTC* date to a date string corresponding to the client
time zone adding the computed offset at the end.

For for more information please read
`PostgreSQL documentation <https://www.postgresql.org/docs/current/datatype-datetime.html/>`_

How this API handles dates
==========================

Default (RDBMS doesn't store time zone)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Regarding how PHP and SQL servers handle time zones, those arbitrary choices
have been implemented:

 - **You can set up a time zone globally at the connexion level**, all dates
   returned by the SQL server will be converted to this time zone. We call
   that the *client connection time zone*.

 - **Client connection time zone can be different from PHP default time zone**
   but will default to PHP default time zone if not specified explicitly.

 - **Per default since most RDBMS don't return time zone information, we**
   **will consider all output dates to already be converted to the client**
   **by the server to the connection time zone** and make no further conversion.

 - If server doesn't proceed to any conversion, this means that returned dates
   might be wrong, **in this case, it's your job to ensure that all connected**
   **clients have the same client connection time zone**. There is no magic
   formula to avoid this.

 - **When inserting dates, PHP** ``\DateTimeInterface`` **input object if they**
   **have a different time zone than the client connection one, will be converted**
   **to client connection time zone first before being formatted to SQL and sent.**
   **to the server**. Since SQL string will loose the offset information, at least
   we give it a valid date which is coherent with your configuration (which has
   been sent to SQL server when connecting).

 - For now, we don't support the ``AT TIME ZONE 'Foo'`` SQL operator, you still
   can use it into your queries, using raw SQL expressions.

.. warning::

   If the client that inserted the date and the client that read it don't have
   the same client conenction time zone, you will experience time shift bugs:
   **We strongly recommend that you use UTC as client connection time zone**
   **when your server doesn't handle date offset in input/output.**

.. warning::

   MySQL ``timestamp`` type, which has a 32 bits UNIX timestamp range only. Yet,
   it has the benefit of automatically converting stored dates to UTC.

   MySQL ``datetime`` type doesn't consider time zone, inserts raw dates without
   conversion, then outputs the same date without conversion.

.. note::

   We always convert PHP date objets to the client connection time zone before
   sending it to server, and after retrieving it from server. Behavior on the
   server side will differ depending upon the implementation.

.. note::

   In the future, it is considered to add an option to force the connection to
   translate all dates in UTC in both ways, so you could work safely with MySQL.
   This has not been implemented yet.

PostgreSQL (stores time zone)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

PostgreSQL understands and returns date offset information into SQL dates
input and ouput, it also able to set a time offset on date that don't have one
case in which it considers date to be on the client connection time zone which
has been setup at runtime while connecting. Here is how we do to handle it
gracefully:

 - When reading server output SQL dates, we search for offset information to
   be within: if set, the PHP date will be created using this time offset.
   If time offset differs from client connection time zone, PHP date object
   will be converted to client connection time zone: conversion will be done
   properly and UTC time will not be altered.

 - When inserting dates, because PostgreSQL handles the client connection time
   zone correctly, we apply the default algorithm describe upper: we convert
   all PHP dates to client connection timezone, then send an SQL date without
   time offset to server.

.. note::

   PostgreSQL does not store time offset, but it stores the information that
   the date had one when inserting, which means it knows that UTC date is
   correct.

.. warning::

   You should never use ``timestamp without time zone`` with PostgreSQL, or
   all warnings issued in the first paragraph applies as well.

Usage
=====

Regarding time without time zone
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

**If you use PostgreSQL** ``timestamp without time zone`` **or use a RDBMS that**
**does not allow specifiying as column type, we cannot guess which time zone was**
**used when you stored your dates.** Because of this, the converter will create new
``\DateTimeImmutable`` objects using the SQL date and time, considering that the
SQL date was returned using the client connection time zone.

.. warning::

   This mean that if PHP was configured in GMT+2 for example when inserting the
   date, and the client configured in GMT-2 when reading the date, you'll have
   a biased date with a 4 hour shift, which is an applicative bug.

Driver connection configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When instanciating the driver, add the ``timezone`` option, for example using
the ``DriverFactory``:

.. code-block:: php

   <?php

   use Goat\Driver\Goat\Driver\DriverFactory;

   $driver = DriverFactory::fromUri('pgsql://foo:bar@hostname:port/db?timezone=Europe/Paris');

Or by instanciating the driver directly:

.. code-block:: php

   <?php

   use Goat\Driver\Configuration;
   use Goat\Driver\ExtPgSQLDriver;

   $driver = new ExtPgSQLDriver();
   $driver->setConfiguration(new Configuration([
       'charset' => 'UTF8',
       'database' => 'my_database',
       // ...
       'timezone => 'Europe/Paris',
   ]));

Or when using the Symfony bundle, by adding it into your connection options
section of the ``packages/goat.yaml`` file.

.. code-block:: yaml

   goat_query:
       runner:
           default:
               driver: ext-pgsql
               metadata_cache: apcu
               url: '%env(resolve:DATABASE_URL)%'
               timezone: "Europe/Paris" 

.. note::

   You can setup a different time zone for each connection.
