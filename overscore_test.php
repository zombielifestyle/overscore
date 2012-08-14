<?php

require 'overscore.php';

class InvokeMe {
    public $invoked = false;
    function invoke($arg = true) {
        $this->invoked = $arg;
    }
}

class overscore_test extends PHPUnit_Framework_TestCase  {

    function requiresPhp54() {
        if (PHP_VERSION_ID <= 50400) {
            $this->markTestSkipped();
        }
    }

    function testBind() {
        $this->requiresPhp54();

        $f = function() {
            return $this->fro;
        };
        $o = (object) array('fro' => 'zzle');
        $f2 = _bind($f, $o);
        $this->assertEquals('zzle', $f2());
    }

    function testCall() {
        $value = _call(function($hello, $world) {
            return "$hello $world";
        }, null, 'hello', 'world');
        $this->assertEquals("hello world", $value);
    }

    function testCallWithContext() {
        $this->requiresPhp54();

        $o = (object) array('world' => 'world');
        $value = _call(function($hello, $world) {
            return "$hello $this->world";
        }, $o, 'hello', 'world');
        $this->assertEquals("hello world", $value);
    }

    function testApply() {
        $value = _apply(function($world) {
            return "hello $world";
        }, null, array('world'));
        $this->assertEquals("hello world", $value);
    }

    function testApplyWithContext() {
        $this->requiresPhp54();

        $o = (object) array('world' => 'world');
        $value = _apply(function() {
            return "hello $this->world";
        }, $o, array('world'));
        $this->assertEquals("hello world", $value);
    }

    function testMemoize() {
        $calls = 0;
        $f = function () use(&$calls) {
            $calls++;
            $args = func_get_args();
            return implode(',', $args);
        };
        $memo = _memoize($f);
        $this->assertEquals("1,2", $memo(1,2));
        $this->assertEquals("1,2", $memo(1,2));
        $this->assertEquals("1,2,3", $memo(1,2,3));
        $this->assertEquals(2, $calls);
    }

    function testOnce() {
        $f = function() {
            static $calls = 0;
            return ++$calls;
        };
        $f2 = _once($f);
        $f2();
        $this->assertEquals(1, $f2());
    }

    function testAfter() {
        $f = function() {
            return 'finally called!';
        };
        $f2 = _after(2, $f);
        $this->assertNull($f2());
        $this->assertEquals('finally called!', $f2());
    }

    function testWrap() {
        $f = function() {
            return 'world';
        };
        $f2 = _wrap($f, function($f) {
            return 'hello '.$f().'!';
        });
        $this->assertEquals('hello world!', $f2());
    }

    function testCompose() {
        $greet = function($who) {
            return "hello $who";
        };
        $smart = function($greet) {
            return "$greet, you smartass!";
        };
        $f = _compose($greet, $smart);
        $this->assertEquals('hello harry, you smartass!', $f('harry'));
    }

    function testKeys() {
        $o = array('first' => 'me', 'second' => 'you');
        $this->assertEquals(array('first','second'), _keys($o));
        $o = (object)$o;
        $this->assertEquals(array('first','second'), _keys($o));
    }

    function testValues() {
        $o = array('first' => 'me', 'second' => 'you');
        $this->assertEquals(array('me','you'), _values($o));
        $o = (object)$o;
        $this->assertEquals(array('me','you'), _values($o));
    }

    function testFunctions() {
        $this->assertEquals(array('close','rewind', 'read'), _functions('Directory'));
        $this->assertEquals(array('close','rewind', 'read'), _functions(dir(__DIR__)));
    }

    function testExtends() {
        $a1 = array('a' => 1, 'b' => 'b');
        $a2 = array('a' => 2);
        $a3 = array('a' => 3, 'c' => 'c');
        $a = _extends($a1, $a2, $a3);
        $expected = array('a' => 3, 'b' => 'b', 'c' => 'c');
        $this->assertEquals($expected, $a);
    }

    function testPick() {
        $a = array('a' => 'a', 'b' => 'b', 'c' => 'c');
        $expected = array('a' => 'a', 'c' => 'c');
        $this->assertEquals($expected, _pick($a, 'a', 'c'));
    }

    function testDefaults() {
        $a = array('b' => 'b');
        $defaults = array('a' => 'a', 'c' => 'c');
        $expected = array('a' => 'a', 'b' => 'b', 'c' => 'c');
        $result = _defaults($a, $defaults);
        $this->assertEquals($expected, $result);
        $result['a'] = 'd';
        $expected['a'] = 'd';
        $this->assertEquals($expected, _defaults($result, $defaults));
    }

