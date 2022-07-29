<?php

namespace PrestashopModels\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class GeneratorCommand extends Command
{
    /** @var string */
    private $namespace;

    public function __construct($namespace)
    {
        parent::__construct(static::$defaultName);
        $this->namespace = $namespace;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    abstract protected function getStub();

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get the tests autoload configuration namespace.
     *
     * @return string
     */
    public function getTestsAutoloadNamespace()
    {
        $composer = json_decode(file_get_contents($this->getNamespace() . '/composer.json'), true);

        if (empty($composer['autoload-dev']['psr-4'])) {
            return '';
        }

        foreach ($composer['autoload-dev']['psr-4'] as $namespace => $path) {
            if ($path !== 'tests/') {
                continue;
            }

            return $namespace;
        }

        return '';
    }

    protected function makeDirectory($path)
    {
        @mkdir($path, 0777, true);
    }

    public function readStub()
    {
        return file_get_contents($this->getStub());
    }
}