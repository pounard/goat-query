Goat
====

.. toctree::
   :maxdepth: 1
   :caption: Contents:

   install/install
   datatype
   dates
   query/index
   result

Introduction
^^^^^^^^^^^^

Goat is an SQL connector along with a powerful SQL query builder and efficient,
type-safe, stream-based result iterators able to hydrate objects.

Top features are:

 * it provides a **database driver connector abstraction**, as of now supporting
   **MySQL** and **PostgreSQL**,

 * it provides a **complete, powerful and easy-to-use query builder**,

 * **it converts selected data to PHP native types using typing information**
   **from the database** and allows you to extend this convertion mechanism,

 * it **hydrates database row on arbitrary user given classes**, and allows
   **hierarchical hydration** (nesting objects),

 * it **supports advanced modern SQL features** such as **CTE** among other
   things.

**More generally, this connector was built for speed and efficient object**
**hydration with a strong emphasis on correct data typing.**

It aims to cover the same areas as most ORM will do, with a different
software design and approach:

 * **it does not replace and ORM, but more likely could be used as the**
   **SQL query builder for it,**

 * result set are immutable, readonly, iterable only once per default,
   in order to work on data stream and never consume more memory than
   necessary,

 * types are important, this query builder focuses on validating and converting
   data types from PHP to SQL as well as from SQL to PHP.

Current driver support
^^^^^^^^^^^^^^^^^^^^^^

 * **MySQL 5.7** via **PDO**,
 * **MySQL 5.8** via **PDO**,
 * **PostgreSQL >= 9.5** (until current) via **PDO**,
 * **PostgreSQL >= 9.5** (until current) via **ext-pgsql** *(recommended driver)*.
 
Why the name?
^^^^^^^^^^^^^

.. note::

   Because you probably are as stupid as a goat to write a new database
   connector from scratch!
