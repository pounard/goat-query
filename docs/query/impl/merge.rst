MERGE / UPSERT
==============

Introduction
^^^^^^^^^^^^

Query builder is able to write SQL:2003 standard compliant ``MERGE``
queries. But since almost no RDBMS support SQL:2003 ``MERGE``, we voluntarily
choose to support only a sub-set of it, allowing us to transparently write
their equivalent using PostgreSQL and MySQL own dialects.

``MERGE`` queries allow to write complex ``INSERT`` statements which can
fallback to an ``UPDATE`` in case of key conflict.

.. note::

   MERGE queries are atomic, and will perform much better than writing
   a complex transaction or a stored procedure to handle upserts.

An standard SQL ``MERGE`` query looks like:

.. code-block:: sql

   MERGE INTO table1
       USING
           (table_reference) AS table_reference_alias
           ON (conditions)
       WHEN MATCHED THEN
           UPDATE SET
               table1.col1 = table_reference_alias.foo,
               table1.col2 = table_reference_alias.bar
           DELETE WHERE conditions2
       WHEN NOT MATCHED THEN
           INSERT (
               col1, col2, col3
           ) 
           VALUES (
               table_reference_alias.foo,
               table_reference_alias.bar,
               table_reference_alias.baz
           )

Where ``table_reference`` in the ``USING`` clause can be either one of:

 - a constant table expression, i.e. ``VALUES (...) [, ...]`` as you would
   write in an ``INSERT`` query,
 - a nested expression or sub-query returning rows.

.. warning::

   In order to be compliant with most RDBMS, we do not support the ``ON`` clause
   at the ``USING`` level, but consider you always will work with tables
   **primary or unique keys** instead.

Because we do not fully implement the ``MERGE`` clause, this API calls these
``upsert`` queries instead.

When writing an ``upsert`` query, you will have the choice between only two
``on [conflict | duplicate]`` behaviours:

 - do nothing (do not insert or update the conflicting rows),
 - do update.

PostgreSQL is able to target the conflicting keys, that why the upsert object
will give you the possibility to explicitely expose the key columns you wish
to target.

MySQL decides arbitrarily to run the ``UPDATE`` for every conflicting key,
which will give you less flexibility using this API.

.. warning::

   The query builder does not allow yet to substitute UPDATE values by raw
   expressions, this will be done in a near future.

INSERT IGNORE on conflict
^^^^^^^^^^^^^^^^^^^^^^^^^

The following query:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->upsertValues('table1')
       ->columns(['foo', 'bar', 'fizz', 'buzz'])
       ->values([1, 2, 3, 4])
       ->values([5, 6, 7, 8])
       ->onConflictIgnore()
       ->execute()
   ;

Will be converted in SQL standard to:

.. code-block:: sql

   MERGE INTO "table1"
   USING
       VALUES (
           ?, ?, ?, ?
       ), (
           ?, ?, ?, ?
       ) AS "upsert"
   WHEN NOT MATCHED THEN
       INSERT INTO "table1" (
           "foo", "bar", "fizz", "buzz"
       ) VALUES (
           "upsert"."foo",
           "upsert"."bar",
           "upsert"."fizz",
           "upsert"."buzz"
       )
   ;

In PostgreSQL to:

