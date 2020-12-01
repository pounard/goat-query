<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Driver\Error;

class PDOPgSQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateConverter(): ConverterInterface
    {
        return new PgSQLConverter();
    }

    /**
     * {@inheritdoc}
     *
     * @link http://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
     *
     * I have to admit, I was largely inspired by Doctrine DBAL for this one.
     * All credits to the Doctrine team, developers and contributors. You do
     * very impressive and qualitative work, I hope you will continue forever.
     * Many thanks to all contributors. If someday you come to France, give me
     * a call, an email, anything, and I'll pay you a drink, whoever you are.
     */
    protected function convertPdoError(\PDOException $error): \Throwable
    {
        $errorCode = $error->errorInfo[1] ?? $error->getCode();
        $sqlState = $error->errorInfo[0] ?? $error->getCode();

        switch ($sqlState) {

            case '40001':
            case '40P01':
                return new Error\TransactionDeadlockError($error->getMessage(), $errorCode, $error);

            case '0A000':
                // Foreign key constraint violations during a TRUNCATE operation
                // are considered "feature not supported" in PostgreSQL.
                if (\strpos($error->getMessage(), 'truncate') !== false) {
                    return new Error\ForeignKeyConstraintViolationError($error->getMessage(), $errorCode, $error);
                }

                break;

            case '23502':
                return new Error\NotNullConstraintViolationError($error->getMessage(), $errorCode, $error);

            case '23503':
                return new Error\ForeignKeyConstraintViolationError($error->getMessage(), $errorCode, $error);

            case '23505':
                return new Error\UniqueConstraintViolationError($error->getMessage(), $errorCode, $error);

            /*
            case '42601':
                // Syntax error.
             */

            case '42702':
                return new Error\AmbiguousIdentifierError($error->getMessage(), $errorCode, $error);

            /*
            case '42703':
                // Invalid identifier.
             */

            case '42P01':
                return new Error\TableDoesNotExistError($error->getMessage(), $errorCode, $error);

            /*
            case '42P07':
                // Table exists.
             */

            /*
            case '7':
                // In some case (mainly connection errors) the PDO exception does not provide a SQLSTATE via its code.
                // The exception code is always set to 7 here.
                // We have to match against the SQLSTATE in the error message in these cases.
                if (\strpos($error->getMessage(), 'SQLSTATE[08006]') !== false) {
                    // Connexion error.
                }

                break;
             */
        }

        // Attempt with classes if we do not handle the specific SQL STATE code.
        switch (\substr($sqlState, 2)) {

            case '40':
                return new Error\TransactionError($error->getMessage(), $errorCode, $error);
        }

        return $error;
    }
}
