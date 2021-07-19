# Benchmark

Benchmarks uses `phpbench/phpbench` library.

## Usage

Arbitrarily run benchmarks:

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate
```

Run and tag benchmark (for later comparison):

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate --tag=v30
```

Run benchmark and compare with tag:

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate --ref=v30
```

## Preliminary results

### Converter API from 3.x to 4.x

3.0.0 version yields excellent converter performance:

```
+-----------------+------------------------+-----+-------+-----+----------+---------+--------+
| benchmark       | subject                | set | revs  | its | mem_peak | mode    | rstdev |
+-----------------+------------------------+-----+-------+-----+----------+---------+--------+
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 1.402mb  | 0.138μs | ±5.76% |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 1.402mb  | 0.142μs | ±1.23% |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 1.402mb  | 0.437μs | ±4.86% |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 1.402mb  | 0.234μs | ±4.72% |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 1.402mb  | 2.709μs | ±1.61% |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 1.402mb  | 1.831μs | ±4.21% |
+-----------------+------------------------+-----+-------+-----+----------+---------+--------+
```

4.0.0 version yields a 2x performance penalty:

```
+-----------------+------------------------+-----+-------+-----+---------------+------------------+------------------+
| benchmark       | subject                | set | revs  | its | mem_peak      | mode             | rstdev           |
+-----------------+------------------------+-----+-------+-----+---------------+------------------+------------------+
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 1.402mb 0.00% | 0.270μs +96.41%  | ±16.22% +181.68% |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 1.402mb 0.00% | 0.301μs +112.40% | ±4.37% +256.32%  |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 1.402mb 0.00% | 0.443μs +1.28%   | ±4.23% -12.92%   |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 1.402mb 0.00% | 0.519μs +121.73% | ±8.49% +79.97%   |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 1.402mb 0.00% | 3.362μs +24.09%  | ±3.48% +115.41%  |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 1.402mb 0.00% | 2.609μs +42.51%  | ±2.11% -50.03%   |
+-----------------+------------------------+-----+-------+-----+---------------+------------------+------------------+
```

To be noted that thanks to the new API, conversion is now done lazily, on-demande
and in many cases, this may yield, in the end, best performances.
