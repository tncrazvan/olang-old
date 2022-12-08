<?php

use function OLang\parse;

use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase {
    public function testCompiler() {
        $ast = parse(<<<OLANG
            struct user {
                required<string> username
                required<string> email
                optional<string> phone

            }

            const createUser = ::{
                required<string> username
                required<string> email
                optional<string> phone
            }

            createUser(username: "loopcake", email: "tangent.jotey@gmail.com", phone: "3343517612")

            OLANG);

        echo $ast;
    }
}