<?php

namespace tiborb;

use tiborb\Sequence;
use PhpAmqpLib\Connection\AMQPConnection;

/**
 * Description of Resequencer
 *
 * @author tiborb 
 */
class Resequencer{
    
    private $channel;
    private $connection;
    private $callback;
    private $sequence;
    private $allMessages = 0;
    private $orderdMessages = 0;
    private $limit = 20000;
    private $in = array();
    private $out = array();
    
    public function __construct() {
        $this->connection = new AMQPConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASS, RABBIT_VHOST);
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('logs', 'fanout', false, false, false);
        
        $this->sequence = new Sequence(array(), new Comparator());
        
        // for every message
        $this->callback = function($msg){
            $value = $msg->body;
            if (is_numeric($value)){
                #$this->in[] = $value;
                #$this->in = array_unique($this->in);
                $this->allMessages++;
                $this->sequence->push((int)$value);
            } else {
                echo "NAN\n";
            }
            $vals = $this->sequence->getOrderedSequence();            
            
            #$this->out = array_unique(array_merge($this->out, $vals));
            
            echo "-------------\n";
            $buffered = $this->sequence->getBuffer();
            echo "buffer  [" . implode(',', $buffered) . "] x" . count($buffered). "\n";
            echo "ordered [" . implode(',', $vals). "] x" . count($vals). "\n";
            
            $this->orderdMessages += count($vals);
            
            echo "{$this->orderdMessages}/" . count($this->allMessages) . "\n";
            if ($value % $this->limit == 0){
                #echo "limit reached: " . count($this->allMessages) . "\n";
                #var_dump(array_diff($this->in, $this->out));
                #die;
            }
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
        $this->channel->close();
        $this->connection->close();
    }
}
