<?php

use function OLang\comments;
use function OLang\parse as ast;
use function OLang\strings;

use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase {
    public function testStrings() {
        [$source, $strings] = strings(<<<OLANG
            "hello world"
            OLANG);

        $this->assertNotEmpty($strings);
        $this->assertEquals("hello world", $strings[0] ?? '');
        $this->assertEquals("string#0", $source);

        [$source, $strings] = strings(<<<OLANG
            "hello \ \\\\\"world\""
            OLANG);

        $this->assertNotEmpty($strings);
        $this->assertEquals("hello \ \\\"world\"", $strings[0] ?? '');
        $this->assertEquals("string#0", $source);
    }

    public function testComments() {
        [$source, $comments] = comments(<<<OLANG
            struct user {
                username: string = string#0
                email: string    = string#1
                phone: string    = string#2
            }   // test test

            // test test
            // test test
            OLANG);
        $this->assertNotEmpty($comments);
    }
    
    

    public function testAST() {
        $source = <<<OLANG
            struct user {
                username: string = "test"
                email: string    = "asd"
                phone: string    = "awerqw"
                is_admin => bool {
                    // logic goes here
                }
            }

            validate => bool {
                email: string = "my@email.com"
                phone: string = "111111"

                // validation logic
                
            }

            validate(email: "some@email.com", phone: "22222")

            if 1 > 2 {
                // some comment
                return 1
            }

            if 2 > 1 {
                // then
            } else {
                // else
            }

            if 2 > 1 {
                // then
            } else {
                // else
                if 3 === 3 {
                }
            }

            if 2 > 1 {
                // then
            } else if 3 === 3 {
                // else if
            }

            // due to how the parser works, this is also allowed
            if 2 > 1 {
                // then
            } else => if 3 === 3 {
                // else if
            }

            // a few more interesting examples

            if 1 > 2 => validate(email: "some.other@email.com", phone: "33333333")
            else if 1 > 2 => false

            // this looks weird, don't think many would use it

            if 1 > 2 => validate(email: "some.other.other@email.com", phone: "44444444")
            else => if 1 > 2 => false
            OLANG;

        [$source, $strings]  = strings($source);
        [$source, $comments] = comments($source);

        $ast = ast($source);

        // declaration, struct user
        $this->assertEquals('structDeclaration', $ast[0]['meta'] ?? '');
        $this->assertEquals('user', $ast[0]['data']['name'] ?? '');

        $this->assertEquals('parameter', $ast[0]['data']['block'][0]['meta'] ?? '');
        $this->assertEquals('required', $ast[0]['data']['block'][0]['data']['availability'] ?? '');
        $this->assertEquals('string', $ast[0]['data']['block'][0]['data']['type'] ?? '');
        $this->assertEquals('username', $ast[0]['data']['block'][0]['data']['name'] ?? '');
        $this->assertEquals('string#0', $ast[0]['data']['block'][0]['data']['default'][0] ?? '');

        $this->assertEquals('parameter', $ast[0]['data']['block'][1]['meta'] ?? '');
        $this->assertEquals('required', $ast[0]['data']['block'][1]['data']['availability'] ?? '');
        $this->assertEquals('string', $ast[0]['data']['block'][1]['data']['type'] ?? '');
        $this->assertEquals('email', $ast[0]['data']['block'][1]['data']['name'] ?? '');
        $this->assertEquals('string#1', $ast[0]['data']['block'][1]['data']['default'][0] ?? '');

        $this->assertEquals('parameter', $ast[0]['data']['block'][2]['meta'] ?? '');
        $this->assertEquals('required', $ast[0]['data']['block'][2]['data']['availability'] ?? '');
        $this->assertEquals('string', $ast[0]['data']['block'][2]['data']['type'] ?? '');
        $this->assertEquals('phone', $ast[0]['data']['block'][2]['data']['name'] ?? '');
        $this->assertEquals('string#2', $ast[0]['data']['block'][2]['data']['default'][0] ?? '');

        $this->assertEquals('callableDeclaration', $ast[0]['data']['block'][3]['meta'] ?? '');
        $this->assertEquals('is_admin', $ast[0]['data']['block'][3]['data']['name'] ?? '');
        $this->assertEquals('bool', $ast[0]['data']['block'][3]['data']['returnType'] ?? '');
        $this->assertEquals(null, $ast[0]['data']['block'][3]['data']['expression'] ?? '');

        // declaration, callable validate
        $this->assertEquals('callableDeclaration', $ast[1]['meta'] ?? '');
        $this->assertEquals('validate', $ast[1]['data']['name'] ?? '');
        $this->assertEquals('bool', $ast[1]['data']['returnType'] ?? '');

        $this->assertEquals('parameter', $ast[1]['data']['block'][0]['meta'] ?? '');
        $this->assertEquals('required', $ast[1]['data']['block'][0]['data']['availability'] ?? '');
        $this->assertEquals('string', $ast[1]['data']['block'][0]['data']['type'] ?? '');
        $this->assertEquals('email', $ast[1]['data']['block'][0]['data']['name'] ?? '');
        $this->assertEquals('string#3', $ast[1]['data']['block'][0]['data']['default'][0] ?? '');

        $this->assertEquals('parameter', $ast[1]['data']['block'][1]['meta'] ?? '');
        $this->assertEquals('required', $ast[1]['data']['block'][1]['data']['availability'] ?? '');
        $this->assertEquals('string', $ast[1]['data']['block'][1]['data']['type'] ?? '');
        $this->assertEquals('phone', $ast[1]['data']['block'][1]['data']['name'] ?? '');
        $this->assertEquals('string#4', $ast[1]['data']['block'][1]['data']['default'][0] ?? '');

        $this->assertEquals(null, $ast[1]['data']['expression'] ?? '');

        // call, callable validate
        $this->assertEquals('callableCall', $ast[2]['meta'] ?? '');
        $this->assertEquals('validate', $ast[2]['data']['name'] ?? '');
        $this->assertEquals('email', $ast[2]['data']['arguments'][0]['key'] ?? '');
        $this->assertEquals('string#5', $ast[2]['data']['arguments'][0]['value']['data'][0] ?? '');
        $this->assertEquals('phone', $ast[2]['data']['arguments'][1]['key'] ?? '');
        $this->assertEquals('string#6', $ast[2]['data']['arguments'][1]['value']['data'][0] ?? '');
    }


    public function testErrors() {
        $error0  = '';
        $error1  = '';
        $error2  = '';
        $error3  = '';
        $error4  = '';
        $error5  = '';
        $error6  = '';
        $error7  = '';
        $error8  = '';
        $error9  = '';
        $error10 = '';
        $error11 = '';
        $error12 = '';
        $error13 = '';
        $error14 = '';
        $error15 = '';
        $error16 = '';
        $error17 = '';
        $error18 = '';
        $error19 = '';
        $error20 = '';
        $error21 = '';

        try {
            ast(<<<OLANG
                struct user {
                    username: string = 
                }
                OLANG);
        } catch(Error $e) {
            $error0 = (string)$e;
        }

        try {
            ast(<<<OLANG
                struct user {
                    username: = string#0
                }
                OLANG);
        } catch(Error $e) {
            $error1 = (string)$e;
        }

        try {
            ast(<<<OLANG
                struct user 
                    
                }
                OLANG);
        } catch(Error $e) {
            $error2 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                struct user {
                OLANG);
        } catch(Error $e) {
            $error3 = (string)$e;
        }

        try {
            ast("test: = 1");
            ast("test = 1");
        } catch(Error $e) {
            $error4 = (string)$e;
        }

        try {
            ast("test:string = ");
        } catch(Error $e) {
            $error5 = (string)$e;
        }

        try {
            ast("name = ");
        } catch(Error $e) {
            $error6 = (string)$e;
        }

        try {
            ast(<<<OLANG
                name:bool = test() +
                OLANG);
        } catch(Error $e) {
            $error7 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() -
                OLANG);
        } catch(Error $e) {
            $error8 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() and
                OLANG);
        } catch(Error $e) {
            $error9 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() or
                OLANG);
        } catch(Error $e) {
            $error10 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() ==
                OLANG);
        } catch(Error $e) {
            $error11 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() ===
                OLANG);
        } catch(Error $e) {
            $error12 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() !=
                OLANG);
        } catch(Error $e) {
            $error13 = (string)$e;
        }
        
        try {
            ast(<<<OLANG
                name:bool = test() !==
                OLANG);
        } catch(Error $e) {
            $error14 = (string)$e;
        }

        $this->assertStringContainsString('Invalid syntax, expecting a valid expression.', $error0);
        $this->assertStringContainsString('Invalid syntax, expecting a type when declaring a parameter.', $error1);
        $this->assertStringContainsString('Invalid syntax, expecting "{" before block declaration.', $error2);
        $this->assertStringContainsString('Invalid syntax, could not detect end of block declaration.', $error3);
        $this->assertStringContainsString('Invalid syntax, expecting a type when declaring a parameter.', $error4);
        $this->assertStringContainsString('Invalid syntax, expecting a valid expression.', $error5);
        $this->assertStringContainsString('Invalid syntax, expecting a valid expression.', $error6);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (+).', $error7);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (-).', $error8);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (and).', $error9);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (or).', $error10);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (==).', $error11);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (===).', $error12);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (!=).', $error13);
        $this->assertStringContainsString('Invalid syntax, expression for parameter "name" must not end with an operation (!==).', $error14);
    }
}