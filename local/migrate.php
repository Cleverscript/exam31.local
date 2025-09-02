<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;

try {
    if (count($argv) < 2) {
        throw new RuntimeException("Не указана команда.\nДоступные команды: help, module <module_id>.");
    }

    if (!in_array($argv[1], array('help', 'module'))) {
        throw new RuntimeException("Команда \"$argv[1]\" не поддерживается программой.");
    }

    if ($argv[1] === 'help') {
        echo <<<HELP

        Инструмент позволяет выполнять миграции модуля Битрикс.

        Использование:
            php migrate.php [OPTIONS]...

        Команды:
            help                  Выводит справку по этой программе и завершает ее выполнение.
            module <module_id>    Выполняет миграции указанного модуля.

        Примеры:
            /usr/bin/php migrate.php help
            /usr/bin/php migrate.php module b24.academy


        HELP;
        return;
    }

    if (empty($argv[2])) {
        throw new RuntimeException('Не указан идентификатор модуля.');
    }

    $moduleId = $argv[2];

    $modulePath = __DIR__ . '/modules/' . $moduleId;
    if (!is_dir($modulePath)) {
        throw new RuntimeException("Модуль \"$moduleId\" не найден.");
    }

    $migrationsPath = $modulePath . '/install/migrations';
    if (!is_dir($migrationsPath)) {
        throw new RuntimeException('Директория с миграциями не найдена.');
    }

    require_once dirname(__DIR__) . '/bitrix/modules/main/cli/bootstrap.php';
    while (ob_end_flush()) ;

    $moduleVersion = (int) Option::get($moduleId, 'VERSION') ?: 0;
    $migrations = glob($migrationsPath . '/*.php');

    sort($migrations, SORT_NATURAL);

    $db = Application::getConnection();
    $em = EventManager::getInstance();
    $docRoot = Application::getDocumentRoot();
    $modRoot = $modulePath;

    foreach ($migrations as $migration) {
        if (!preg_match('/(?<v>[0-9_]*)\.php/', $migration, $m)) {
            continue;
        }

        $fileVersion = (int) str_replace('_', '', $m['v']);

        if ($fileVersion <= $moduleVersion) {
            continue;
        }

        fwrite(STDERR, '[' . date('d.m.Y H:i:s') . '] ' . "Начата миграция \"{$m['v']}\"" . PHP_EOL);

        require_once $migration;

        fwrite(STDERR, '[' . date('d.m.Y H:i:s') . '] ' . "Завершена миграция \"{$m['v']}\"" . PHP_EOL);
    }
} catch (Exception $exception) {
    fwrite(STDERR, "При выполнении программы возникла ошибка:\n$exception\n");
}
