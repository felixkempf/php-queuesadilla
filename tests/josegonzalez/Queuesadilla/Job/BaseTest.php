<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Job\Base;
use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\NullLogger;

class BaseTest extends TestCase
{
    public function setUp()
    {
        $config = [];
        $items = [
            [
                'delay' => 0,
                'class' => 'Foo',
                'vars' => [
                    'foo' => 'bar',
                    'baz' => 'qux',
                ],
            ],
            [
                'attempts' => 0,
                'delay' => 0,
                'class' => 'Foo',
                'vars' => [
                    'foo' => 'bar',
                    'baz' => 'qux',
                ],
            ],
            [
                'attempts' => 1,
                'delay' => 0,
                'class' => 'Foo',
                'vars' => [
                    'foo' => 'bar',
                    'baz' => 'qux',
                ],
            ],
        ];

        $this->Logger = new NullLogger;
        $this->Engine = new NullEngine($this->Logger, $config);
        $this->Jobs = [
            new Base($items[0], $this->Engine),
            new Base($items[1], $this->Engine),
            new Base($items[2], $this->Engine),
        ];
    }

    public function tearDown()
    {
        unset($this->Engine);
        unset($this->Jobs);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::__construct
     */
    public function testConstruct()
    {
        $data = [
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ];

        $job = new Base($data, $this->Engine);
        $this->assertEquals($data, $job->item());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::attempts
     */
    public function testAttempts()
    {
        $this->assertEquals(0, $this->Jobs[0]->attempts());
        $this->assertEquals(0, $this->Jobs[1]->attempts());
        $this->assertEquals(1, $this->Jobs[2]->attempts());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::data
     */
    public function testData()
    {
        $this->assertNull($this->Jobs[0]->data('unset_variable'));
        $this->assertTrue($this->Jobs[0]->data('unset_variable', true));
        $this->assertEquals('bar', $this->Jobs[0]->data('foo'));
        $this->assertEquals('qux', $this->Jobs[0]->data('baz'));
        $this->assertEquals([
                'foo' => 'bar',
                'baz' => 'qux',
        ], $this->Jobs[0]->data());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::item
     */
    public function testItem()
    {
        $this->assertEquals([
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[0]->item());

        $this->assertEquals([
            'attempts' => 0,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[1]->item());

        $this->assertEquals([
            'attempts' => 1,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[2]->item());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::delete
     */
    public function testDelete()
    {
        $this->Engine->return = true;
        $this->assertTrue($this->Jobs[0]->delete());

        $this->Engine->return = false;
        $this->assertFalse($this->Jobs[0]->delete());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Job\Base::release
     */
    public function testRelease()
    {
        $this->Engine->return = true;
        $this->assertTrue($this->Jobs[0]->release(10));
        $this->assertEquals([
            'attempts' => 1,
            'delay' => 10,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[0]->item());

        $this->Engine->return = false;
        $this->assertFalse($this->Jobs[1]->release());
        $this->assertEquals([
            'attempts' => 1,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[1]->item());

        $this->assertFalse($this->Jobs[2]->release());
        $this->assertEquals([
            'attempts' => 2,
            'delay' => 0,
            'class' => 'Foo',
            'vars' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $this->Jobs[2]->item());
    }
}