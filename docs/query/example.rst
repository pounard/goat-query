
A basic example
===============

Goat comes with a powerful and easy-to-user query builder.

If you have a nice IDE you won't ever need to read this documentation everything
can done using method call chaining.

Let's dive in, first consider a simple PHP class:

.. code-block:: php

   <?php

   namespace App\Entity;

   use Ramsey\Uuid\Uuid;
   use Ramsey\Uuid\UuidInterface;

   final class Task
   {
       /** @var UuidInterface */
       private $id;

       /** @var bool */
       private $is_done = false;

       /** @var ?string */
       private $title;

       /** @var ?int */
       private $priority;

       /** @var ?\DateTimeInterface */
       private $ts_deadline;

       /** @var int */
       private $note_count = 0;

       public function getId() : UuidInterface
       {
           return $this->id ?? ($this->id = Uuid::uuid4());
       }

       public function isDone() : bool
       {
           return $this->is_done;
       }

       public function getTitle() : ?string
       {
           return $this->title;
       }

       public function getPriority() : int
       {
           return $this->priority ?? 0;
       }

       public function deadlinesAt() : ?\DateTimeInterface
       {
           return $this->ts_deadline;
       }

       public function getNoteCount() : int
       {
           return $this->note_count ?? 0;
       }
   }

See the :ref:`hydrator documentation <hydrator>`.

And this SQL query:

.. code-block:: sql

   SELECT
       task.*,
       task_note.type,
       COUNT(task_note.id) AS comment_count
   FROM task
   LEFT OUTER JOIN task_note
       ON task_note.task_id = task.id
   WHERE
       task.user_id = 3
       AND task.deadline < NOW()
   GROUP BY
       task.id, task_note.type
   ORDER BY
       task_note.type ASC,
       COUNT(task_note.nid) DESC
   LIMIT 7 OFFSET 42
   HAVING
       COUNT(task_note.nid) < 3
   ;

One way to build this query would be:

.. code-block:: php

   <?php

   use Goat\Query\Query;

   /** @var \Goat\Runner\Runner $value */
   $runner = get_database();

   $query = runner->select('task')
       ->column('task.*')
       ->column('task_note.type')
       ->columnExpression('count(task_note.id)', 'comment_count')
       ->leftJoin('task_note', 'task_note.task_id = task.id', 'task_note')
       ->groupBy('task.id')
       ->groupBy('task_note.type')
       ->orderBy('task_note.type')
       ->orderByExpression('count(task_note.nid)', Query::ORDER_DESC)
       ->range(7, 42)
       ->where('task.user_id', 12)
       ->whereExpression('task.deadline < now()')
       ->havingExpression('count(task_note.nid) < ?', 3)
   ;

.. note::

   **All SQL identifiers, schema names, table names, column names**, and even
   some temporary expression identifiers **will be automatically escaped**
   **properly for the target datatabase when using the query builder**!

See the :ref:`query builder documentation <query-builder>`.

You may now fetch the result:

.. code-block:: php

   <?php

   $result = $query->execute([], \App\Entity::class);

   foreach ($result as $task) {
       // Task is now a fully hydrated \App\Entity::class
   }

See the :ref:`result iterator documentation <result-iterator>`.

.. warning::

   Results never create temporary arrays and that is **the key for achieving great**
   **performances without consumming memory**: **result can be iterated over only once!**
