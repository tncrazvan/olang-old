<?php

use function OLang\parse as ast;

use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase {
    public function testAst() {
        try {
            $ast = ast(<<<OLANG
                struct user {
                    username: string = string#0
                    email: string    = string#1
                    phone: string    = string#2

                    is_active: bool => 0

                    is_admin: bool => {
                        // logic goes here
                    }
                }

                validate: bool => {
                    email: string = string#3
                    phone: string = string#4

                    // validation logic
                    
                }

                validate: bool => email:string = string#5, phone:string = string#6 | email != string#7 and phone != string#8

                validate(email: string#5, phone: string#6)
                OLANG);
            echo $ast;
        } catch(Error $e) {
            echo (string)$e;
        }
    }
}