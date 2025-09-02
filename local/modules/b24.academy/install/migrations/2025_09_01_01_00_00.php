<?php

use B24\Academy\UI\Tasks\ListPageMutator;

\Bitrix\Main\Diag\Debug::writeToFile(
    __FILE__ . ':' . __LINE__ . "\n(" . date('Y-m-d H:i:s') . ")\n" . print_r([__DIR__ . '../files/css', $docRoot . '/local/css'], true) . "\n\n",
    '',
    'local/log/debug.log'
);

CopyDirFiles(__DIR__ . '/../files/css', $docRoot . '/local/css', true, true);

$em->registerEventHandler(
    'main',
    'OnEpilog',
    'b24.academy',
    ListPageMutator::class,
    'handleOnEpilog'
);

\Bitrix\Main\Config\Option::set('b24.academy', 'VERSION', 2025_09_01_01_00_00);