    function testHas() {
        $a = array('a' => 'a');
        $this->assertTrue(_has($a, 'a'));
        $this->assertFalse(_has($a, 'b'));
        $a = (object)$a;
        $this->assertTrue(_has($a, 'a'));
        $this->assertFalse(_has($a, 'b'));
    }

    function testEmpty() {
        $this->assertTrue(_empty(array()));
        $this->assertTrue(_empty(0));
        $this->assertFalse(_empty(array(1)));
    }

    function testIdentity() {
        $this->assertEquals('a', _identity('a'));
        $this->assertNotEquals('a', _identity('b'));
    }

    function testTimes() {
        $calls = 0;
        $f = function () use(&$calls) {
            ++$calls;
        };
        _times(3, $f);
        $this->assertEquals(3, $calls);
    }

    function testUniqueId() {
        $this->assertEquals('foo_1', _uniqueId('foo_'));
        $this->assertEquals('foo_2', _uniqueId('foo_'));
        $this->assertEquals(1, _uniqueId());
        $this->assertEquals(2, _uniqueId());
    }

    function testEscape() {
        $this->assertEquals('&lt;foo', _escape('<foo'));
    }

    function testResult() {
        $a = array('a' => 'a', 'b' => function() {return 'b';});
        $this->assertEquals('a', _result($a, 'a'));
        $this->assertEquals('b', _result($a, 'b'));
    }

    function testEachWithData() {
        $result = array();
        $f = function($value, $index) use(&$result) {
            $result[] = "$value-$index";
        };
        _each(array(1,2), $f);
        $this->assertEquals(array("1-0", "2-1"), $result);
    }

    function testEachWithNoData() {
        $result = array();
        $f = function($value, $index) use(&$result) {
            $result[] = "$value-$index";
        };
        _each(array(), $f);
        $this->assertEquals(array(), $result);
    }

    function testEachWithContext() {
        $this->requiresPhp54();

        $o = (object) array('result' => array());
        _each(array(1,2), function($value, $index) {
            $this->result[] = "$value-$index";
        }, $o);
        $this->assertEquals(array("1-0", "2-1"), $o->result);
    }

    function testMap() {
        $result = _map(array('a', 'b'), function($a) {
            return strtoupper($a);
        });
        $this->assertEquals(array('A','B'), $result);
    }

    function testMapWithNoData() {
        $result = _map(array(), function($a) {
            return strtoupper($a);
        });
        $this->assertEquals(array(), $result);
    }

    function testMapWithContext() {
        $this->requiresPhp54();

        $o = (object) array('prefix' => '-');
        $result = _map(array('a', 'b'), function($a) {
            return $this->prefix.$a;
        }, $o);
        $this->assertEquals(array('-a','-b'), $result);
    }

    function testReduce() {
        $result = _reduce(array(1,2,3), function($memo, $value) {
            return "$memo$value";
        }, 0);
        $this->assertEquals('0123', $result);
    }

    function testReduceWithNoData() {
        $result = _reduce(array(), function($memo, $value) {
            return "$memo$value";
        }, 0);
        $this->assertEquals(0, $result);
    }

    function testReduceWithContext() {
        $this->requiresPhp54();

        $o = (object) array('sep' => '-');
        $result = _reduce(array(1,2,3), function($memo, $value) {
            return "$memo$this->sep$value";
        }, 0, $o);
        $this->assertEquals('0-1-2-3', $result);
    }

    function testReduceRight() {
        $result = _reduceRight(array(1,2,3), function($memo, $value) {
            return "$memo$value";
        }, 0);
        $this->assertEquals('0321', $result);
    }

    function testReduceRightWithNoData() {
        $result = _reduceRight(array(), function($memo, $value) {
            return "$memo$value";
        }, 0);
        $this->assertEquals(0, $result);
    }

    function testReduceRightWithContext() {
        $this->requiresPhp54();

        $o = (object) array('sep' => '-');
        $result = _reduceRight(array(1,2,3), function($memo, $value) {
            return "$memo$this->sep$value";
        }, 0, $o);
        $this->assertEquals('0-3-2-1', $result);
    }

    function testFind() {
        $result = _find(array(1,2,3), function($value) {
            return $value == 2;
        });
        $this->assertEquals(2, $result);
    }

    function testFindWithNoData() {
        $result = _find(array(), function($value) {
            return $value == 2;
        });
        $this->assertEquals(null, $result);
    }

