# Driver

`Driver` instance is responsible of (in order):

 - connecting to the database,
 - send configuration,
 - inspect backend variant and version to build platform.

It gets connexion option and configures it, then creates the platform.

`Platform` contains SQL version-specific code, such as query formatter,
schema introspector, and other things the user cannot configure, and which may
vary depending upon the SQL server version. It handles everything the user
cannot have hands onto, but SQL server has.

`Runner` is the runtime monster:

 - public facade for executing SQL queries,
 - holds the converter (which can be injected and may contain user code).

It contains user configuration and runtime. The runner knows nothing about SQL
itself, it just holds a connexion, send requests, and handles iterators and
transactions.

In other words:

 - drivers connects,
 - platform handles SQL dialect,
 - runner executes,
 - a single runner implementation can use different plaform implementations,
   real reason why both implementations are actually separate.
