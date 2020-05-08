DELETE
======

Basics
^^^^^^

``FROM`` clause relation is provided in the object constructor:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->delete('other_table')
       ->where('foo', 12)
   ;

Which is equivalent to:

.. code-block:: sql

   DELETE FROM some_table
   WHERE foo = ?

Adding conditions inherit from the same methods as the ``SelectQuery`` object,
using ``->where()`` and ``->whereExpression()``

RETURNING deleted values
^^^^^^^^^^^^^^^^^^^^^^^^

You can use PostgreSQL ``RETURNING`` statement with INSERT queries:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->delete('other_table')
       ->where('foo', 12)
       ->returning('*')
       ->returningExpression('CAST(foo AS date)')
       // ...
   ;

Which is equivalent to:

.. code-block:: sql

   DELETE FROM some_table
   WHERE foo = ?
   RETURNING *, CAST(foo AS date)

.. note::

   You can specify identifiers or expressions to ``returning()``, not only explicitly deleted columns.
