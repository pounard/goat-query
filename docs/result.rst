
.. _result-iterator:

The result iterator
===================

Introduction
^^^^^^^^^^^^

Result iterator is an iterator of values, values can be either:

 * **an array whose keys are column aliases or names**, values are hydrated and
   converted values from the database,

 * **an hydrated class instance** if the ``class`` option was provided to the
   query options.

.. warning::

   One very important fact to know: **a result can always be iterated only once**,
   results are never kept into memory for performance reasons.

If you need to iterate more than once over it, fetch the result as an array:

.. code-block:: php

   <?php

   $result = $runner->execute("SELECT ...");

   $array = \iterator_to_array($result);

But it also a value object that carries some metadata, such as:

 * column names,
 * column types,
 * row count.

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