<?php

use function CatPaw\Q\get;
use function CatPaw\Q\index;
use function CatPaw\Q\set;
use function CatPaw\Q\source;
use function CatPaw\Q\token;

use PHPUnit\Framework\TestCase;

class TestSuiteParser extends TestCase {
    /** @return void  */
    public function testStringVariable() {
        source(<<<Q
            let var1 = 'lorem';
            Q);
        token("let", function($m) {
            token("assignment", function($m) {
                set("var-name", trim($m->previous));
                token("execute", function($m) {
                    set("var-value", trim($m->previous));
                });
            });
        });

        $this->assertEquals("var1", get("var-name"));
        $this->assertEquals("'lorem'", get("var-value"));

        source(<<<Q
            let var2 = "ipsum";
            Q);

        token("let", function($m) {
            token("assignment", function($m) {
                set("var2-name", trim($m->previous));
                token("execute", function($m) {
                    set("var2-value", trim($m->previous));
                });
            });
        });

        $this->assertEquals("var2", get("var2-name"));
        $this->assertEquals("\"ipsum\"", get("var2-value"));

        source(<<<Q
            let var3: string = "test";
            Q);
        token("let", function($m) {
            token("typeIndicator", function($m) {
                set("var3-name", trim($m->previous));
                token("assignment", function($m) {
                    set("var3-type", trim($m->previous));
                    token("execute", function($m) {
                        set("var3-value", trim($m->previous));
                    });
                });
            });
        });

        $this->assertEquals("var3", get("var3-name"));
        $this->assertEquals("string", get("var3-type"));
        $this->assertEquals("\"test\"", get("var3-value"));
    }

    public function testIfExpression() {
        source(<<<Q
            if 1 < 3 { }
            Q);
        token("if", function($m) {
            $i = index();
            if (!token("==|<|>|>=|<=", function($m) {
                set("left", trim($m->previous));
                set("token", trim($m->token));
                token("{", function($m) {
                    set("right", trim($m->previous));
                });
            })) {
                index($i);
                token("(", function($m) {
                    set("function-name", trim($m->previous));
                });
            }
        });

        $this->assertEquals("1", get("left"));
        $this->assertEquals("<", get("token"));
        $this->assertEquals("3", get("right"));
    }
}