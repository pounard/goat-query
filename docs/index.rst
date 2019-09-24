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
   **MySQL 5x**, **PostgreSQL 9.x and 10.x**,

 * it provides a **complete and easy-to-use query builder**,

 * **it converts selected data to PHP native types using typing information**
   **from the database** and allows you to extend this convertion mechanism,

 * it **hydrates database row on arbitrary user given classes**, and allows
   **hierarchical hydration** (nesting objects),

 * it provides a very basic ORM-like data mapping layer, along with basic
   CRUD functionality.

**More generally, this connector was built for speed and efficient object**
**hydration with a strong emphasis on correct data typing.**

Principle
^^^^^^^^^

It aims to cover the same areas as most ORM will do, with a different
software design and approach:

 *  **TL;DR: if you love your ORM, don't use Goat.**

 * you shall not map relations onto objects: objects are a mutable data
   representation in memory while relations are a mathematical concept,
   both do not play well together;

 * selecting data is projecting a unique and restricted set of data at a
   specific point in time: selected data is not the truth, it's only a
   representation of it;

 * selected data should always remain immutable, you need it for viewing or
   displaying purpose; since it only represents a degraded, altered
   projection of your data at a specific point in time, you should never
   modify it; as soon as you did selected data, someone else probably already
   modified it!

 * selected data will always be typed, never cast strings ever again! Your
   database knows better than you the data types it carries, why not trust it
   and let you enjoy what the database really gives to you?

 * data alteration (insertion, update, merge and deletion) can not happen using
   entity objects, you can not alter something that's already outdated;

 * everyone needs a query builder; but everyone needs to be able to write real
   SQL queries too; nothing will prevent you from customazing your SQL. 

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
