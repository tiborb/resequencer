<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;


class Comparator
{
    
}


class Sequence{
    private $seq = array();
    
    public function push($elem){
        array_push($this->seq, $elem);
    }
    
    public function flush($start = 0, $end = 0){
        
    }
    
    private function findGap(){
        
    }
    
    private function getTillGap(){
        
    }
}



class Resequencer{
    
    private $channel;
    private $connection;
    private $callback;
    
    private $sequence = array();
    
    public function __construct() {
        $this->connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('logs', 'fanout', false, false, false);

        $this->callback = function($msg){
            $this->sequence[] = $msg->body;
            echo ' [x] ', $msg->body, "\n";
            $this->dump();
        };
    }

    public function init()
    {
        list($this->queue_name, ,) = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->queue_bind($this->queue_name, 'logs');
        echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";
    }
    
    public function consume()
    {
        $this->channel->basic_consume($this->queue_name, '', false, true, false, false, $this->callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
    
    public function __destruct() {
        echo 'Bye!' , "\n";
        $this->channel->close();
        $this->connection->close();
    }
    
    public function dump()
    {
        var_dump($this->sequence);
    }
}

$r = new Resequencer();
$r->init();
$r->consume();