SELECT
======

``FROM`` clause relation is provided in the object constructor accompagnied by the main
from table alias:

.. code-block:: php

   <?php

   $select = $runner->getQueryBuilder()->select('some_table', 't');

Is equivalent to:

.. code-block:: sql

   SELECT * FROM some_table t

Selecting data
^^^^^^^^^^^^^^

SELECT *
--------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('t.*')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT t.* FROM some_table t

Please also note that if you omit any ``column*()`` calls, the query builder will
automatically set the columns to ``*``, for example:

.. code-block:: php

   <?php

   $select = $runner->getQueryBuilder()->select('some_table');

Is equivalent to:

.. code-block:: sql

   SELECT * FROM some_table

.. warning::

   If you omit columns and let the query builder use ``*``, it will not be prefixed
   with the table name, in case where you join multiple tables altogether, **it will**
   **select everything from every table**.

SELECT column1, column2
-----------------------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('t.column1')
       ->column('t.column2')
   ;

   // Or

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->columns(['t.column1', 't.column2'])
   ;

Are both equivalent to:

.. code-block:: sql

   SELECT t.column1, t.column2 FROM some_table t

.. note::

   Strings such as ``table.column`` will be parsed and the dot will be interpreted:
   this means that once escaped, the generated SQL query will look like:

   .. code-block:: sql

      SELECT "t"."column1", "t"."column2" FROM "some_table" "t"

SELECT column1 AS a, column2 AS b
---------------------------------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('column1', 'a')
       ->column('column2', 'b')
   ;

   // Or

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->columns(['a' => 'column1', 'b' => 'column2'])
   ;

Are both equivalent to:

.. code-block:: sql

   SELECT column1 AS a, column2 AS b FROM some_table t

.. note::

   When using the ``columns()`` method, you can mix both keyed and non keyed
   entries in the array: those with non numeric and string keys will be aliased
   using the key.

   Key is the alias because you can select the same alias only once, but you use
   the same column more than once.

Mixing an array with numeric and string keys when calling ``columns()``:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->columns([
           'a' => 'column1',
           'column2',
           'count_foo' => new \Goat\Query\ExpressionRaw('COUNT(foo)'),
       ])
   ;

Is equivalent to:

.. code-block:: sql

   SELECT
       column1 AS a,
       column2,
       COUNT(foo) AS count_foo
   FROM some_table t

Abitrary expressions
--------------------

You can pass **any** SQL in the SELECT clause:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->columnExpression('COUNT(foo)', 'count_foo')
       ->column('column2', 'b')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT COUNT(foo) AS count_foo, column2 AS b, column2 AS b FROM some_table t

.. warning::

   Please note that **you can pass any SQL including invalid one**.

You can also use ``ExpressionRaw`` instances:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column(new \Goat\Query\ExpressionRaw('COUNT(foo)'), 'count_foo')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT COUNT(foo) AS count_foo FROM some_table t

Groups / aggregates
-------------------

Use arbitrary expression for using aggregation:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('product')
       ->columnExpression('SUM(amount)', 'total_amount')
       ->groupBy(product)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT product, SUM(amount) AS total_amount FROM some_table t

Joining tables
^^^^^^^^^^^^^^

LEFT JOIN
---------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('table_a', 't')
       ->leftJoin('table_b', 'a.id = b.id', 'b');
   ;

   // Or

   $select = $runner->getQueryBuilder()->select('table_a', 't');
   $where = $runner->leftJoinWhere('table_b', 'b');
   $where->expression('a.id = b.id');


Is equivalent to:

.. code-block:: sql

   SELECT * FROM table_a a
   LEFT JOIN table_b b
       ON a.id = b.id

RIGHT JOIN
----------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('table_a', 't')
       ->innerJoin('table_b', 'a.id = b.id', 'b');
   ;

   // Or

   $select = $runner->getQueryBuilder()->select('table_a', 't');
   $where = $runner->innerJoinWhere('table_b', 'b');
   $where->expression('a.id = b.id');

