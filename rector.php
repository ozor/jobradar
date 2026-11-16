<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

// Not part of `make check`: run `make rector` (dry-run) and apply with `make rector-fix`
// only as a deliberate, separately reviewed change.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/var/cache/rector')
    ->withPhpSets(php84: true)
    // Symfony upgrade sets matching the installed symfony/* version (8.1).
    ->withComposerBased(symfony: true)
    ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml')
;
