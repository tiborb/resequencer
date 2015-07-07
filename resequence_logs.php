<?php

require_once __DIR__. '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;

interface ComparatorInterface{
    public function getComparator();
    public function next($a);
    public function previous($a);
    public function isNext($a, $b);
}

class Comparator implements ComparatorInterface {

    public function getComparator()
    {
        return function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        };
    }
    
    public function next($a)
    {
        return ++$a;
    }
    
    public function previous($a)
    {
        return --$a;
    }
    
    public function isNext($a, $b)
    {   
        return ($b == $this->next($a));
    }
}

class Sequence{

    /**
     * @var ComparatorInterface
     */
    private $comparator;
    
    private $duplicates = false;

    private $buffer;

    public function __construct($array, ComparatorInterface $comparator)
    {
        $this->clearBuffer();
        $this->comparator = $comparator;
    }
    
    public function push($value)
    {   
        if (false === $this->duplicates && in_array($value, $this->buffer)){
            return;
        }
        $this->buffer[] = $value;
    }
    
    public function sort()
    {
        usort($this->buffer, $this->comparator->getComparator());
    }
    
    public function count()
    {
        return count($this->buffer);
    }
    
    public function getBuffer()
    {
        return $this->buffer;
    }
    
    public function clearBuffer()
    {
        $this->buffer = array();
    }
    
    public function firstKey()
    {
        reset($this->buffer);
        return key($this->buffer);
    }
    
    public function endKey()
    {
        end($this->buffer);
        return key($this->buffer);
    }
    
    public function getOrderedSequence()
    {   
        $ordered = array();
        $this->sort();
        reset($this->buffer);
        
        while (($value = current($this->buffer)) !== false)
        {
            $key = key($this->buffer);
            $expectedNext = $this->comparator->next($value);
            #printf("%s -> %s, expected next %s\n", $key, $value, $expectedNext);
            $next = next($this->buffer);
            if ($next !== FALSE){
                if (true === $this->comparator->isNext($value, $next)){
                    // there is a gap in the sequence at key position
                    $ordered[] = array_shift($this->buffer);
                }else{
                    echo "bad sequence or end\n";
                    return $ordered;
                }
            }
        }
        return $ordered;
    }
}

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

$r = new Resequencer();
$r->init();
$r->consume();