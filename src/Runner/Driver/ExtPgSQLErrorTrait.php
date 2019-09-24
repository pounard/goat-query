<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Query\QueryError;

trait ExtPgSQLErrorTrait
{
    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $result
     */
    private function resultError($result)
    {
        $status = \pg_result_status($this->resource);

        if (PGSQL_BAD_RESPONSE === $status || PGSQL_FATAL_ERROR === $status) {
            $errorString = \pg_result_error($result);
            if (false === $errorString) {
                throw new QueryError("unknown error: could not fetch status code");
            } else {
                throw new QueryError($errorString, $status);
            }
        }
    }

    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $resource
     * @param string $rawSQL
     */
    private function driverError($resource = null, string $rawSQL = null)
    {
        $errorString = \pg_last_error($resource);
        if (false === $errorString) {
            $errorString = "unknown error: could not fetch status code";
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
            throw new QueryError($errorString);
        } else {
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
            throw new QueryError($errorString, (int)\pg_connection_status($resource));
        }
    }
}
