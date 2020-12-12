<?php

declare(strict_types=1);

namespace Goat\Schema\Tools;

use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\TableMetadata;
use Goat\Schema\Browser\AbstractSchemaVisitor;
use Goat\Schema\Browser\Context;
use Goat\Schema\ColumnMetadata;

/**
 * Produce Graphviz schema using the dot format.
 *
 * For now, it will work only while browsing a single schema.
 *
 * @todo
 *   Styling has been shamlessly stolen from doctrine/dbal own graphviz visitor
 *   yet it is not very good looking, we will have to work on this. 
 *
 * @experimental
 */
final class GraphvizVisitor extends AbstractSchemaVisitor
{
    private string $output = '';

    public function __construct(?string $schema = null)
    {
        if (!$schema) {
            $schema = "G";
        }

        // @todo escape schema name?
        $this->write(<<<EOT
            digraph "{$schema}" {
                splines = true;
                overlap = false;
                outputorder = edgesfirst;
            EOT
        );
    }

    public function getOutput(): string
    {
        return $this->output . "}\n";
    }

    public function onTable(Context $context, TableMetadata $table): void
    {
        $this->node(
            $this->tableNodeName($table),
            [
                'label' => $this->tableNodeLabel($table), // $this->createTableLabel($table),
                'shape' => 'plaintext',
            ]
        );
    }

    public function onForeignKey(
        Context $context,
        ForeignKeyMetatadata $foreignKey,
        TableMetadata $sourceTable,
        TableMetadata $targetTable
    ): void {
        $this->relation(
            $this->tableNodeName($sourceTable) . ':' . $this->columnPortName(\current($foreignKey->getColumnNames())) . ':se',
            $this->tableNodeName($targetTable) . ':' . $this->columnPortName(\current($foreignKey->getForeignColumnNames())) . ':se',
            [
                'dir' => 'back',
                'arrowtail' => 'dot',
                'arrowhead' => 'normal',
                'label' => $foreignKey->getName(),
            ]
        );
    }

    private function tableNodeLabel(TableMetadata $table): string
    {
        $name = $table->getSchema() . '.' . $table->getName();

        $label = <<<EOT
            <<TABLE CELLSPACING="0" BORDER="1" ALIGN="LEFT">
            <TR><TD BORDER="0" COLSPAN="3" ALIGN="CENTER" BGCOLOR="#fcaf3e">
            <FONT COLOR="#2e3436" FACE="Helvetica" POINT-SIZE="12">{$name}</FONT></TD></TR>
            EOT
        ;

        $primaryKey = $table->getPrimaryKey();

        foreach ($table->getColumns() as $column) {
            \assert($column instanceof ColumnMetadata);

            $columnLabel = $column->getName();
            $columnPortName = $this->columnPortName($column->getName());
            $columnType = $column->getType();

            $label .= "\n" . <<<EOT
                <TR>
                <TD BORDER="0" ALIGN="LEFT" BGCOLOR="#eeeeec">
                <FONT COLOR="#2e3436" FACE="Helvetica" POINT-SIZE="12">{$columnLabel}</FONT>
                </TD>
                <TD BORDER="0" ALIGN="LEFT" BGCOLOR="#eeeeec">
                <FONT COLOR="#2e3436" FACE="Helvetica" POINT-SIZE="10">{$columnType}</FONT>
                </TD>
                <TD BORDER="0" ALIGN="RIGHT" BGCOLOR="#eeeeec" PORT="{$columnPortName}">
                EOT;

            $primaryKey = $table->getPrimaryKey();
            if ($primaryKey && \in_array($column->getName(), $primaryKey->getColumnNames())) {
                $label .= "#";
            }

            $label .= '</TD></TR>';
        }

        return $label . "\n</TABLE>>";
    }

    private function tableNodeName(TableMetadata $table): string
    {
        return $table->getSchema() . '__' . $table->getName();
    }

    private function columnPortName(string $name): string
    {
        return 'col_' . \strtolower($name);
    }

    private function node(string $name, array $options): void
    {
        $output = '    ' . $name . ' [';
        foreach ($options as $key => $value) {
            $output .= $key . '=' . $value . ' ';
        }

        $this->write($output . "]");
    }

    private function relation(string $from, string $to, array $options): void
    {
        $output = '    ' . $from . ' -> ' . $to . ' [';
        foreach ($options as $key => $value) {
            $output .= $key . '=' . $value . ' ';
        }
        $this->write($output . "]");
    }

    private function write(string $string): void
    {
        $this->output .= $string . "\n";
    }
}