.. code-block:: sql

   INSERT INTO "table1" (
       "foo", "bar", "fizz", "buzz"
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON CONFLICT
       DO NOTHING
   ;

In MySQL to:

.. code-block:: sql

   INSERT IGNORE INTO `table1` (
       `foo`, `bar`, `fizz`, `buzz`
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   );

INSERT UPDATE on conflict
^^^^^^^^^^^^^^^^^^^^^^^^^

The following query:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->upsertValues('table1')
       ->columns(['foo', 'bar', 'fizz', 'buzz'])
       ->values([1, 2, 3, 4])
       ->values([5, 6, 7, 8])
       ->onConflictUpdate()
       ->execute()
   ;

Will be converted in SQL standard to:

.. code-block:: sql

   MERGE INTO "table1"
   USING
       VALUES (
           ?, ?, ?, ?
       ), (
           ?, ?, ?, ?
       ) AS "upsert"
   WHEN MATCHED THEN
       UPDATE SET
           "foo" = "upsert"."foo",
           "bar" = "upsert"."bar",
           "fizz" = "upsert"."fizz",
           "buzz" = "upsert"."buzz"
   WHEN NOT MATCHED THEN
       INSERT INTO "table1" (
           "foo", "bar", "fizz", "buzz"
       ) VALUES (
           "upsert"."foo",
           "upsert"."bar",
           "upsert"."fizz",
           "upsert"."buzz"
       )
   ;

In PostgreSQL to:

.. code-block:: sql

   INSERT INTO "table1" (
       "foo", "bar", "fizz", "buzz"
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON CONFLICT
       DO UPDATE SET
           "foo" = excluded."foo",
           "bar" = excluded."bar",
           "fizz" = excluded."fizz",
           "buzz" = excluded."buzz"
   ;

In MySQL to:

.. code-block:: sql

   INSERT INTO `table1` (
       `foo`, `bar`, `fizz`, `buzz`
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON DUPLICATE KEY
       UPDATE
           `foo` = excluded.`foo`,
           `bar` = excluded.`bar`,
           `fizz` = excluded.`fizz`,
           `buzz` = excluded.`buzz`

Specifying the conflicting key
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Specifying a key using the ``setKey()`` method restricts columns that will be
added ot the ``SET`` clause when the behaviour is ``ON CONFLICT UPDATE``,
it will behave the same amonst all RDBMS. Using the previous example:

.. code-block:: php

   <?php

   $runner
       ->getQueryBuilder()
       ->upsertValues('table1')
       ->setKey(['foo', 'bar'])
       ->columns(['foo', 'bar', 'fizz', 'buzz'])
       ->values([1, 2, 3, 4])
       ->values([5, 6, 7, 8])
       ->onConflictUpdate()
       ->execute()
   ;

.. note::

   The query builder cannot guess which is your primary key or which are your
   unique keys as it does not and will never introspect your SQL schema at
   runtime. It's a good practice to always explicit your potentially
   conflicting key using this method.

Will be converted in SQL standard to:

.. code-block:: sql

   MERGE INTO "table1"
   USING
       VALUES (
           ?, ?, ?, ?
       ), (
           ?, ?, ?, ?
       ) AS "upsert"
   WHEN MATCHED THEN
       UPDATE SET
           "fizz" = "upsert"."fizz",
           "buzz" = "upsert"."buzz"
   WHEN NOT MATCHED THEN
       INSERT INTO "table1" (
           "foo", "bar", "fizz", "buzz"
       ) VALUES (
           "upsert"."foo",
           "upsert"."bar",
           "upsert"."fizz",
           "upsert"."buzz"
       )
   ;

In PostgreSQL to:

.. code-block:: sql

   INSERT INTO "table1" (
       "foo", "bar", "fizz", "buzz"
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON CONFLICT
       DO UPDATE SET
           "fizz" = excluded."fizz",
           "buzz" = excluded."buzz"
   ;

In MySQL to:

.. code-block:: sql

   INSERT INTO `table1` (
       `foo`, `bar`, `fizz`, `buzz`
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON DUPLICATE KEY
       UPDATE
           `fizz` = excluded.`fizz`,
           `buzz` = excluded.`buzz`

.. note::

   Notice in the examples above that given key has disapeared from the
   ``UPDATE`` clause in generated SQL.

Using a nested query in USING clause
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can use a nested sub-query or raw expression in place of the USING clause,
everthing documented above works the same. You just need to use
``->upsertQuery()`` in place of ``->upsertValues()`` and call ``->query()``
instead of ``->columns()`` and ``->values()``.

The following query:

.. code-block:: php

   <?php

   $using = $runner
       ->getQueryBuilder()
       ->select('table2')
       ->column('a')
       ->column('b')
       ->column('c')
       ->column('d')
   ;

   $runner
       ->getQueryBuilder()
       ->upsertQuery('table1')
       ->setKey(['foo', 'bar'])
       ->query($using);
       ->onConflictUpdate()
       ->execute()
   ;

Will be converted in SQL standard to:

.. code-block:: sql

   MERGE INTO "table1"
   USING
       (
           SELECT "a", "b", "c", "d"
           FROM "table2"
       ) AS "upsert"
   WHEN MATCHED THEN
       UPDATE SET
           "fizz" = "upsert"."fizz",
           "buzz" = "upsert"."buzz"
   WHEN NOT MATCHED THEN
       INSERT INTO "table1" (
           "foo", "bar", "fizz", "buzz"
       ) VALUES (
           "upsert"."foo",
           "upsert"."bar",
           "upsert"."fizz",
           "upsert"."buzz"
       )
   ;

In PostgreSQL to:

.. code-block:: sql

   INSERT INTO "table1" (
       "foo", "bar", "fizz", "buzz"
   )
   SELECT "a", "b", "c", "d"
   FROM "table2"
   ON CONFLICT
       DO UPDATE SET
           "fizz" = excluded."fizz",
           "buzz" = excluded."buzz"
   ;

In MySQL to:

.. code-block:: sql

   INSERT INTO `table1` (
       `foo`, `bar`, `fizz`, `buzz`
   )
   SELECT `a`, `b`, `c`, `d`
   FROM `table2`
   ON DUPLICATE KEY
       UPDATE
           `fizz` = excluded.`fizz`,
           `buzz` = excluded.`buzz`

Using RETURNING
^^^^^^^^^^^^^^^

``RETURNING`` clause can be added to ``upsert`` queries if your RDBMS
supports it:

.. code-block:: php

   $runner
       ->getQueryBuilder()
       ->upsertValues('table1')
       ->setKey(['foo', 'bar'])
       ->columns(['foo', 'bar', 'fizz', 'buzz'])
       ->values([1, 2, 3, 4])
       ->values([5, 6, 7, 8])
       ->onConflictUpdate()
       ->returning()
       ->execute()
   ;

Which will be converted using PostgreSQL:

.. code-block:: sql

   INSERT INTO "table1" (
       "foo", "bar", "fizz", "buzz"
   )
   VALUES (
       ?, ?, ?, ?
   ), (
       ?, ?, ?, ?
   )
   ON CONFLICT
       DO UPDATE SET
           "fizz" = excluded."fizz",
           "buzz" = excluded."buzz"
   RETURNING *
   ;

