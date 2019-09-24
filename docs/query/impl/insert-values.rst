INSERT .. VALUES
================

Basics
^^^^^^

``INTO`` clause relation is provided in the object constructor:

.. code-block:: php

   <?php

   $insert = $runner->getQueryBuilder()->insertValues('some_table');

Is equivalent to:

.. code-block:: sql

   INSERT INTO some_table () VALUES ()

Or with PostgreSQL:

.. code-block:: sql

   INSERT INTO some_table DEFAULT VALUES

Implicit values
^^^^^^^^^^^^^^^

In order to insert values from an array, you may just write:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->insertValues('some_table')
       ->values([
           'a' => 1,
           'b' => 'foo',
           'c' => new \DateTimeImmutable(),
           // ...
       ])
       ->execute()
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b, c
   ) VALUES (
      1, 'foo', '2019-09-23 14:50:37'
   )

.. warning::

   In order to insert multiple values, subsequent ``values()`` method call must
   present data in the same order if non indexed, or with the same column
   names:

You may write something such as:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->insertValues('some_table')
       ->values([
           'a' => 1,
           'b' => 'foo',
       ])
       ->values([
           2
           'bar',
       ])
       ->values([
           'b' => 'baz',
           'a' => 3,
       ])
       ->execute()
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b
   ) VALUES (
      1, 'foo'
   ), (
      2, 'bar'
   ), (
      3, 'baz'
   )

Explicit values
^^^^^^^^^^^^^^^

If you know in advance which columns you are writing, you can specify them
before calling ``values()`` then append data without the need of naming
array keys:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->insertValues('some_table')
       ->columns(['a', 'b'])
       ->values([1, 'foo'])
       ->values([2, 'bar'])
       ->values([4, 'baz'])
       ->execute()
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b
   ) VALUES (
      1, 'foo'
   ), (
      2, 'bar'
   ), (
      3, 'baz'
   )

.. warning::

   In order to insert multiple values, subsequent ``values()`` method call must
   present data in the same order.

RETURNING inserted values
^^^^^^^^^^^^^^^^^^^^^^^^^

You can use PostgreSQL ``RETURNING`` statement with INSERT queries:

.. code-block:: php

   <?php

   $result = $runner
       ->getQueryBuilder()
       ->insertValues('some_table')
       ->values(['a' => 1, 'b' => 'foo'])
       ->values(['a' => 2, 'b' => 'bar'])
       ->returning('a')
       ->returning('b')
       ->execute()
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b
   ) VALUES (
      1, 'foo'
   ), (
      2, 'bar'
   )
   RETURNING a, b

.. note::

   You can specify identifiers or expressions to ``returning()``, not only explicitly INSERTed columns.
