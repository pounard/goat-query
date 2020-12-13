<?php

declare (strict_types=1);

namespace Goat\Console\Command;

use Goat\Console\App;
use Goat\Schema\Browser\SchemaBrowser;
use Goat\Schema\Tools\GraphvizVisitor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GraphvizCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('graphviz');
        $this->setAliases(['gv']);
        $this->setDescription("Generate graphviz source file.");

        App::configureCommand($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = App::getDatabaseDriver($input);
        $runner = $driver->getRunner();
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $schema = $input->getOption('schema') ?? 'public';

        $visitor = new GraphvizVisitor();

        (new SchemaBrowser($schemaIntrospector))
            ->visitor($visitor)
            ->browseSchema($schema, SchemaBrowser::MODE_RELATION_NORMAL)
        ;

        $output->writeln($visitor->getOutput());

        return 0;
    }
}
