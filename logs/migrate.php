<?php

require dirname(__DIR__) . '/app/core/Database.php';
require dirname(__DIR__) . '/app/core/Migration.php';

Migration::run();

echo "Migration completed\n";
