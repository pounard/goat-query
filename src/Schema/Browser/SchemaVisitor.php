<?php

declare(strict_types=1);

namespace Goat\Schema\Browser;

use Goat\Schema\ColumnMetadata;
use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\KeyMetatadata;
use Goat\Schema\TableMetadata;

interface SchemaVisitor
{
    public function onTable(
        Context $context,
        TableMetadata $table
    ): void;

    public function onColumn(
        Context $context,
        ColumnMetadata $column,
        TableMetadata $table
    ): void;

    public function onPrimaryKey(
        Context $context,
        KeyMetatadata $primaryKey,
        TableMetadata $sourceTable
    );

    public function onForeignKey(
        Context $context,
        ForeignKeyMetatadata $foreignKey,
        TableMetadata $sourceTable,
        TableMetadata $targetTable
    ): void;

    public function onReverseForeignKey(
        Context $context,
        ForeignKeyMetatadata $foreignKey,
        TableMetadata $sourceTable,
        TableMetadata $targetTable
    ): void;
}
