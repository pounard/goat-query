<?php

declare(strict_types=1);

namespace Goat\Schema\Tests\Browser;

use Goat\Schema\ColumnMetadata;
use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\KeyMetatadata;
use Goat\Schema\TableMetadata;
use Goat\Schema\Browser\AbstractSchemaVisitor;
use Goat\Schema\Browser\Context;

/**
 * @todo Because the schema is a generated unique non predictable name:
 *   - We cannot test cross-schema introspection,
 *   - We cannot test list schema,
 *   - We cannot output schema names.
 */
final class OutputVisitor extends AbstractSchemaVisitor
{
    private $output = '';

    public function getOutput()
    {
        return $this->output;
    }

    public function onTable(
        Context $context,
        TableMetadata $table
    ): void {
        $this->write(\implode('; ', [
            $context->getDepth(),
            /* $context->getRootSchema() . '.' . */ $context->getRootTable(),
            'TABLE',
            $this->table($table)
        ]));
    }

    public function onColumn(
        Context $context,
        ColumnMetadata $column,
        TableMetadata $table
    ): void {
        $this->write(\implode('; ', [
            $context->getDepth(),
            /* $context->getRootSchema() . '.' . */ $context->getRootTable(),
            'COLUMN',
            $this->column($column),
        ]));
    }

    public function onPrimaryKey(
        Context $context,
        KeyMetatadata $primaryKey,
        TableMetadata $sourceTable
    ) {
        $this->write(\implode('; ', [
            $context->getDepth(),
            /* $context->getRootSchema() . '.' . */ $context->getRootTable(),
            'PRIMARY KEY',
            $this->key($primaryKey)
        ]));
    }

    public function onForeignKey(
        Context $context,
        ForeignKeyMetatadata $foreignKey,
        TableMetadata $sourceTable,
        TableMetadata $targetTable
    ): void {
        $this->write(\implode('; ', [
            $context->getDepth(),
            /* $context->getRootSchema() . '.' . */ $context->getRootTable(),
            'FOREIGN KEY',
            $this->foreignKey($foreignKey)
        ]));
    }

    public function onReverseForeignKey(
        Context $context,
        ForeignKeyMetatadata $foreignKey,
        TableMetadata $sourceTable,
        TableMetadata $targetTable
    ): void {
        $this->write(\implode('; ', [
            $context->getDepth(),
            /* $context->getRootSchema() . '.' . */ $context->getRootTable(),
            'REVERSE FOREIGN KEY',
            $this->foreignKey($foreignKey)
        ]));
    }

    private function write(string $line)
    {
        $this->output .= $line . "\n";
    }

    private function column(ColumnMetadata $column): string
    {
        return /* $column->getSchema() . '.' . */ $column->getTable() . '.' . $column->getName();
    }

    private function table(TableMetadata $table): string
    {
        return /* $table->getSchema() . '.' . */ $table->getName();
    }

    private function key(KeyMetatadata $key): string
    {
        return $key->getName()
            . ' ON '
            /* . $key->getSchema() . '.'  */ . $key->getTable()
            . ' (' . \implode(', ', $key->getColumnNames()) . ')';
    }

    private function foreignKey(ForeignKeyMetatadata $key): string
    {
        return $this->key($key)
            . ' REFERENCES '
            /* . $key->getForeignSchema() . '.' */ . $key->getForeignTable()
            . ' (' . \implode(', ', $key->getForeignColumnNames()) . ')';
    }
}
