<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tiborb;

use tiborb\ComparatorInterface;

/**
 * Description of Sequence
 *
 * @author tiborb
 */
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
