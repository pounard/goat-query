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

## 2.0 results

```
+-----------------+------------------------+-----+-------+-----+-----------+----------+---------+
| benchmark       | subject                | set | revs  | its | mem_peak  | mode     | rstdev  |
+-----------------+------------------------+-----+-------+-----+-----------+----------+---------+
| SqlWriterBench  | benchArbitrary         | 0   | 10000 | 5   | 872.048kb | 15.098μs | ±2.78%  |
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 862.688kb | 0.125μs  | ±4.71%  |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 862.688kb | 0.126μs  | ±17.32% |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 862.720kb | 0.417μs  | ±23.11% |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 862.720kb | 0.213μs  | ±0.79%  |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 862.720kb | 2.376μs  | ±1.83%  |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 862.688kb | 1.303μs  | ±2.03%  |
+-----------------+------------------------+-----+-------+-----+-----------+----------+---------+
```

## From 2.0 to 2.1

```
+-----------------+------------------------+-----+-------+-----+------------------+-----------------+-----------------+
| benchmark       | subject                | set | revs  | its | mem_peak         | mode            | rstdev          |
+-----------------+------------------------+-----+-------+-----+------------------+-----------------+-----------------+
| SqlWriterBench  | benchArbitrary         | 0   | 10000 | 5   | 872.048kb +0.05% | 14.384μs -4.73% | ±1.28% -54.09%  |
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.113μs -9.30%  | ±5.91% +25.43%  |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.122μs -3.59%  | ±1.04% -94.02%  |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.420μs +0.70%  | ±3.02% -86.95%  |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.219μs +3.18%  | ±1.65% +109.96% |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 862.720kb 0.00%  | 2.387μs +0.44%  | ±2.25% +22.61%  |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 1.337μs +2.57%  | ±2.24% +10.85%  |
+-----------------+------------------------+-----+-------+-----+------------------+-----------------+-----------------+
```

2.1 version induces slight performance improvements in the converter API.

## From 2.1 to 3.0

```
+-----------------+------------------------+-----+-------+-----+------------------+------------------+----------------+
| benchmark       | subject                | set | revs  | its | mem_peak         | mode             | rstdev         |
+-----------------+------------------------+-----+-------+-----+------------------+------------------+----------------+
| SqlWriterBench  | benchArbitrary         | 0   | 10000 | 5   | 872.480kb +3.91% | 10.455μs -27.31% | ±1.41% +10.84% |
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.138μs +21.56%  | ±3.56% -39.73% |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.143μs +17.76%  | ±1.68% +62.19% |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.450μs +7.04%   | ±3.01% -0.38%  |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.240μs +9.54%   | ±1.72% +4.07%  |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 862.720kb 0.00%  | 2.882μs +20.75%  | ±0.77% -65.70% |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 1.841μs +37.73%  | ±0.83% -62.83% |
+-----------------+------------------------+-----+-------+-----+------------------+------------------+----------------+
```

3.0 version:

 - causes a 30% performance penaly in converter API.
 - improve SQL formating by 30%.

## From 3.0 to 4.0

```
+-----------------+------------------------+-----+-------+-----+------------------+------------------+-----------------+
| benchmark       | subject                | set | revs  | its | mem_peak         | mode             | rstdev          |
+-----------------+------------------------+-----+-------+-----+------------------+------------------+-----------------+
| SqlWriterBench  | benchArbitrary         | 0   | 10000 | 5   | 906.616kb +2.58% | 10.329μs -1.21%  | ±3.72% +163.13% |
| ConversionBench | benchIntFromSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.268μs +94.40%  | ±0.57% -84.07%  |
| ConversionBench | benchIntToSql          | 0   | 10000 | 5   | 862.688kb 0.00%  | 0.291μs +102.85% | ±2.19% +30.71%  |
| ConversionBench | benchRamseyUuidFromSql | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.427μs -4.94%   | ±0.90% -69.97%  |
| ConversionBench | benchRamseyUuidToSql   | 0   | 10000 | 5   | 862.720kb 0.00%  | 0.530μs +120.35% | ±3.75% +118.07% |
| ConversionBench | benchArrayFromSql      | 0   | 10000 | 5   | 862.720kb 0.00%  | 3.489μs +21.08%  | ±1.46% +90.05%  |
| ConversionBench | benchArrayToSql        | 0   | 10000 | 5   | 862.688kb 0.00%  | 2.679μs +45.49%  | ±0.76% -8.61%   |
+-----------------+------------------------+-----+-------+-----+------------------+------------------+-----------------+
```

4.0 version causes a 100% performance penaly in converter API with scalar values.

To be noted here, this is mitigated when converting arrays and UUID values, the
converter API rewrite was previously based upon an huge `switch` statement which
is now a PHP-multidimensional array representing PHP to SQL and SQL to PHP types
possible conversion.
