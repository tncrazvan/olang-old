<?php

namespace Syntax {
    use function CatPaw\Q\advance;
    use function CatPaw\Q\ast;
    use function CatPaw\Q\index;
    use CatPaw\Q\Node;
    use function CatPaw\Q\source;
    use function CatPaw\Q\token;

    /**
     * @param  string $source
     * @return bool
     */
    function decode($source) {
        source($source);

        index(0);
        if (
            null === (token("function", fn () => ast()[] = new Node(toPHP: fn () => 'function '))
            ?? token("(", function($m) {
                ast()[] = new Node(toPHP: fn () => trim($m->previous)."(");
                source(advance());
                $source = source();

                $arguments = fn ($then) => token("let|const", function($m) {
                    return ast()[] = new Node(toPHP: fn () => '$');
                })
                ?? token("=", function($m) {
                    return ast()[] = new Node(toPHP: fn () => trim($m->previous)." = ");
                })
                ?? token(";", function($m) {
                    return ast()[] = new Node(toPHP: fn () => trim($m->previous).",");
                }) ?? $then;

                if (str_starts_with($source, ")")) {
                    if (null !== token(")", fn ($m) => ast()[] = new Node(toPHP: fn () => trim($m->previous).")"))) {
                        die("Expecting \")\".\n");
                    } else {
                        source(advance());
                        $source = source();
                    }
                } else if ($arguments($arguments)) {
                    die("Invalid arguments for function $m->previous.\n");
                }
            })
            ?? token(")", fn () => ast()[] = new Node(toPHP: fn () => ")"))
            ?? token("{", function($m) {
                ast()[] = new Node(toPHP: fn () => "{");
                source(advance());
                $source = source();
                
                if (!decode($source)) {
                    die("Invalid function block.\n");
                }
            })
            ?? token("}", fn () => new Node(toPHP:fn () => "}")))
        ) {
            return true;
        }

        index(0);
        if (
            null === (token("let|const", function($m) {
                return ast()[] = new Node(toPHP: fn () => match ($m->token) {
                    'const' => 'const ',
                    'let'   => '$',
                    default => '$'
                });
            })
            ?? token("=", function($m) {
                return ast()[] = new Node(toPHP: fn () => trim($m->previous)." = ");
            })
            ?? token(";", function($m) {
                return ast()[] = new Node(toPHP: fn () => trim($m->previous).";");
            }))
        ) {
            return true;
        }

        index(0);

        if (null === (
            token("{", function() {
                ast()[] = new Node(toPHP: fn () => "{");
                source(advance());
                $source = source();

                if (str_starts_with($source, "}")) {
                    if (null !== token("}", fn ($m) => ast()[] = new Node(toPHP: fn () => trim($m->previous)."}"))) {
                        die("Expecting \"}\".\n");
                    } else {
                        source(advance());
                        $source = source();
                    }
                }

                if (!decode($source)) {
                    die("Invalid execution block.\n");
                }
            })
            ?? token("}", function() {
                return ast()[] = new Node(toPHP: fn () => "}");
            })
        )) {
            return true;
        }

        index(0);
        if (
            token("(", function($m) {
                ast()[] = new Node(toPHP: fn () => trim($m->previous)."(");
                source(advance());
                $source = source();
                if (!decode($source)) {
                    die("Invalid function invokation.\n");
                }
            })
            ?? token(")", fn () => new Node(toPHP: fn () => ")"))
            ?? token(";", fn () => new Node(toPHP: fn () => ";"))
            ?? true
        ) {
            return true;
        }

        return false;
    }
}