<?php

use function OLang\target;
use function OLang\transpile;

use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase {
    public function testCompiler() {
        target("php");
        $php = transpile(<<<OLANG
            struct user {


            }

            const createUser = ::{
                required<string> username
                required<string> email
                optional<string> phone
            }

            createUser(
                username: "loopcake",
                email: "tangent.jotey@gmail.com",
                phone: "3343517612"
            )

            OLANG);

        echo $php;
    }
}