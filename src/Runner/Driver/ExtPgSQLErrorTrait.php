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
    private function resultError($resource = null)
    {
        $status = \pg_result_status($resource);

        if (PGSQL_BAD_RESPONSE === $status || PGSQL_FATAL_ERROR === $status) {
            $errorString = \pg_result_error($resource);
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
            throw new DriverError($errorString);
        } else {
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
            throw new DriverError($errorString, (int)\pg_connection_status($resource));
        }
    }
}
