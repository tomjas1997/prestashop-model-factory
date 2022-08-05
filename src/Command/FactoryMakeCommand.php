<?php

namespace Invertus\Prestashop\Models\Command;

use Invertus\Prestashop\Models\Helpers\NamespaceHelper;
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

        $factoryName = $input->getArgument('factoryName');
        $modelNamespace = $input->getOption('model');

        $path = $this->getNamespace().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Factories'.DIRECTORY_SEPARATOR.'Models';

        $factoryPath = $path.DIRECTORY_SEPARATOR.$factoryName.'.php';

        if (file_exists($factoryPath)) {
            $io->error('Factory already exists');

            return false;
        }

        $this->makeDirectory($path);

        if (empty($modelNamespace)) {
            $io->note('Model was not passed, deciding model from factory name.');
        }

        if (!empty($modelNamespace) && !class_exists($modelNamespace)) {
            $io->error('Model namespace passed does not exist');

            return false;
        }

        file_put_contents($factoryPath, $this->buildClass(
            $factoryName,
            $modelNamespace
        ));

        $io->success(sprintf('Successfully created %s', $factoryName));

        return true;
    }

    protected function buildClass($factoryName, $optionNamespace)
    {
        $factory = basename(str_replace('Factory', '', $factoryName));

        $namespaceModel = !empty($optionNamespace)
            ? $this->qualifyModel($optionNamespace)
            : $this->qualifyModel($this->guessModelName($factoryName));

        $model = basename(str_replace('\\', '/', $namespaceModel));

        if (!empty($autoloadNamespace = NamespaceHelper::getTestsAutoloadNamespaceFromComposer($this->getNamespace()))) {
            $namespace = $autoloadNamespace.'Factories\Models';
        } else {
            $namespace = 'Tests\Factories\Models';
        }

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{ factory }}' => $model,
            '{{ model }}' => $model
        ];

        return str_replace(
            array_keys($replace), array_values($replace), $this->readStub()
        );
    }

    protected function guessModelName($factoryName)
    {
        $modelName = basename(str_replace('Factory', '', $factoryName));

        if (class_exists($modelName)) {
            return $modelName;
        }

        return 'ObjectModel';
    }

    protected function qualifyModel($model)
    {
        return str_replace('/', '\\', ltrim($model, '\\/'));
    }
}