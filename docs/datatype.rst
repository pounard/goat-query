
.. _data-typing:

Datatypes and converter
=======================

Goat provides an API called ``Converter`` which is responsible for converting
PHP native types and to SQL and the other way around. It can be extended easily.

Every type has a unique identifier, for example ``int`` which reprensents an
integer, and a set of type aliases, for example ``int4`` refers to ``int``.

Supported data types
^^^^^^^^^^^^^^^^^^^^

The following matrix reveal all natively supported PHP and SQL types, but does
not cover all aliases, which most solely exists for backward compatibility.

In order to have a complete list of all supported aliases, please refer to the
``src/Converter/DefaultConverter`` source file.

+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| Type      | Aliases  | PHP Native type            | SQL type        | Notes                                                     |
+===========+==========+============================+=================+===========================================================+
| bigint    | int8     | int                        | bigint          | size (32 or 64 bits) depends on your arch                 |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| bigserial | serial8  | int                        | bigserial       | size (32 or 64 bits) depends on your arch                 |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| bool      | boolean  | bool                       | boolean         |                                                           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| bytea     | blob     | string or resource         | bytea           | some drivers will give you a resource instead of a string |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| date      |          | \DateTimeImmutable         | date            | PHP has no date type, timezone might cause you trouble    |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| decimal   | numeric  | float                      | decimal         |                                                           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| float4    | real     | float                      | float4          | May loose precision                                       |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| float8    | real     | float                      | float8          |                                                           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| int       | int4     | int                        | int             | size (32 or 64 bits) depends on your arch                 |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| interval  |          | \DateInterval              | interval        | you probably will need to deambiguate from time           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| json      | jsonb    | array                      | json            | difference between json and jsonb is in storage           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| serial    | serial4  | int                        | serial          | size (32 or 64 bits) depends on your arch                 |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| time      |          | \DateInterval              | time            | you probably will need to deambiguate from interval       |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| timestamp | datetime | \DateTimeImmutable         | timestamp       |                                                           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| uuid      | uuid     | \Ramsey\Uuid\UuidInterface | uuid            | you will need to install ramsey/uuid in order to use it   |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+
| varchar   | text     | string                     | varchar or text |                                                           |
+-----------+----------+----------------------------+-----------------+-----------------------------------------------------------+

.. warning::

   Please be aware that not all types will work on all drivers, it depends upon
   the SQL server you are using. For example, MySQL < 8 will not support the
   ``json`` type.

PostgreSQL arrays
^^^^^^^^^^^^^^^^^

All types without exception can be manipulated as value-arrays. In order to cast
values as typed arrays, use the form ``TYPE[]``, for example: ``int[]``.

When you want to pass an array of values into your parameters, just pass the
value transparently:

.. code-block:: php

   <?php

   $runner->execute("insert into foo (my_array) values (?)", [[1, 2, 3]]);

Conversion is automatic.

Explicit type cast
^^^^^^^^^^^^^^^^^^

When you write SQL, if a PHP datatype matches more than one SQL datatype, you can
arbitrarily cast the value, converter will catch user-given types.

Cast can be done either by the type identifier or any of its aliases.

Casting in raw SQL
##################

In order to cast values in raw SQL, use the PostgreSQL syntax as such:

.. code-block:: php

   <?php

   $runner->execute("select ?::int" [1]);
   $runner->execute("select ?::date" [new \DateTimeImmutable()]);

.. warning::

   Please note that anything you cast which is not ``?`` will be left
   untouched.

For example:

.. code-block:: php

   <?php

   $runner->execute("select ?::int" [1]);

Will be sent to the server as:

.. code-block:: sql

   select 1

Whereas:

.. code-block:: php

   <?php

   $runner->execute("select 1::int");

Will be sent to the server as:

.. code-block:: sql

   select 1::int

Casting in query builder
########################

When using the query builder, you are not responsible for writing the SQL
code, but you can hint the SQL writer as such:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->select()
       ->columnExpression(\Goat\Query\ExpressionValue::create(1, 'int'))
   ;

Which will be converted as:

.. code-block:: sql

   select 1

.. note::

   Arbitrary SQL code in ``\Goat\Query\ExpressionRaw`` instances will inherit
   from the same rules as casting in raw SQL.

Let SQL do the cast
###################

If you are trying to let the SQL server do the cast by itself, you should write
it using the SQL-92 standard ``CAST()`` function as such:

.. code-block:: php

   <?php

   $runner->execute("select cast(? as int)" [1]);

Which will be left untouched in SQL sent to the server:

.. code-block:: sql

   select cast(1 as int)

If your SQL backend does not support SQL standard cast please refer to its documentation.

Explicit result casting
^^^^^^^^^^^^^^^^^^^^^^^

See the :ref:`result iterator <result-iterator-cast>` chapter.
