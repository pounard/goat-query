WITH (Common Table Expressions)
===============================

All query implementations benefit from the **Common Table Expressions** (*CTE*) as
long as the underlaying database engine supports it.

.. note::

   We recommend reading
   `PostgreSQL official WITH documentation <https://www.postgresql.org/docs/current/static/queries-with.html>`_
   for more information about this feature.

.. warning::

   This API **CTE implementation is incomplete**, it does not allow you to write recursive
   CTEs yet.

``WITH`` statement is a coma-separated list of table expressions, this API only supports
``SELECT`` table expression.

This API allows you to set any number of table expressions, with two different ways:

 * either create a select query using a runner, and set it as a with statement using
   the ``with($alias, $select)`` method,
 * or call the ``createWith($alias, $relation)`` method.

Consider the following SQL query (from PostgreSQL official documentation):

.. code-block:: sql

   WITH regional_sales AS (
       SELECT region, SUM(amount) AS total_sales
       FROM orders
       GROUP BY region
   ), top_regions AS (
       SELECT region
       FROM regional_sales
       WHERE total_sales > (SELECT SUM(total_sales)/10 FROM regional_sales)
   )
   SELECT region,
          product,
          SUM(quantity) AS product_units,
          SUM(amount) AS product_sales
   FROM orders
   WHERE region IN (SELECT region FROM top_regions)
   GROUP BY region, product;

Can be written in two different ways, the first one using the ``with()`` method:

.. code-block:: php

   <?php

   $regional_sales = $runner
       ->getQueryBuilder()
       ->select('orders')
       ->column('region')
       ->columnExpression("SUM(amount)", 'total_sales')
       ->groupBy('region')
   ;

   $top_regions = $runner
       ->getQueryBuilder()
       ->select('orders')
       ->columns(['region', 'product'])
       ->expression("total_sales > (SELECT SUM(total_sales)/10 FROM regional_sales)")
   ;

   $select = $runner
       ->getQueryBuilder()
       ->select('orders')
       ->with('regional_sales', $regional_sales)
       ->with('top_regions', $top_regions)
       ->columns(['region', 'product'])
       ->columnExpression("SUM(quantity)", 'product_units')
       ->columnExpression("SUM(amount)", 'product_sales')
       ->expression("region IN (SELECT region FROM top_regions)")
       ->groupBy('region')
       ->groupBy('product')
   ;

Or using the ``createWith()`` method:

.. code-block:: php

   <?php

   $select = $runner->getQueryBuilder()->select('orders');

   $select
       ->createWith('regional_sales', 'orders')
       ->column('region')
       ->columnExpression("SUM(amount)", 'total_sales')
       ->groupBy('region')
   ;

   $select
       ->createWith('top_regions', 'orders')
       ->columns(['region', 'product'])
       ->expression("total_sales > (SELECT SUM(total_sales)/10 FROM regional_sales)")
   ;

   $select
      ->columns(['region', 'product'])
      ->columnExpression("SUM(quantity)", 'product_units')
      ->columnExpression("SUM(amount)", 'product_sales')
      ->expression("region IN (SELECT region FROM top_regions)")
      ->groupBy('region')
      ->groupBy('product')
   ;

Once set, table expressions aliases can be used as any other normal table whenever
the query builder exposes a ``$relation`` parameter, for every method without any
exception.
