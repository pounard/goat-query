<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Query\QueryError;
use Goat\Runner\ServerError;

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
    private function serverError($resource = null, string $rawSQL = null)
    {
        $errorString = false;
        $pgStatus = 0;

        if (null !== $resource) {
            $errorString = @\pg_last_error($resource);
            $pgStatus = @\pg_connection_status($resource);
        }

        if (false === $errorString) {
            $errorString = "unknown error: could not fetch status code";
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }

            throw new ServerError($errorString);

        } else {
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }

            throw new ServerError($errorString, $pgStatus);
        }
    }
}
