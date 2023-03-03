<?php

use function OLang\comments;
use function OLang\Compiler\php;
use function OLang\parse;
use function OLang\strings;

use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase {
    public function testStrings() {
        $source = <<<OLANG
            test => void {
                print("hello\" world")
            }

            test()
            OLANG;

        [$source, $strings]  = strings($source);
        [$source, $comments] = comments($source);
        $ast                 = parse($source);
        
        $output = php($ast, $strings);

        $this->assertNotEmpty($output);

        $this->assertEquals(<<<PHP
            function test():void{
                print("hello\" world");
            }
            test();
            PHP, $output);
    }
}