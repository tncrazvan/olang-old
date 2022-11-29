<?php

use function CatPaw\Q\ast;
use function CatPaw\Q\get;
use function CatPaw\Q\set;
use function CatPaw\Q\source;
use function CatPaw\Q\token as token;

use PHPUnit\Framework\TestCase;

class TestSuiteParser extends TestCase {
    /** @return void  */
    public function testStringVariable() {
        source(<<<Q
            let var1 = 'lorem';
            Q);
        token("let") 
        ?? token("=", fn ($m) => set("var-name", trim($m->previous))) 
        ?? token(";", fn ($m) => set("var-value", trim($m->previous)));

        $this->assertEquals("var1", get("var-name"));
        $this->assertEquals("'lorem'", get("var-value"));

        source(<<<Q
            let var2 = "ipsum";
            Q);

        token("let") 
        ?? token("=", fn ($m) => set("var2-name", trim($m->previous)))
        ?? token(";", fn ($m) => set("var2-value", trim($m->previous)));

        $this->assertEquals("var2", get("var2-name"));
        $this->assertEquals("\"ipsum\"", get("var2-value"));

        source(<<<Q
            let var3: string = "test";
            Q);
        token("let")
        ?? token(":", fn ($m) => set("var3-name", trim($m->previous)))
        ?? token("=", fn ($m) => set("var3-type", trim($m->previous)))
        ?? token(";", fn ($m) => set("var3-value", trim($m->previous)));

        $this->assertEquals("var3", get("var3-name"));
        $this->assertEquals("string", get("var3-type"));
        $this->assertEquals("\"test\"", get("var3-value"));
    }

    public function testIfExpression() {
        source(<<<Q
            if 1 < 3 { }
            Q);

        token("if")
        ?? (
            token("==|<|>|>=|<=", function($m) {
                set("left", trim($m->previous));
                set("token", trim($m->token));
            }) 
            ?? token("{", fn ($m) => set("right", trim($m->previous)))
        )?->else(
            token("(", fn ($m) => set("function-name", trim($m->previous)))
        );

        $this->assertEquals("1", get("left"));
        $this->assertEquals("<", get("token"));
        $this->assertEquals("3", get("right"));

        source(<<<Q
            if active {}
            Q);
        
        token("if")
        ?? token("{", fn ($m) => set("var-name", trim($m->previous)));

        $this->assertEquals("active", get("var-name"));

        source("if active and admin {}");

        token("if")
        ?? token("and|or", fn ($m) => set("left", trim($m->previous)) && set("operation", trim($m->token)))
        ?? token("{", fn ($m) => set("right", trim($m->previous)));

        $this->assertEquals("active", get("left"));
        $this->assertEquals("and", get("operation"));
        $this->assertEquals("admin", get("right"));
    }

    public function testSignedInt() {
        source("-1;");
        token("-|+", fn ($m) => set("sign", trim($m->token)));
        token(";", fn ($m) => set("value", trim($m->previous)));
        $this->assertEquals("-", get("sign"));
        $this->assertEquals("1", get("value"));
    }

    public function testingDecodingConst() {
        ast(true);
        \Syntax\decode(<<<OLANG
            const asd = "";
            OLANG);
        $ast = ast();

        $output = '';
        foreach ($ast as $node) {
            $output .= ($node->toPHP)();
        }
        $this->assertEquals('const asd = "";', $output);
    }

    public function testingDecodingLet() {
        ast(true);
        \Syntax\decode(<<<OLANG
            let asd = "";
            OLANG);
        $ast = ast();

        $output = '';
        foreach ($ast as $node) {
            $output .= ($node->toPHP)();
        }
        $this->assertEquals('$asd = "";', $output);
    }

    public function testingDecodingFunction() {
        ast(true);
        \Syntax\decode(<<<OLANG
            function test(){

            }
            OLANG);
        $ast = ast();

        $output = '';
        foreach ($ast as $node) {
            $output .= ($node->toPHP)();
        }
        $this->assertEquals('function test(){}', $output);
    }

    // public function testingDecodingFunctionWithLetParams() {
    //     ast(true);
    //     \Syntax\decode(<<<OLANG
    //         function test(
    //             let var1 = '';
    //             let var2 = '';
    //         ){

    //         }
    //         OLANG);
    //     $ast = ast();

    //     $output = '';
    //     foreach ($ast as $node) {
    //         $output .= ($node->toPHP)();
    //     }
    //     $this->assertEquals('function test(){}', $output);
    // }
}