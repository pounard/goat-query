INSERT .. SELECT
================

Basics
^^^^^^

``INTO`` clause relation is provided in the object constructor:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('other_table')
       ->columns(['foo', 'bar'])
   ;

   $runner
       ->getQueryBuilder()
       ->insertQuery('some_table')
       ->columns(['a', 'b'])
       ->query($select)
       ->execute()
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b
   )
   SELECT foo, bar FROM other_table

RETURNING inserted values
^^^^^^^^^^^^^^^^^^^^^^^^^

You can use PostgreSQL ``RETURNING`` statement with INSERT queries:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('other_table')
       ->columns(['foo', 'bar'])
   ;

   $result = $runner
       ->getQueryBuilder()
       ->insertQuery('some_table')
       ->columns(['a', 'b'])
       ->query($select)
       ->returning('a')
       ->returning('b')
    ;

Which is equivalent to:

.. code-block:: sql

   INSERT INTO some_table (
      a, b
   )
   SELECT foo, bar FROM other_table
   RETURNING a, b

.. note::

   You can specify identifiers or expressions to ``returning()``, not only explicitly INSERTed columns.
