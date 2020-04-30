
.. _query-builder-where:

WHERE (predicates)
==================

For all queries that supports it (``SELECT``, ``UPDATE`` and ``DELETE``) you can use
the builder to express advanced and complex predicates.


WHERE .. = ..
-------------

The ``condition($column, $value, $operator)`` method allows you to arbitrarily filter the
SELECT query with any values:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('a')
       ->where('b', 12)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a FROM some_table t WHERE b = 12

.. note::

   ``$value`` parameter can be any PHP type that is supported by the converter, please
   :ref:`see the datatype documentation <data-typing>`: **This is always true for every**
   **kind of value parameter, in every method**.

Example using the ``$operator`` argument:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('a')
       ->where('some_date', new \DateTime(), '<')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a FROM some_table t WHERE some_date < '2018-02-16 15:48:54'

.. warning::

   The ``$operator`` value will be left untouched in the final SQL string, use it
   wisely: never allow user arbitrary values to reach this, it is opened to SQL
   injection.

Additionnaly, you can use a callback to set the conditions, simply provide any
callable that takes a ``Where`` instance as its first argument, and set your
conditions there:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->where(function (\Goat\Query\Where $where) {
           $where
               ->isEqual('some_table.id', 12)
               ->isGreaterOrEqual('some_table.birthdate', new \DateTimeImmutable('2019-09-24'))
               // ...
           ;
       })
   ;

Which is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table t
   WHERE
       some_table.id = 12
       AND some_table.birthdate >= '2019-09-24'
       -- ...

.. note::

   Calling ``condition()`` with a single callable argument is strictly equivalent
   to calling ``expression()`` with a single callable argument.

.. warning::

   In order to keep backward compatibility, and because values and expressions
   can be raw strings that may conflict with existing PHP function names,
   **function names as strings cannot be used as callables**.

WHERE .. [NOT] IN (..)
----------------------

``WHERE .. IN (..)`` condition can be written using the ``condition()`` method:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('a')
       ->where('b', [1, 2, 3])
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a FROM some_table t WHERE b IN (1, 2, 3)

.. note::

   You don't have to manually set the ``$operator`` variable to ``IN``, the query
   builder will do it for you.

``WHERE .. NOT IN (..)`` condition needs that you set the ``$operator`` parameter:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('a')
       ->where('b', [1, 2, 3], 'NOT IN')
   ;

   // Or

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table', 't')
       ->column('a')
       ->where('b', [1, 2, 3], \Goat\Query\Where::NOT_IN)
   ;

Are both equivalent equivalent to:

.. code-block:: sql

   SELECT a FROM some_table t WHERE b IN (1, 2, 3)

.. note::

   You don't have to manually set the ``$operator`` variable to ``IN``, the query
   builder will do it for you.

.. note::

   You can always mix up ``Where::IN`` with ``Where::EQUAL`` and ``Where::NOT_IN``
   with ``Where::NOT_EQUAL``, the query builder will dynamically attempt to fix
   it depending on the value type.

WHERE .. [NOT] IN (SELECT ..)
-----------------------------

``WHERE .. IN (SELECT ..)`` condition can be written using the ``condition()`` method:

.. code-block:: php

   <?php

   $inSelect = $runner
       ->getQueryBuilder()
       ->select('other_table')
       ->column('foo')
       ->where('type', 'bar')
   ;

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->where('b', $inSelect)
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a
   FROM some_table
   WHERE b IN (
      SELECT foo
      FROM other_table
      WHERE type = 'bar'
   )

``WHERE .. NOT IN (SELECT ..)`` condition needs that you set the ``$operator`` parameter:

.. code-block:: php

   <?php

   $inSelect = $runner
       ->getQueryBuilder()
       ->select('other_table')->column('foo')->where('type', 'bar')
   ;

   // ...

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->where('b', $inSelect, 'NOT IN')
   ;

   // Or

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->where('b', $inSelect, \Goat\Query\Where::NOT_IN)
   ;

Are both equivalent to:

.. code-block:: sql

   SELECT a
   FROM some_table
   WHERE b NOT IN (
      SELECT foo
      FROM other_table
      WHERE type = 'bar'
   )


WHERE .. [NOT] IN (<TABLE EXPRESSION>)
--------------------------------------

This will come later once table expression will be implemented.

WHERE <ARBITRARY EXPRESSION>
----------------------------

Using the ``expression($statement, $arguments = [])`` you can pass any SQL expresion
in the ``WHERE`` clause:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->whereExpression('1')
   ;

Is equivalent to:

.. code-block:: sql

   SELECT a FROM some_table WHERE 1

Additionnaly, you can use a callback to set the expression, callback must return
the expression:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->whereExpression(function () {
           return '1';
       })
   ;

You may as well return any ``Expression`` instance, including ``ExpressionColumn``,
``ExpressionValue`` and so on:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->whereExpression(function () {
           return ExpressionRaw::create('1');
       })
   ;

You can also use the ``Where`` instance given as the first callback argument,
which is the main query where, in this case, you don't need to return a value:

.. code-block:: php

   <?php

   $select = $runner
       ->getQueryBuilder()
       ->select('some_table')
       ->column('a')
       ->whereExpression(function (\Goat\Query\Where $where) {
           $where->expression('1');
       })
   ;

Are all equivalent to:

.. code-block:: sql

   SELECT a FROM some_table WHERE 1

OR / AND with the Where object
------------------------------

For most complex conditions, with ``OR`` and ``AND`` groups, you will need to fetch the
``\Goat\Query\Where`` component of the query:


.. code-block:: php

   <?php

   $select = $runner->getQueryBuilder()->select('some_table')->column('a');
   $where = $select->getWhere();

You may now use the ``\Goat\Query\Where`` object to set advanced conditions.

OR / AND chaining
-----------------

This method is not advised to use except in case where you attach a great importance to
code readability: using ``open()`` and ``close()`` methods will allow you to change the
current group you are in without breaking chaining.

Let's consider the following PHP code:

.. code-block:: php

   <?php

   $select = new SelectQuery('some_table');
   $select->getWhere()
       ->open(\Goat\Query\Where::OR)
           ->condition('theWorld', 'enough', 'IS NOT')
           ->expression('count(theWorld) = ?::int4', [1])
           ->open()
               ->expression('1 = ?', 0)
               ->expression('2 * 2 = ?', 5)
           ->close()
       ->close()
       ->and()
           ->condition('beta', [37, 42], Where::BETWEEN)
           ->condition('gamma', [123, 234], Where::NOT_BETWEEN)
       ->close()
       ->isNull('roger')
       ->or()
           ->condition('test', 1)
           ->condition('other', ['this', 'is', 'an array'])
       ->close()
       ->condition('baaaa', 'goat');
   ;

Is equivalent to:

.. code-block:: sql

   SELECT *
   FROM some_table
   WHERE
      (
         theWorld IS NOT 'enough'
         OR count(theWorld) = 1
         OR (
            1 = 0
            AND 2 * 2 = 5
         )
      )
      AND (
         'beta' BETWEEN 37 AND 42
         AND gamma NOT BETWEEN 123 AND 234
      )
      AND roger IS NULL
      AND (
         test = 1
         OR other IN ('this', 'is', 'an array')
      )
      AND baaaa = 'goat'

In this case:

 * ``->open()`` and ``->and()`` are equivalent.
 * ``->open(\Goat\Query\Where::OR)`` and ``->or()`` are equivalent.
