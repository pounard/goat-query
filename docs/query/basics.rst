Concepts
========

Query creation
^^^^^^^^^^^^^^

A query created from its runner:

.. code-block:: php

   <?php

      /** @var \Goat\Runner\Runner $runner */

      $select = $runner->getQueryBuilder()->select($relation, $alias);

      $update = $runner->getQueryBuilder()->update($relation, $alias);

      $insertValues = $runner->getQueryBuilder()->insertValues($relation);

      $insertQuery = $runner->getQueryBuilder()->insertQuery($relation);

      $delete = $runner->getQueryBuilder()->delete($relation, $alias);

Generated SQL
^^^^^^^^^^^^^

**Generated SQL is SQL-92 standard compliant per default**, along with a few
variations from SQL 1996, 1999, 2003, 2006, 2008, 2011 when implemented. For SQL
servers that don't play well with SQL standard, drivers will fix the SQL query
formatting accordingly by themselves.

Depending on the database server, some constructs might not work (for example MySQL
does not support WITH or RETURNING statements): in most cases, it will fail while
during query execution in tyhe RDBMS side.

Identifiers and arbitrary expressions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Relation and column references are not validated during query building: you can always
write arbitrary identifiers, they will be left untouched within the generated SQL.

**Each identifier, value, predicate, relation, ... any method parameter can be replaced**
**by ``\Goat\Query\Expression`` instances: those objects will be considered as raw SQL**
**and place as-is without any escaping within the SQL queries.**

This explicitely allow you to go beyond the query builder capabilities and write
custom or specific arbitrary SQL.

.. warning::

   **Never allow arbitrary user values to pass down as ExpressionRaw SQL string**:
   since they are not properly escaped, they represent a security risk.

   **Keep them for edge cases the builder can't do**.

The ``ExpressionRaw`` object allows you to pass arbitrary parameters that must
refer to :ref:`parameters placehoders <query-parameter-placeholder>` within
the expression arbitrary SQL string, example usage on a select query adding
an arbitrary raw expression to the where clause:

.. code-block:: php

   <?php

   // WHERE COUNT(comment) > 5
   $select->expression('COUNT(comment) > ?', [5]);

Parameter placeholders will be gracefully merged to the others in their
rightful respective order at execute time.

Available expressions
^^^^^^^^^^^^^^^^^^^^^

ExpressionRaw
#############

This expression allows to write arbitrary unvalidated SQL.

.. code-block:: php

   <?php

   // Create a raw expression
   \Goat\Query\ExpressionRaw::create('count(*)');

   // Create a raw expression with arguments
   \Goat\Query\ExpressionRaw::create('sum(foo.column1) = ?', [12]);

ExpressionColumn
################

This expression allows you to identify a column, which will be properly escaped
in the generated SQL.

**Simple example**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionColumn::create('some_column');

Will be formatted as:

.. code-block:: sql

   "some_column"

**With a table alias (implicit)**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionColumn::create('some_column.some_table');

Will be formatted as:

.. code-block:: sql

   "some_table"."some_column"

**With a table alias (explicit)**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionColumn::create('some_column', 'some_table');

Will be formatted as:

.. code-block:: sql

   "some_table"."some_column"

**If you need to escape dot**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionColumn::escape('some.column', 'some.table');

Will be formatted as:

.. code-block:: sql

   "some.table"."some.column"

ExpressionRelation
##################

This expression allows you to identify a table, relation, constant table with
alias, WITH statement.

**Simple example**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionRelation::create('some_table');

Will be formatted as:

.. code-block:: sql

   "some.table"

**With a table alias**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionRelation::create('some_table', 'foo');

Will be formatted as:

.. code-block:: sql

   "some.table" as "foo"

**With a schema (implicit)**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionRelation::create('my_schema.some_table', 'foo');

Will be formatted as:

.. code-block:: sql

   "my_schema"."some_table" as "foo"

**With a schema (explicit)**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionRelation::create('some_table', 'foo', 'my_schema');

Will be formatted as:

.. code-block:: sql

   "my_schema"."some_table" as "foo"

**If you need to escape dot**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionRelation::escape('some.table', 'some.alias', 'my.schema');

Will be formatted as:

