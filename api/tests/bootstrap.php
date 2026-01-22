<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Ensure test database is set up
passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:create --env=test --if-not-exists 2>/dev/null',
    __DIR__
));

passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:update --env=test --force --complete 2>/dev/null',
    __DIR__
));
