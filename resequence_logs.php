<?php

namespace tiborb;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__. '/config.php';

use tiborb\Resequencer;

$r = new Resequencer();
$r->init();
$r->consume();