    function testFindWithContext() {
        $this->requiresPhp54();

        $o = (object) array('cmp' => 2);
        $result = _find(array(1,2,3), function($value) {
            return $value == $this->cmp;
        }, $o);
        $this->assertEquals(2, $result);
    }

    function testFilter() {
        $result = _filter(array(1,'a',2), function($value) {
            return is_int($value);
        });
        $this->assertEquals(array(1,2), $result);
    }

    function testFilterWithNoData() {
        $result = _filter(array(), function($value) {
            return is_int($value);
        });
        $this->assertEquals(array(), $result);
    }

    function testFilterWithContext() {
        $this->requiresPhp54();

        $o = (object) array('cmp' => 1);
        $result = _filter(array(1,'a',2), function($value) {
            return is_int($value) && $value > $this->cmp;
        }, $o);
        $this->assertEquals(array(2), $result);
    }

    function testReject() {
        $result = _reject(array(1,'a',2), function($value) {
            return is_int($value);
        });
        $this->assertEquals(array('a'), $result);
    }

    function testRejectWithNoData() {
        $result = _reject(array(), function($value) {
            return is_int($value);
        });
        $this->assertEquals(array(), $result);
    }

    function testRejectWithContext() {
        $this->requiresPhp54();

        $o = (object) array('cmp' => 1);
        $result = _reject(array(1,'a',2), function($value) {
            return is_int($value) && $value > $this->cmp;
        }, $o);
        $this->assertEquals(array(1,'a'), $result);
    }

    function testAll() {
        $this->assertFalse(_all(array(1,0)));
        $this->assertFalse(_all(array(true,'')));
        $this->assertTrue(_all(array(true,1,'a',array(1))));
        $this->assertTrue(_all(array()));
        $this->assertTrue(_all(array(5,6), function($v) {return $v > 4;}));
    }

    function testAny() {
        $this->assertFalse(_any(array()));
        $this->assertFalse(_any(array('',0,false,null)));
        $this->assertTrue(_any(array(0,true)));
        $this->assertTrue(_any(array(5,6), function($v) {return $v > 4;}));
    }

    function testContains() {
        $this->assertTrue(_contains(array('baz','foo'), 'foo'));
        $this->assertTrue(_contains(array('baz', 'voo' => 'foo'), 'foo'));
        $this->assertFalse(_contains(array('baz','foop'), 'foo'));
    }

    function testInvoke() {
        $list = array(new InvokeMe(), new InvokeMe());
        _invoke($list, 'invoke');
        $this->assertEquals(true, $list[0]->invoked);
        $this->assertEquals(true, $list[1]->invoked);
    }

    function testPluck() {
        $a = array(array('a' => 'a1'), array('a' => 'a2'));
        $this->assertEquals(array('a1','a2'), _pluck($a, 'a'));
    }

    function testMax() {
        $list = array(64, 12, 19, 101);
        $this->assertEquals(101, _max($list));
        $this->assertEquals(303, _max($list, function($value) {return $value*3;}));
    }

    function testMin() {
        $list = array(64, 12, 19, 101);
        $this->assertEquals(12, _min($list));
        $this->assertEquals(36, _min($list, function($value) {return $value*3;}));
    }

    function testSortBy() {
        $this->markTestIncomplete();
    }

    function testCountBy() {
        $this->markTestIncomplete();
    }

    function testGroupBy() {
        $this->markTestIncomplete();
    }

    function tesSortedIndex() {
        $this->markTestIncomplete();
    }

    function testAppend() {
        $list = array(1,2,3);
        $this->assertEquals(array(1,2,3), _concat($list));
        $this->assertEquals(array(1,2,3,12), _concat($list, 12));
        $this->assertEquals(array(12,1,2,3), _concat(12, $list));
        $this->assertEquals(array(null,1,2,3), _concat(null, $list));
        $this->assertEquals(array(1,2,3,12,16), _concat($list, array(12,16)));
        $this->assertEquals(array(1,2,3,12,16), _concat($list, 12, 16));
        // var_dump(_concat(null, array('foo')));
    }

    function _testPrepend() {
        $list = array(1,2,3);
        $this->assertEquals(array(1,2,3), _prepend($list));
        $this->assertEquals(array(12,1,2,3), _prepend($list, 12));
        $this->assertEquals(array(array(12),1,2,3), _prepend($list, array(12)));
        $this->assertEquals(array(12,16,1,2,3), _prepend($list, 12, 16));
    }

