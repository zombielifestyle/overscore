<?php

function _bind($f, $context){
    if ($f instanceof Closure && method_exists($f, 'bindTo')) {
        return $f->bindTo($context);
    }
    return $f;
}

function _call($f, $context) {
    if (is_object($context)) {
        $f = _bind($f, $context);
    }
    $args = array_slice(func_get_args(), 2);
    return call_user_func_array($f, $args);
}

function _apply($f, $context, $args = array()){
    if (is_object($context)) {
        $f = _bind($f, $context);
    }  
    return call_user_func_array($f, $args);
}

function _memoize($f) {
    return function() use($f) {
        static $memo = array();
        $args = func_get_args();
        $key = md5(serialize($args));
        if (!isset($memo[$key])) {
            $memo[$key] = _apply($f, null, $args);
        }
        return $memo[$key];
    };
}

function _once($f) {
    return function() use($f) {
        static $called = false, $returnValue = null;
        if (!$called) {
            $called = true;
            $returnValue = $f();
        }
        return $returnValue;
    };
}

function _after($count, $f) {
    return function() use($count, $f) {
        static $calls = 0, $returnValue;
        if (++$calls == $count) {
            $returnValue = $f();
        }
        return $returnValue;
    };
}

function _wrap($f, $wrapper){
    return function() use($f, $wrapper) {
        return $wrapper($f);
    };
}

function _compose() {
    $functions = func_get_args();
    return function($value) use($functions) {
        foreach ($functions as $function) {
            $value = $function($value);
        }
        return $value;
    };
}

function _keys($o) {
    if (is_object($o)) {
        $o = get_object_vars($o);
    }
    return array_keys($o);
}

function _values($o) {
    if (is_object($o)) {
        $o = get_object_vars($o);
    }
    return array_values($o);
}

function _functions($o) {
    $class = null;
    if (is_object($o)) {
        $class = get_class($o);
    } else if (is_string($o) && class_exists($o)) {
        $class = $o;
    }
    if ($class) {
        return get_class_methods($class);
    }
}

function _extends(){
    $args = func_get_args();
    return _apply('array_merge', null, $args);
}

function _pick($haystack) {
    $needles = array_flip(array_slice(func_get_args(), 1));
    return array_intersect_key($haystack, $needles);
}

function _defaults($a, $defaults) {
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $a)) {
            $a[$key] = $value;
        }
    }
    return $a;
}

function _has($a, $key){
    if (is_array($a)) {
        return array_key_exists($key, $a);
    } 
    else if (is_object($a)) {
        return in_array($key, _keys($a)); 
    }
    return null;
}

function _empty($a) {
    return empty($a);
}

function _identity($identity) {
    return $identity;
}

function _times($count, $f) {
    while ($count-- > 0) {
        $f();
    }
}

function _uniqueId($prefix = null) {
    static $ids = array();
    if (!_has($ids, $prefix)) {
        $ids[$prefix] = 0;
    }
    return $prefix.++$ids[$prefix];
}

function _escape($str){
    return htmlentities($str);
}

function _result($a, $key) {
    $value = null;
    if (array_key_exists($key, $a)) {
        if (is_array($a)) {
            $value = $a[$key];
            if ($value instanceof Closure) {
                $value = $value();
            }
        }
    }
    return $value;
}

function _each($list, $iterator, $context = null) {
    foreach ($list as $key => $value) {
        _call($iterator, $context, $value, $key);
    }
}

function _map($list, $iterator, $context = null) {
    foreach ($list as &$value) {
        $value = _call($iterator, $context, $value);
    }
    return $list;
}

function _reduce($list, $iterator, $memo, $context = null) {
    while ($item = array_shift($list)) {
        $memo = _call($iterator, $context, $memo, $item);
    }
    return $memo;
}

function _reduceRight($list, $iterator, $memo, $context = null) {
    while ($item = array_pop($list)) {
        $memo = _call($iterator, $context, $memo, $item);
    }
    return $memo;
}

function _find($list, $iterator, $context = null) {
    foreach ($list as $value) {
        if (_call($iterator, $context, $value)) {
            return $value;
        }
    }
}

