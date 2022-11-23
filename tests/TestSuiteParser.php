<?php

use function CatPaw\Q\parse;

use PHPUnit\Framework\TestCase;

class TestSuiteParser extends TestCase {
    /** @return void  */
    public function testSource() {
        parse(<<<Q
            let asd = ''
            Q);
    }
}