Is equivalent to:

.. code-block:: sql

   SELECT * FROM table_a a
   INNER JOIN table_b b
       ON a.id = b.id

Other modes
-----------

You may arbitrary JOIN using a custom JOIN statement by setting the `join()` 4th
parameter or the `joinWhere()` 3rd parameter:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('table_a', 't')
       ->join('table_b', 'a.id = b.id', 'b', \Goat\Query\Query::JOIN_RIGHT_OUTER);
   ;

   // Or

   $select = $runner->getQueryBuilder()->select('table_a', 't');
   $where = $runner->joinWhere('table_b', 'b', \Goat\Query\Query::JOIN_RIGHT_OUTER);
   $where->expression('a.id = b.id');

Is equivalent to:

.. code-block:: sql

   SELECT * FROM table_a a
   RIGHT OUTER JOIN table_b b
       ON a.id = b.id

GROUP BY
^^^^^^^^

GROUPing BY is as easy as:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->expression('SUM(t.a)')
       ->groupBy('t.b')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT SUM(t.a)
   FROM some_table t
   GROUP BY t.b

ORDER BY
^^^^^^^^

.. note::

   Default ordering is always `ASC`.

Column
------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->orderBy('t.a')
       ->orderBy('t.b', \Goat\Query\Query::ORDER_DESC)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table t
   ORDER BY t.a ASC, t.b DESC

Expression
----------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->orderByExpression(<<<SQL
   CASE
       WHEN a = 'one' THEN 1
       WHEN a = 'two'' THEN 2
       ELSE NULL
   END
   SQL
       )
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table t
   ORDER BY (
       CASE
           WHEN a = 'one' THEN 1
           WHEN a = 'two'' THEN 2
           ELSE NULL
       END
   ) ASC

NULLS [FIRST, LAST]
-------------------

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->orderBy(
           't.a',
           \Goat\Query\Query::ORDER_ASC,
           \Goat\Query\Query::NULL_FIRST
       )
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table t
   ORDER BY t.a ASC NULLS FIRST
   
And:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->orderBy(
           't.a',
           \Goat\Query\Query::ORDER_ASC,
           \Goat\Query\Query::NULL_LAST
       )
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table t
   ORDER BY t.a ASC NULLS LAST

WHERE (predicates)
^^^^^^^^^^^^^^^^^^

Please read the :ref:`where clause and predicates documentation <query-builder-where>`.

HAVING (predicates)
^^^^^^^^^^^^^^^^^^^

``HAVING`` clause behavior is stricly identical to ``WHERE`` clause, except that:

 * ``condition()`` becomes ``havingCondition()``,
 * ``expression()`` becomes ``havingExpression()``,
 * ``getWhere()`` becomes ``getHaving()``.

Method signature are all the same, please refer to the
:ref:`where clause and predicates documentation <query-builder-where>`.

As a brief example:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->condition('a', 1)
       ->groupBy('c')
       ->having('b', 2)
       ->havingExpression('COUNT(a) = ?', 3)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table
   WHERE
       a = 1
   GROUP BY c
   HAVING
      b = 2
      AND COUNT(a) = 3

SELECT .. FOR UPDATE
^^^^^^^^^^^^^^^^^^^^

Simply call the ``forUpdate()`` method:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->forUpdate()
       ->columns(['a', 'b', 'c'])
       ->condition('a', 42)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a, b, c FROM some_table WHERE a = 42 FOR UPDATE

Moreover, if you do not wish to fetch the result, you may also call the ``performOnly()`` method:


.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->forUpdate()
       ->performOnly()
       ->columns(['a', 'b', 'c'])
       ->condition('a', 42)
   ;

Is equivalent to (when driver supports it, such as PostgreSQL):

.. code-block:: sql

   PERFORM a, b, c FROM some_table WHERE a = 42 FOR UPDATE

And will fallback on (when driver don't support ``PERFORM``):

.. code-block:: sql

   SELECT a, b, c FROM some_table WHERE a = 42 FOR UPDATE
