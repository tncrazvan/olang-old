<?php

use function OLang\parse as ast;

use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase {
    private const SOURCE = <<<OLANG
        struct user {
            username: string = string#0
            email: string    = string#1
            phone: string    = string#2

            is_admin => bool {
                // logic goes here
            }
        }

        validate => bool {
            email: string = string#3
            phone: string = string#4

            // validation logic
            
        }

        validate(email: string#5, phone: string#6)
        OLANG;

    public function testAst() {
        $ast = ast(self::SOURCE);

        // declaration, struct user
        $this->assertEquals('structDeclaration', $ast[0]['meta'] ?? '');
        $this->assertEquals('name', $ast[0]['data']['name']['meta'] ?? '');
        $this->assertEquals('', $ast[0]['data']['name']['data']['prefix'] ?? '');
        $this->assertEquals('user', $ast[0]['data']['name']['data']['name'] ?? '');

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
        $this->assertEquals('oneLineComment', $ast[0]['data']['block'][3]['data']['block'][0]['meta'] ?? '');
        $this->assertEquals(' logic goes here', $ast[0]['data']['block'][3]['data']['block'][0]['data'] ?? '');
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

        $this->assertEquals('oneLineComment', $ast[1]['data']['block'][2]['meta'] ?? '');
        $this->assertEquals(' validation logic', $ast[1]['data']['block'][2]['data'] ?? '');

        $this->assertEquals(null, $ast[1]['data']['expression'] ?? '');

        // call, callable validate
        $this->assertEquals('callableCall', $ast[2]['meta'] ?? '');
        $this->assertEquals('validate', $ast[2]['data']['name'] ?? '');
        $this->assertEquals('email', $ast[2]['data']['arguments'][0]['key'] ?? '');
        $this->assertEquals('string#5', $ast[2]['data']['arguments'][0]['value'][0] ?? '');
        $this->assertEquals('phone', $ast[2]['data']['arguments'][1]['key'] ?? '');
        $this->assertEquals('string#6', $ast[2]['data']['arguments'][1]['value'][0] ?? '');
    }
    
    public function testConvertToPHP() {
        $ast = ast(self::SOURCE);
    }
}