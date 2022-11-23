<?php

use function CatPaw\Q\parse;
use function CatPaw\Q\state;

use PHPUnit\Framework\TestCase;

class TestSuiteParser extends TestCase {
    /** @return void  */
    public function testSource() {
        parse(<<<Q
            let var1 = 'lorem';
            Q);
        $this->assertEquals('var1', state()->get()['name']);
        $this->assertEquals("'lorem'", state()->get()['value']);
    }
}