# New in 4.x

## Backward compatibility break and deprecations

 - The whole `\Goat\Converter\` namespace has been rewritten, if you implemented
   custom value converters, you will need to rewrite those using the new API,
   read more about this topic later in this document.

 - Using directly PHP arrays as the returned of `\Goat\Runner\ResultIterator::fetch()`
   method is now deprecated, it returns `\Goat\Runner\Row` instances which
   implements the `\ArrayAccess` interface for backward compatibility,
   nevertheless if you use then `\array_*()` functions, `foreach` or attempt
   any modification the `fetch()` return, your code will break. You may work
   around this behaviour by simply calling `->setHydrator(fn (Row $row) => $row->toHydratedArray())`
   on your result instances. This is also true for arbitrary `foreach` on
   result iterators.

 - `\Goat\Runner\ResultIterator::fetchColumn()` method is not deprecated, it
   forces to maintain inefficient and slow code, and can be easily implemented
   in user-land by using `setKeyColumn()` and a a custom hydrator altogether.
   In all cases, using `fetchColumn()` is a code smell, it would mean that
   wrote a SQL query that fetches more columns that you really need in most
   cases.

 - `\Goat\Runner\ResultIterator` group expansion feature (aka. expanding
   the SQL value array into a multi-dimensional array structure based upon a
   column name separator) is deprecated and must be explicitely enabled on a per
   query basis. It will be removed in next major. Code was slow and ran
   systematically for all rows hydration.

 - `\Goat\Driver\Instrumentation\` namespace is removed. It now uses the
   `makinacorpus/profiling` packages instead for profiling, which gives better
   measures and has more features.

## Converter internal API changes

### Design changes

Prior to 4.x version, the only type value taken into account for the converter
API was the SQL value type. From the SQL type, the converter arbitrarily chose
any of the converter exposing support for it, and returned the converted value
whichever was the returned PHP value.

Using this API, it was not possible to write type-safe code in userland, since
the user couldn't explicitely control the returned PHP value type.

New 4.x converter algorithm now accepts an expected PHP value type encourages
the user to pass it explicitely. Converters don't expose a supported SQL type
but a list of supported possible from SQL to PHP type conversions.

This new API is used correctly allow the user to write type safe-code since it
will either:

 - return a value whose type is the expected type,
 - return null is value on the SQL side is null,
 - raise a `\Goat\Converter\TypeConversionError` if conversion is not possible.

This new algorithm works bothways:

 - when fetching an SQL value and converting it to an expected userland PHP
   type (what we call the `output`),
 - when sending a PHP value to SQL as parameter to a query (what we call the
   `input`.

All these possible transitions are kept static in an array in a two-dimensional
array in memory. Whereas this algorithm is probably a bit slower than the previous
giant switch case over the SQL type, it is much more scalable and will remain
efficient when we'll add new types.

Due to this invasive change, API could not be kept compatible.

You will experience breakages in your userland code in one and only one use-case
which is if you wrote custom SQL converters or interfered with components
registration in your framework integration.

The runner, query builder, expressions, and driver API don't suffer from API
changes due to this code, and kept the exact same API for userland.

### Code changes

The `\Goat\Converter\ConverterInterface`, `\Goat\Converter\ValueConverterRegistry`
and `ValueConverterInterface` interfaces have been removed, and replaced with:

 - `\Goat\Converter\Converter` is now the new user facing API for using converters.
 - `\Goat\Converter\InputValueConverter` allows writing from PHP to SQL converters.
 - `\Goat\Converter\OutputValueConverter` allows writing from SQL to PHP converters.

For both `input` and `output` converters, two implementations are possible:

 - `static` implementation: the converter registers a list of possible
   conversions, which is being registered in a static array, it allows fast
   lookup in possible conversions at runtime.

 - `dynamic` implementation: for when you cannot tell if a conversion is
   possible from a static type name (for example, PostgreSQL arrays which are
   a dynamic string such as `OTHERTYPE[]` or `_OTHERTYPE`) which provides two
   `supportsInput()` and `supportsOutput()` methods which will be run for each
   value to convert, and are much slower.

## Result iterator hydrator signature change

Prior to 4.x version, the `\Goat\Runner\ResultIterator::setHydrator()` method
expected a `callable` whose signature was `function (array $row): mixed`.

The 4.x version now changes the default expected signature to
`function (\Goat\Runner\Row $row): mixed`. The `Row` interface is an
indirection between the SQL result array and userland signature which allows to
transparently use the new converter API:

 - The `\Goat\Runner\Row::get(int|string $name, ?string $phpType): mixed` method
   allows the userland hydrator to specify the expected PHP type, thus allows
   your hydrators to be written in a type-safe manner.

 - The value hydration (SQL to PHP type conversion) process is now lazy, and
   will executed only on-demand in the hydrator, and only for the fields you
   need to convert.

 - This lead to a cut in half of the whole `AbstractResultIterator` class
   since the responsability of converting values was removed from it, making it
   much more solid, resilient and maintanable.

For backward compatibility, when userland code sets an hydrator to a result
iterator, the provided callable will be introspected, and prior to 4.x expected
signature will be honnored.

All users are strongly advised to convert all their hydrators to the new API,
the old one is much slower, and the legacy hydrator signature support will be
dropped in next major.

## Performance improvements in SQL writer

SQL writer was fine tuned to avoid regex parsing of generated SQL for catching
the `?` placeholder:

 - Driver-specific placeholders are now directly written in the generated SQL
   along the query AST traversal, eliminating the need of re-parsing the
   generated SQL.

 - All user given arguments, without exception, are traversed as well during
   query generation, and such ordering is always respected.

 - The only SQL being parsed are `RawExpression` instances, which now accept
   expressions as arguments.

## 3.x last-minute undocumented changes

### User given arguments can now be expressions

All user given query arguments can now always be any expression. This means that
all expressions given as arguments, in all cases, will be formated and generated
SQL will be injected in the user given expression at the expected position.

This opens infinite possibilities for writing complex SQL statements.

### Constant table expression column names can be specified

Constant table expression allows to give aliases for each column in rows it
defines and allows you to write this kind of SQL statement:

```php
$expression = new ConstantTableExpression();
$expression->columns(['foo', 'bar', 'baz']);
$expression->row([1, 2, 3]);

