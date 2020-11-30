
.. _result-iterator:

The result iterator
===================

Introduction
^^^^^^^^^^^^

Result iterator is an iterator of values, values can be either:

 * **an array whose keys are column aliases or names**, values are hydrated and
   converted values from the database,

 * **an hydrated class instance** if the ``class`` option was provided to the
   query options,

 * **any object** hydrated by the callback provided in the ``hydrator`` option.

But it also a value object that carries some metadata, such as:

 * column names,
 * column types,
 * row count.

Every value the SQL server will send back to you will be automatically converted
to PHP native types using the converter. See the :ref:`data types matrix <data-typing>`.

.. warning::

   Per default: **a result can always be iterated only once**, results are not
   kept into memory for performance reasons.

If you need to iterate more than once over it, call the ``ResultIterator::setRewindable()``
method on your result:

.. code-block:: php

   <?php

   $result = $runner
       ->execute("SELECT ...")
       ->setRewindable()
   ;

   foreach ($result as $row) {
       // First iteration will work as expected.
   }

   foreach ($result as $row) {
       // Second, third, ... iteration will work as well.
   }

Using the ``ResultIterator::setRewindable()`` method will not impact speed for
small results, but will raise memory usage.

.. warning::

   When you enable the rewindable behavior of a result iterator, all results
   will be kept in memory until the ``ResultIterator`` is being garbage
   collected, this is something your need to think about carefuly before
   using.

Fetching data
^^^^^^^^^^^^^

For this section, we will assume that the current return is this table:

+-----+-------+
| "a" | "b"   |
+=====+=======+
| 1   | 'foo' |
+-----+-------+
| 2   | 'bar' |
+-----+-------+
| 3   | 'baz' |
+-----+-------+

fetch()
#######

Fetches the next row and advance in stream.

Return value is the next row of values, ie:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $result->fetch();

Will give you:

+-------+-----------+
| "a"   | "b"       |
+=======+===========+
| ``1`` | ``'foo'`` |
+-------+-----------+
| 2     | 'bar'     |
+-------+-----------+
| 3     | 'baz'     |
+-------+-----------+

fetchField()
############

Fetches the next row and return a single column.

If you don't specify any parameter, it will return the first column from the
result. Example:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $result->fetch();

Will give you:

+-------+-------+
| "a"   | "b"   |
+=======+=======+
| ``1`` | 'foo' |
+-------+-------+
| 2     | 'bar' |
+-------+-------+
| 3     | 'baz' |
+-------+-------+

You can specify either the column number (numbering starts with ``0``) or name:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $result->fetch(1);

   // Or

   $result->fetch("b");

Will give you:

+-----+-----------+
| "a" | "b"       |
+=====+===========+
| 1   | ``'foo'`` |
+-----+-----------+
| 2   | 'bar'     |
+-----+-----------+
| 3   | 'baz'     |
+-----+-----------+

fetchColumn()
#############

Fetches a single column from all the rows.

If you don't specify any parameter, it will return the first column from the
result. Example:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $result->fetchColumn();

Will give you:

+-------+-------+
| "a"   | "b"   |
+=======+=======+
| ``1`` | 'foo' |
+-------+-------+
| ``2`` | 'bar' |
+-------+-------+
| ``3`` | 'baz' |
+-------+-------+

You can specify either the column number (numbering starts with ``0``) or name:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $result->fetchColumn(1);

   // Or

   $result->fetchColumn('b');

Will give you:

+-----+-----------+
| "a" | "b"       |
+=====+===========+
| 1   | ``'foo'`` |
+-----+-----------+
| 2   | ``'bar'`` |
+-----+-----------+
| 3   | ``'baz'`` |
+-----+-----------+

.. _result-iterator-cast:

Hydrating rows
^^^^^^^^^^^^^^

You may arbitrarily use any callable for hydrating rows, callable signature must be:

.. code-block:: php

   <?php

   function (array $row): mixed;

Where ``$row`` is raw row fetched from database whose values have been converted
using the ``Converter`` component.

You can specify the hydrator within the ``$options`` array:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...", [], [
       'hydrator' => function (array $row) {
           return new SomeObject($row);
       },
   ]);

Which is equivalent to:

.. code-block:: php

   <?php

   $result = $runner
       ->getQueryBuilder()
       ->select('some_table')
       // ... build your query
       ->setOption('hydrator', function (array $row) {
           return new SomeObject($row);
       })
       ->execute()
   ;

But you also may directly call ``ResultIteratorInterface::setHydrator()`` this way:

.. code-block:: php

   <?php

   $result = $runner
       ->getQueryBuilder()
       ->select('some_table')
       // ... build your query
       ->execute()
       ->setHydrator(function (array $row) {
           return new SomeObject($row);
       })
   ;

.. note::

   You can also use ``ocramius/generated-hydrator`` for hydrating results using the
   ``class`` option on queries, this is undocumented yet. If you are using this
   library standalone, it will not work until you set it up right.
