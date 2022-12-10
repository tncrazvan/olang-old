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
                    narts: uint32    = 0


                    ::is_active => bool {
                        treshold: int = 0
                    }

                    ::is_admin => bool {
                        // logic goes here
                    }



                }

                const validate => bool {
                    email: string = string#3
                    phone: string = string#4

                    // validation logic
                    
                }

                validate(email: string#5, phone: string#6)

                // if user::is_active {
                    // logic goes here
                // }

                OLANG);
            echo $ast;
        } catch(Error $e) {
            echo (string)$e;
        }
    }
}