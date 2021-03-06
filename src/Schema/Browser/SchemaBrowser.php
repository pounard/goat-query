<?php

declare(strict_types=1);

namespace Goat\Schema\Browser;

use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\SchemaIntrospector;
use Goat\Schema\TableMetadata;

/**
 * Schema is almost a graph.
 *
 * Technically it is a flat list of symbols (relations, sequences, views, ...)
 * but using relations, we can browse the schema from a table.
 *
 * Two variants are exposed:
 *
 *   - starting from a single table,
 *   - starting from a complete schema (list of tables).
 *
 * For each variant, you will be allowed to expose reverse relation browsing
 * with the following possible scenarios:
 *
 *   - always browse in relation order,
 *   - always browse in relation reverse order,
 *   - browse both.
 *
 * You can then implement behaviors your way.
 *
 * Be warned that it's your job to break circular dependencies while browsing
 * otherwise you could easily end up with infinite recursion scenarios:
 *
 *   - table list columns,
 *   - columns are final,
 *   - table expose primary key,
 *   - primary keys are final,
 *   - table recurse in relations,
 *   - relations recurse into tables.
 *
 * If you have bothways relations between two tables, it is likely that you
 * will experience an infinite loop. Per default, context will prevent that.
 */
final class SchemaBrowser
{
    const MODE_RELATION_NORMAL = 1;
    const MODE_RELATION_REVERSE = 2;
    const MODE_RELATION_BOTH = 3;

    private SchemaIntrospector $schemaInstrospector;
    /** @var SchemaVisitor[] */
    private array $visitors = [];

    public function __construct(SchemaIntrospector $schemaInstrospector)
    {
        $this->schemaInstrospector = $schemaInstrospector;
    }

    public function visitor(SchemaVisitor $visitor): self
    {
        $this->visitors[] = $visitor;

        return $this;
    }

    public function browse(int $mode = self::MODE_RELATION_NORMAL): void
    {
        $context = new DefaultContext($mode, '_none', '_none');

        foreach ($this->schemaInstrospector->listSchemas() as $schema) {
            $context->enter();
            try {
                foreach ($this->schemaInstrospector->listTables($schema) as $tableName) {
                    $context->switch($schema, $tableName);
                    $this->doBrowseTable($schema, $tableName, $context);
                }
            } finally {
                $context->leave();
            }
        }
    }

    public function browseSchema(string $schema, int $mode = self::MODE_RELATION_NORMAL): void
    {
        $context = new DefaultContext($mode, $schema, '_none');

        foreach ($this->schemaInstrospector->listTables($schema) as $tableName) {
            $context->enter();

            try {
                $context->switch($schema, $tableName);
                $this->doBrowseTable($schema, $tableName, $context);
            } finally {
                $context->leave();
            }
        }
    }

    public function browseTable(string $schema, string $table, int $mode = self::MODE_RELATION_NORMAL): void
    {
        $context = new DefaultContext($mode, $schema, $table);

        $context->enter();

        try {
            $context->switch($schema, $table);
            $this->doBrowseTable($schema, $table, $context);
        } finally {
            $context->leave();
        }
    }

    private function doBrowseTable(string $schema, string $tableName, DefaultContext $context): void
    {
        $tableMetadata = $this->schemaInstrospector->fetchTableMetadata($schema, $tableName);

        $this->doBrowseTableMetadata($tableMetadata, $context);
    }

    private function doBrowseTableMetadata(TableMetadata $tableMetadata, DefaultContext $context): void
    {
        if ($context->hasAlreadyBrowsed($tableMetadata)) {
            return;
        }

        $context->enter();
        $context->markAsBroswed($tableMetadata);

        try {
            foreach ($this->visitors as $visitor) {
                $visitor->onTable($context, $tableMetadata);
            }

            if ($primaryKey = $tableMetadata->getPrimaryKey()) {
                $context->enter();
                $context->markAsBroswed($primaryKey);

                try {
                    foreach ($this->visitors as $visitor) {
                        $visitor->onPrimaryKey($context, $primaryKey, $tableMetadata);
                    }
                } finally {
                    $context->leave();
                }
            }

            foreach ($tableMetadata->getColumns() as $column) {
                $context->enter();
                $context->markAsBroswed($column);

                try {
                    foreach ($this->visitors as $visitor) {
                        $visitor->onColumn($context, $column, $tableMetadata);
                    }
                } finally {
                    $context->leave();
                }
            }

            $mode = $context->getBrowserMode();

            if (self::MODE_RELATION_NORMAL === $mode || self::MODE_RELATION_BOTH === $mode) {
                foreach ($tableMetadata->getForeignKeys() as $foreignKey) {
                    $this->doBrowseForeignKey($tableMetadata, $foreignKey, $context, false);
                }
            }

            if (self::MODE_RELATION_REVERSE === $mode || self::MODE_RELATION_BOTH === $mode) {
                foreach ($tableMetadata->getReverseForeignKeys() as $foreignKey) {
                    $this->doBrowseForeignKey($tableMetadata, $foreignKey, $context, true);
                }
            }
        } finally {
            $context->leave();
        }
    }

    private function doBrowseForeignKey(TableMetadata $fromTable, ForeignKeyMetatadata $foreignKey, DefaultContext $context, bool $reverse): void
    {
        $context->enter();
        $context->markAsBroswed($foreignKey);

        try {
            if ($reverse) {
                $otherTable = $this
                    ->schemaInstrospector
                    ->fetchTableMetadata(
                        $foreignKey->getSchema(),
                        $foreignKey->getTable()
                    )
                ;
            } else {
                $otherTable = $this
                    ->schemaInstrospector
                    ->fetchTableMetadata(
                        $foreignKey->getForeignSchema(),
                        $foreignKey->getForeignTable()
                    )
                ;
            }

            foreach ($this->visitors as $visitor) {
                if ($reverse) {
                    $visitor->onReverseForeignKey($context, $foreignKey, $otherTable, $fromTable);
                } else {
                    $visitor->onForeignKey($context, $foreignKey, $fromTable, $otherTable);
                }
            }

            // Foreign key constraint might reference self. 
            // @todo Here be dragons: infinite recusion must be broken here.
            if (!$fromTable->equals($otherTable)) {
                $this->doBrowseTableMetadata($otherTable, $context);
            }
        } finally {
            $context->leave();
        }
    }
}
