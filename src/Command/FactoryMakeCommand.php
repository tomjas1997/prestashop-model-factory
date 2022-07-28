<?php

namespace PrestashopFactories\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FactoryMakeCommand extends GeneratorCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'make:factory';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Create a new model factory!';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/Factory.stub';
    }

    protected function configure()
    {
        $this
            ->addArgument('factoryName', InputArgument::REQUIRED, 'Factory name')
            ->addOption('model', 'm', InputOption::VALUE_REQUIRED, 'Model namespace. Example: "\Tab"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $testsNamespace = $this->getTestsNamespace();

        if (empty($testsNamespace)) {
            $io->error('composer.json must have autoload-dev configuration for tests/ folder.');

            return false;
        }

        $factoryName = $input->getArgument('factoryName');
        $modelNamespace = $input->getOption('model');

        $path = $this->getNamespace().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Factories'.DIRECTORY_SEPARATOR.'Entities';

        $factoryPath = $path.DIRECTORY_SEPARATOR.$factoryName.'.php';

        if (file_exists($factoryPath)) {
            $io->error('Factory already exists');

            return false;
        }

        $this->makeDirectory($path);

        if (empty($modelNamespace)) {
            //NOTE defaulting if no model was passed.
            $modelNamespace = '\ObjectModel';
        }

        file_put_contents($factoryPath, $this->buildClass(
            $factoryName,
            $modelNamespace
        ));

        $io->success(sprintf('Successfully created %s', $factoryName));

        return true;
    }

    protected function buildClass($factoryName, $modelNamespace)
    {
        $factory = ucfirst(strtolower(str_replace('Factory', '', $factoryName)));

        $model = basename(str_replace('\\', '/', $modelNamespace));

        $namespace = $this->getTestsNamespace().'Factories\Entities';

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            '{{ namespacedModel }}' => $modelNamespace,
            '{{ factory }}' => $factory,
            '{{ model }}' => $model
        ];

        return str_replace(
            array_keys($replace), array_values($replace), $this->readStub()
        );
    }
}