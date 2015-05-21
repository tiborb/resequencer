<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest', '/');
$channel = $connection->channel();
$channel->exchange_declare('logs', 'fanout', false, false, false);

$elems = range(1, 10000);
$chunks = array_chunk($elems, rand(5, 50));
foreach ($chunks as $chunk) {
    if (rand(1, 3) % 3 == 0) {
        shuffle($chunk);
    }
    foreach ($chunk as $key => $val) {
        $channel->basic_publish(new AMQPMessage($val), 'logs');
        echo " [x] Sent ", $val, "\n";
    }
}

$channel->close();
$connection->close();