    function testFirst() {
        $this->assertEquals(null, _first(array()));
        $this->assertEquals('oh', _first(array('oh','hi')));
        $this->assertEquals(array('oh', 'hi'), _first(array('oh','hi', 'bro'), 2));
        $this->assertEquals(array('oh', 'hi', 'bro'), _first(array('oh','hi', 'bro'), 4));
    }

    function testInitial() {
        $this->assertEquals(array(), _initial(array()));
        $this->assertEquals(array('oh','hi'), _initial(array('oh','hi','dude')));
        $this->assertEquals(array('oh'), _initial(array('oh','hi','dude'), 2));
        $this->assertEquals(array(), _initial(array('oh','hi','dude'), 3));
    }

    function testLast() {
        $this->assertEquals(null, _last(array()));
        $this->assertEquals('hi', _last(array('oh','hi')));
        $this->assertEquals(array('hi', 'bro'), _last(array('oh','hi', 'bro'), 2));
        $this->assertEquals(array('oh', 'hi', 'bro'), _last(array('oh','hi', 'bro'), 4));
    }

    function testRest() {
        $this->assertEquals(array(), _rest(array()));
        $this->assertEquals(array('hi','dude'), _rest(array('oh','hi','dude')));
        $this->assertEquals(array('dude'), _rest(array('oh','hi','dude'), 2));
        $this->assertEquals(array(), _rest(array('oh','hi','dude'), 3));
    }

    function testCompact() {
        $list = array('',false,0,1);
        $this->assertEquals(array(1), _compact($list));
        $this->assertEquals(array(), _compact(array()));
    }

    function testFlatten() {
        $list = array(1, array(array(2),3),4);
        $this->assertEquals(array(1,2,3,4), _flatten($list));
        $this->assertEquals(array(), _flatten(array()));
    }

    function testWithout() {
        $list = array(1,2,1,3);
        $this->assertEquals(array(2,3), _without($list, 1));
        $this->assertEquals(array(3), _without($list, 1, 2));
        $this->assertEquals(array(), _without(array(), 1, 2));
    }

    function testWith() {
        $this->assertEquals(array(1), _with(array(), 1));
        $this->assertEquals(array(1,2,3), _with(array(1,2,3), 1));
        $this->assertEquals(array(2,3,1), _with(array(2,3), 1));
    }

    function testUnion() {
        $this->assertEquals(array(), _union(array()));
        $this->assertEquals(array(1,2,3,100,12),_union(array(1,2,3),array(1,100,12,2),array(1,2)));
    }

    function testIntersection() {
        $this->assertEquals(array(), _intersection(array(),array()));
        $this->assertEquals(array(1,2),_intersection(array(1,2,3),array(1,100,12,2),array(1,2)));
    }

    function testUniq() {
        $this->assertEquals(array(), _uniq(array()));
        $this->assertEquals(array(1, 2 => 2), _uniq(array(1,1,2,2)));
    }

    function testZip() {
        $name = array('drax', 'hulk', 'galactus');
        $alignment = array('chaotic neutral', 'chaotic good', 'lawful evil');
        $favoriteDish = array('cheesecake', 'pancake', 'fish stick');
        $expected = array(
            array('drax', 'chaotic neutral', 'cheesecake'),
            array('hulk', 'chaotic good', 'pancake'),
            array('galactus', 'lawful evil', 'fish stick'),
        );
        $this->assertEquals($expected, _zip($name, $alignment, $favoriteDish));
        $this->assertEquals(array(), _zip(array(),array()));
    }

    function testIndexOf() {
        $this->assertEquals(-1, _indexOf(array(), 2));
        $this->assertEquals(2, _indexOf(array(0,1,2,1), 2));
        $this->assertEquals(-1, _indexOf(array(0,1,2,1), 3));
        $this->assertEquals(1, _indexOf(array(0,1,2,1), 1));
        $this->assertEquals('a', _indexOf(array('a' => 1, 'b' => 1), 1));
    }

    function testLastIndexOf() {
        $this->assertEquals(-1, _indexOf(array(), 2));
        $this->assertEquals(2, _lastIndexOf(array(0,1,2,1), 2));
        $this->assertEquals(-1, _lastIndexOf(array(0,1,2,1), 3));
        $this->assertEquals(3, _lastIndexOf(array(0,1,2,1), 1));
        $this->assertEquals('b', _lastIndexOf(array('a' => 1, 'b' => 1), 1));
    }

}