function _filter($list, $iterator, $context = null) {
    $results = array();
    foreach ($list as $value) {
        if (_call($iterator, $context, $value)) {
            $results[] = $value;
        }
    }
    return $results;
}

function _reject($list, $iterator, $context = null) {
    $results = array();
    foreach ($list as $value) {
        if (!_call($iterator, $context, $value)) {
            $results[] = $value;
        }
    }
    return $results;
}

function _all($list, $iterator = '_identity', $context = null) {
    foreach ($list as $value) {
        if (!_call($iterator, $context, $value)) {
            return false;
        }
    }
    return true;
}


function _any($list, $iterator = '_identity', $context = null) {
    foreach ($list as $value) {
        if (_call($iterator, $context, $value)) {
            return true;
        }
    }
    return false;
}

function _contains($list, $value){
    return in_array($value, $list);
}

function _invoke($list, $method) {
    foreach ($list as $item) {
        _call(array($item, $method), null);
    }
}

function _pluck($list, $key){
    $result = array();
    foreach ($list as $item) {
        if (_has($item, $key)) {
            $result[] = $item[$key];
        }
    }
    return $result;
}

function _max($list, $iterator = '_identity', $context = null){
    $max = 0;
    foreach ($list as $item) {
        $max = max(_call($iterator, $context, $item), $max);
    }
    return $max;
}

function _min($list, $iterator = '_identity', $context = null){
    $min = PHP_INT_MAX;
    foreach ($list as $item) {
        $min = min(_call($iterator, $context, $item), $min);
    }
    return $min;
}

function sortBy($list, $iterator, $context) {
}

function groupBy($list, $iterator, $context) {
}

function sortedIndex($list, $value, $iterator) {
}

function _concat() {
    $args = func_get_args();
    $list = array();
    foreach ($args as $arg) {
        if (is_array($arg)) {
            $list = array_merge($list, $arg);
        } else {
            $list[] = $arg;
        }
    }
    return $list;
}

function _first($list, $n = 1) {
    if ($n == 1) {
        return reset($list);
    }
    return array_slice($list, 0, $n);
}

function _initial($list, $n = 1) {
    return array_slice($list, 0, count($list)-$n);
}

function _last($list, $n = 1) {
    if ($n == 1) {
        return end($list);
    }
    return array_slice($list, -$n);
}

function _rest($list, $n = 1) {
    return array_slice($list, $n);
}

function _compact($list) {
    $result = array();
    foreach ($list as $value) {
        if (!empty($value)) {
            $result[] = $value;
        }
    }
    return $result;
}

function _flatten($list) {
    $result = array();
    array_walk_recursive($list, function($value) use (&$result) { 
        $result[] = $value; 
    });
    return $result;
}    

function _without($list) {
    $args = _rest(func_get_args());
    $result = array();
    foreach ($list as $value) {
        if (!in_array($value, $args, true)) {
            $result[] = $value;
        }
    }
    return $result;
}

function _with($list) {
    $args = _rest(func_get_args());
    foreach ($args as $value) {
        if (!in_array($value, $list, true)) {
            $list[] = $value;
        }
    }
    return $list;
}

function _union() {
    $args = func_get_args();
    $result = array();
    foreach ($args as $arg) {
        $result = array_unique(array_merge($result, $arg)); 
    }
    return $result;
}

function _intersection() {
    return array_values(_apply('array_intersect', null, func_get_args()));
}

function _uniq($list) {
    return array_unique($list);
}

function _zip() {
    return _apply('array_map', null, _concat(null, func_get_args()));
}

function _indexOf($list, $value) {
    $result = array_search($value, $list);
    return $result === false ? -1 : $result;
}

function _lastIndexOf($list, $value) {
    arsort($list);
    return _indexOf($list, $value);
}

function _beacon($label = 'BEACON', $e = null) {
    $e = $e ? $e : new Exception();
    $trace = $e->getTrace();
    echo $trace[0]['file'].':'.$trace[0]['line'].PHP_EOL. "^$label^".PHP_EOL;
}

function _abort($label = 'ABORT') {
    _beacon($label, new Exception());
    exit;
}

