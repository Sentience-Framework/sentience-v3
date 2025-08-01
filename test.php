<?php

interface TestInterface
{
    public static function createInstance(): static;
}

abstract class Test implements TestInterface
{
    public static $instance = null;

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = static::createInstance();
        }

        return self::$instance;
    }
}

class Test2 extends Test
{
    public string $foo = "bar";

    public static function createInstance(): static
    {
        return new static();
    }

    public function test2(): string
    {
        return $this->foo;
    }
}



$instance = Test2::getInstance();

echo $instance->test2();

$instance = Test2::getInstance();

echo $instance->test2();

$instance = Test2::getInstance();

echo $instance->test2();