$select = new SelectQuery($expression, 'foobar');
```

Which will be formatted as:

```sql
select *
from (
    values
    (?, ?, ?)
) as "foobar" ("foo", "bar", "baz")
```

Column aliases for constant table expression are supported in those use
cases:

 - When used in a CTE (`WITH` clause),
 - When used in the `FROM` clause (in any of `FROM`, `JOIN` or `UNION`).

### CastExpression for explicit value cast

Prior to 4.x, you could use `\Goat\Query\Expression\ValueExpression` to give
explicit typing information to any input value you passed along a query to the
server. This expression gives hint to the convert on how to convert a value but
it didn't propagated type information within the SQL query itself.

In case you need to write an explicit SQL standard `CAST(foo AS type)`
expression, you can use the `\Goat\Query\Expression\CastExpression` whose
constructor signature is the same as the `ValueExpression`, with an additional
third parameter which is the converter-only type hint.

In the meantime, both `ValueExpression` and `CastExpression` now allow any
arbitrary expression to be given as value.

### Composite type / constant row as arguments

Constant rows were expressions the user could give to the query builder,
additionnaly they now are values as well, and an associated converter is
implemented, this means that you can:

 - send arbitrary constant rows as values / arguments in queries,
 - fetch constant rows as output from the SQL server.

For now the implementation will always convert output constant rows or composite
types as PHP array, but in the future it will possible for the user to register
custom hydrators for named composite types.
