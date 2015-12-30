<?php

namespace tiborb;

/**
 *
 * @author tiborb
 */
interface ComparatorInterface{
    public function getComparator();
    public function next($a);
    public function previous($a);
    public function isNext($a, $b);
}
