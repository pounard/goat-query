Goat
====

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   install/install
   datatype
   query/index
   result
   hydrator/index

Introduction
^^^^^^^^^^^^

Goat is an SQL toolbox built around a powerful SQL query builder.

It covers the following basic needs:

 * it provides a **database driver connector abstraction**, as of now supporting
   **MySQL 5.7**, **PostgreSQL from 9.x to current**,

 * it provides a **complete, powerful and easy-to-use query builder**,

 * **it converts selected data to PHP native types using typing information**
   **from the database** and allows you to extend this convertion mechanism,

 * it **hydrates database row on arbitrary user given classes**, and allows
   **hierarchical hydration** (nesting objects),

 * it **supports advanced modern SQL features** such as **CTE** among other
   things.

**More generally, this connector was built for speed and efficient object**
**hydration with a strong emphasis on correct data typing.**

Principle
^^^^^^^^^

It aims to cover the same areas as most ORM will do, with a different
software design and approach:

 * **it does not replace and ORM, but more likely could be used as the**
   **SQL query builder for it,**

 * result set are immutable, readonly, iterable only once per default,
   in order to work on data stream and never consume more memory than
   necessary,

 * types are important, this query builder focuses on validating and converting
   data types from PHP to SQL as well as from SQL to PHP,

 * everyone needs a query builder; but everyone needs to be able to write real
   SQL queries too; nothing will prevent you from customizing your SQL: as such
   almost every parameter you can send to query builder methods can be replaced
   by raw arbitrary SQL expressions.

What it doesn't do (but might someday)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This connector does not provide:

 * a schema API, for manipulating the database schema, we believe that
   developers should always write manually their schema according to the
   database features they will use.

Current driver support
^^^^^^^^^^^^^^^^^^^^^^

 * **MySQL 5.7** via **PDO**,
 * **PostgreSQL >= 9.5** via **PDO**.
 * **PostgreSQL >= 9.5** via **ext-pgsql**.
 
Why the name?
^^^^^^^^^^^^^

.. note::

   Because you probably are as stupid as a goat to write a new database
   connector from scratch!
