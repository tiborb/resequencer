<?php

namespace tiborb;

use tiborb\ComparatorInterface;

/**
 * Description of Comparator
 *
 * @author tiborb
 */
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
