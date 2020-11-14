<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Driver\Error;
use Goat\Runner\ServerError;

trait ExtPgSQLErrorTrait
{
    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $result
     *   Result resource (not connection one).
     */
    private function resultError($resource = null): void
    {
        $status = \pg_result_status($resource, PGSQL_STATUS_LONG);

        if ($status === PGSQL_COMMAND_OK || $status === PGSQL_TUPLES_OK) {
            return;
        }

        $errorString = \pg_result_error($resource);
        $sqlState = \pg_result_error_field($resource, PGSQL_DIAG_SQLSTATE);

        if (false === $errorString) {
            $errorString = "unknown error: could not fetch status code";
        }

        throw $this->createException($errorString, $sqlState);
    }

    /**
     * Throw an exception if the given status is an error
     *
     * @param resource $resource
     *   Connection resource (not a result one).
     * @param string $rawSQL
     *   Raw SQL string sent if knonw.
     */
    private function serverError($resource = null, string $rawSQL = null): void
    {
        $errorString = false;
        $sqlState = null;

        if (null !== $resource) {
            if ($errorString = @\pg_last_error($resource)) {
                // And now we parse...
                //
                // We did set \pg_set_error_verbosity() to PGSQL_ERRORS_VERBOSE
                // during driver init in ExtPgSQLDriver::connect() method, so
                // we can expect to have this kind of result:
                //
                //    TRANSLATED ERROR WORD: XXXXX: MESSAGE
                //
                // where "XXXXX" is the SQLSTATE code, and translation depends
                // upon PostgreSQL configuration.
                //
                // The only way you could fetch the SQLSTATE otherwise would be
                // by sending asynchronous SQL queries, but we would have to
                // to implement application-side polling for fetching result,
                // this would be very bad for performances.
                $exploded = \explode(':', $errorString, 3);
                if (\count($exploded) >= 2 && \preg_match('/^[a-z0-9 ]+$/i', $exploded[1])) {
                    $sqlState = \trim($exploded[1]);
                }
            }
        }

        if (false === $errorString) {
            $errorString = "unknown error: could not fetch status code";
            if ($rawSQL) {
                $errorString .= ', query was: ' .$rawSQL;
            }
        } else if ($rawSQL) {
            $errorString .= ', query was: ' .$rawSQL;
        }

        throw $this->createException($errorString, $sqlState, $rawSQL);
    }

    /**
     * Create exception.
     */
    private function createException(string $errorString, ?string $sqlState = null, ?string $rawSql = null): \Throwable
    {
        if (!$sqlState) {
            throw new ServerError($rawSql, $sqlState, null, $errorString);
        }

        switch ($sqlState) {

            case '40001':
            case '40P01':
                return new Error\TransactionDeadlockError($errorString);

            case '0A000':
                // Foreign key constraint violations during a TRUNCATE operation
                // are considered "feature not supported" in PostgreSQL.
                if (\strpos($errorString, 'truncate') !== false) {
                    return new Error\ForeignKeyConstraintViolationError($errorString);
                }

                break;

            case '23502':
                return new Error\NotNullConstraintViolationError($errorString);

            case '23503':
                return new Error\ForeignKeyConstraintViolationError($errorString);

            case '23505':
                return new Error\UniqueConstraintViolationError($errorString);

            /*
            case '42601':
                // Syntax error.
             */

            case '42702':
                return new Error\AmbiguousIdentifierError($errorString);

            /*
            case '42703':
                // Invalid identifier.
             */

            case '42P01':
                return new Error\TableDoesNotExistError($errorString);

            /*
            case '42P07':
                // Table exists.
             */

            /*
            case '7':
                // In some case (mainly connection errors) the PDO exception does not provide a SQLSTATE via its code.
                // The exception code is always set to 7 here.
                // We have to match against the SQLSTATE in the error message in these cases.
                if (\strpos($errorString, 'SQLSTATE[08006]') !== false) {
                    // Connexion error.
                }

                break;
             */
        }

        // Attempt with classes if we do not handle the specific SQL STATE code.
        switch (\substr($sqlState, 2)) {

            case '40':
                return new Error\TransactionError($errorString);
        }

        throw new ServerError($rawSql, $sqlState, null, $errorString);
    }
}
