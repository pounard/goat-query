Goat
====

.. toctree::
   :maxdepth: 1
   :caption: Contents:

   install/install
   datatype
   query/index
   result
   hydrator/index

Introduction
^^^^^^^^^^^^

Goat is an SQL connector along with a powerful SQL query builder and efficient,
type-safe, stream-based result iterators able to hydrate objects.

Top features are:

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

It aims to cover the same areas as most ORM will do, with a different
software design and approach:

 * **it does not replace and ORM, but more likely could be used as the**
   **SQL query builder for it,**

 * result set are immutable, readonly, iterable only once per default,
   in order to work on data stream and never consume more memory than
   necessary,

 * types are important, this query builder focuses on validating and converting
   data types from PHP to SQL as well as from SQL to PHP.

Future plans
^^^^^^^^^^^^

API is mostly stable, in use in production projects. 1.x version will continue
to be maintained for bugfixes and maintenance only, while 2.x provides new
features and some minimal API changes (for most users those changes will be
completely transparent).

2.x focuses on various visible and less visible improvements:

 - query builder public API method names are more coherent with each other,

 - driver and platform API aren't tied together anymore, SQL writer
   implementations become driver agnostic and re-usable,

 - direct optional dependency to ocramius/generated-hydrator will be added for
   having a much cleaner and more direct result hydration,

 - some basic schema introspection API will be added in a near future,

 - result iterators can be explicitely set as being rewindable, which consumes
   more memory when done, but allow them to be rewinded and re-iterated,

 - API drops internal array usage and replaces them by properly typed objets
   when applicable.

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
