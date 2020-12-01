<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\MySQLConverter;
use Goat\Driver\Error;

class PDOMySQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateConverter(): ConverterInterface
    {
        return new MySQLConverter();
    }

    /**
     * {@inheritdoc}
     *
     * @link http://dev.mysql.com/doc/refman/5.7/en/error-messages-client.html
     * @link http://dev.mysql.com/doc/refman/5.7/en/error-messages-server.html
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

        switch ($errorCode) {

            case '1213':
                return new Error\TransactionDeadlockError($error->getMessage(), $errorCode, $error);

            case '1205':
                return new Error\TransactionLockWaitTimeoutError($error->getMessage(), $errorCode, $error);

            /*
            case '1050':
                // Table exists.
             */

            case '1051':
            case '1146':
                return new Error\TableDoesNotExistError($error->getMessage(), $errorCode, $error);

            case '1216':
            case '1217':
            case '1451':
            case '1452':
            case '1701':
                return new Error\ForeignKeyConstraintViolationError($error->getMessage(), $errorCode, $error);

            case '1062':
            case '1557':
            case '1569':
            case '1586':
                return new Error\UniqueConstraintViolationError($error->getMessage(), $errorCode, $error);

            /*
            case '1054':
            case '1166':
            case '1611':
                // Invalid identifier.
             */

            case '1052':
            case '1060':
            case '1110':
                return new Error\AmbiguousIdentifierError($error->getMessage(), $errorCode, $error);

            /*
            case '1064':
            case '1149':
            case '1287':
            case '1341':
            case '1342':
            case '1343':
            case '1344':
            case '1382':
            case '1479':
            case '1541':
            case '1554':
            case '1626':
                // Syntax error.
             */

            /*
            case '1044':
            case '1045':
            case '1046':
            case '1049':
            case '1095':
            case '1142':
            case '1143':
            case '1227':
            case '1370':
            case '1429':
            case '2002':
            case '2005':
                // Connection error.
             */

            case '1048':
            case '1121':
            case '1138':
            case '1171':
            case '1252':
            case '1263':
            case '1364':
            case '1566':
                return new Error\NotNullConstraintViolationError($error->getMessage(), $errorCode, $error);
        }

        return $error;
    }
}
