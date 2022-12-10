<?php

use function OLang\parse as ast;

use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase {
    public function testAst() {
        $ast = ast(<<<OLANG
            struct user {
                username: string
                email: string
                phone: string

                ::is_active => bool {
                    // check if user is active
                }
            }

            const validate => bool {
                email: string
                phone: string

                // validation logic
            }

            validate(
                email: "asd.asd@asd.com",
                phone: "123123123"
            )

            OLANG);
        echo $ast;
    }
}