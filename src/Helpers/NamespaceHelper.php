<?php

namespace Invertus\Prestashop\Models\Helpers;

class NamespaceHelper
{
    /**
     * Get the tests autoload configuration namespace.
     *
     * @return string
     */
    public static function getTestsAutoloadNamespaceFromComposer($namespace)
    {
        $composer = json_decode(file_get_contents($namespace . '/composer.json'), true);

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
}