.. code-block:: sql

   "my.schema"."some.table" as "foo"

ExpressionValue
###############

Represents a raw value. **You will need this when the converter is unable**
**to find the appropriate type to convert to**, for example when you need
to store ``json`` or ``jsonb`` or a PostgreSQL array.

It will pass the type cast whenever necessary in queries, allowing the
converter to deambiguate values types.

**Simple exemple**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionValue::create(12);

Will be formatted as:

.. code-block:: sql

   ?

**With a type**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionValue::create(12, 'int');

.. code-block:: sql

   ?::int

**JSON**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionValue::create(['foo' => 'bar', 'baz' => [1, 2, 3]], 'json');

.. code-block:: sql

   ?::json

**PostgreSQL array**:

.. code-block:: php

   <?php

   \Goat\Query\ExpressionValue::create([1, 2, 3], 'int[]');

.. code-block:: sql

   ?::int[]

.. note::

   Examples here do not show how the value will be converted, refer to the
   converter documentation for this.

Execution modes
^^^^^^^^^^^^^^^

There are two different execution method: ``execute()`` and ``perform()``: ``execute``
will return a result iterator which will hydrate rows form the database whereas
``perform`` will drop any result and return the affected row count.

.. note::

   ``perform`` will have a different execution path which leads drivers supporting it
   to a huge performance boost: result will not be buffered and sent back to PHP.

.. note::

   ``execute`` **will fallback automatically on** ``perform`` **implementation if the**
   **SQL query being executed cannot return rows**: INSERT, UPDATE and DELETE queries
   without a RETURNING clause.

**Using perform() whenever applyable ensures best performances**.

.. _query-parameter-placeholder:

Parameters placeholders
^^^^^^^^^^^^^^^^^^^^^^^

Independently from the final database driver, all parameters within arbitrary SQL
must be ``?``:

.. code-block:: php

   <?php

   $result = $runner->execute(
       "SELECT * FROM user WHERE mail = ?",
       ['john.smith@example.com'],
       \App\Entity\User::class
   );

Additionnaly in order to ensure correct value conversion and achieve best performances
during SQL query formatting, you can specify the data type using ``?::TYPE``:

.. code-block:: php

   <?php

   $result = $runner->execute(
       "SELECT * FROM user WHERE last_login > ?::timestamp",
       [new \DateTime("today 00:00:01")],
       \App\Entity\User::class
   );

See the :ref:`data types matrix <data-typing>` for available types.

You can specify any number of parameter placeholders within the query, parameters
array must be ordered:

.. code-block:: php

   <?php

   $result = $runner->execute(
       "SELECT * FROM user WHERE last_login > ?::timestamp AND mail = ?",
       [
           new \DateTime("today 00:00:01"),
           'john.smith@example.com'
       ],
       \App\Entity\User::class
   );

Execute options
^^^^^^^^^^^^^^^

Both ``execute`` and ``perform`` have the same input signature:

 - on runner instances: ``execute(string|Statement $query, array $parameters = [], null|string|array $options = null)``
 - on query instances: ``execute(array $parameters = [], null|string|array $options = null)``

``$parameters`` is an ordered array of values to pass along the query. Using the
query builder you will not need it in most cases: arbitrary parameters values should
be passed to query builder methods. Nevertheless, in some edge cases, you might want
to pass :ref:`parameters placehoders <query-parameter-placeholder>`.

``$options`` is a set of key-value pairs that may contain:

 * ``class`` (string): PHP class name for hydrating rows, see
   :ref:`hydration documentation <hydrator>`:

   .. code-block:: php

      <?php

      $result = $select->execute([], ['class' => \App\Entity\Task::class]);

.. note::

   As a convenience, if you don't have any specific options to pass to query, you
   can directly pass the class name string instead of an option array:

      .. code-block:: php

         <?php

         $result = $select->execute([], \App\Entity\Task::class);

.. note::

   Options can also be set on the query itself using the ``setOption()`` or
   ``setOptions()`` methods:

      .. code-block:: php

         <?php

         $select->setOptions(['class' => \App\Entity\Task::class]);
         $result = $select->execute();

         $select->setOption('class', \App\Entity\Task::class);
         $result = $select->execute();
