<?php

require_once __DIR__. '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
global $channel;
global $cnt;
$channel = $connection->channel();
$channel->exchange_declare('logs', 'fanout', false, false, false);

function publish($val){
    global $channel;
    $channel->basic_publish(new AMQPMessage($val), 'logs');
    echo " [x] Sent ", $val, "\n";
}

$controlMsg = "FLUSH";
$elems = range(1, 10000);
$chunks = array_chunk($elems, rand(5, 70));

$cnt = 0;
foreach ($chunks as $chunk) {
    $cnt++;
    if (rand(1, 3) % 3 == 0) {
        shuffle($chunk);
    }
    foreach ($chunk as $key => $val) {
        publish($val);
    }
}

$channel->close();
$connection->close();
