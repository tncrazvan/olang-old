<?php

namespace Olang\Internal {
    function consume(string $pattern, int $groups, string &$source):null|string|array {
        if (preg_match($pattern, $source = trim($source), $matches) && isset($matches[$groups])) {
            $source = trim(preg_replace($pattern, '', $source, 1));
            if ($c = count($matches) === 2) {
                return $matches[1] ?? '';
            }
            if (1 === $c) {
                return [];
            }
            return array_slice($matches, 1);
        }
        return null;
    }

    function expression(string &$source, callable $found) {
        $items = [];

        while (
            (null !== ($value = stringId($source, fn ($value) => $value)))
            || (null !== ($value = integerValue($source, fn ($value) => $value)))
            || (null !== ($value = floatValue($source, fn ($value) => $value)))
            || (null !== ($value = addition($source, fn () => 'addition')))
            || (null !== ($value = subtraction($source, fn () => 'subtraction')))
            || (null !== ($value = booleanValue($source, fn ($value) => $value)))
            || (null !== ($value = valueEqualityCheck($source, fn () => 'valueEqualityCheck')))
            || (null !== ($value = pointerEqualityCheck($source, fn () => 'pointerEqualityCheck')))

        ) {
            $items[] = $found($value);
        }        

        return $items;
    }

    function parameter(string &$source, callable $found) {
        if (!$name = consume('/^\s*([A-z]+[A-z0-9]*)/', 1, $source)) {
            return null;
        }

        if (!$type = consume('/^\s*:\s*([\w\W]*)=/U', 1, $source)) {
            return null;
        }

        if ($default = expression($source, fn ($default) => $default)) {
            return $found($name, $type, $default);
        }
        
        return null;
    }
    
    function addition(string &$source, callable $found) {
        if (null === consume('/^\s*\+/', 0, $source)) {
            return null;
        }

        return $found(true);
    }

    function subtraction(string &$source, callable $found) {
        if (!consume('/^\s*\-/', 0, $source)) {
            return null;
        }

        return $found(true);
    }

    function valueEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*==/', 0, $source)) {
            return null;
        }

        return $found(true);
    }

    function pointerEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*===/', 0, $source)) {
            return null;
        }

        return $found(true);
    }

    function name(string &$source, callable $found) {
        if (!$name = consume('/^\s*(:{2})?([A-z]+[A-z0-9]*)/', 1, $source)) {
            return null;
        }

        return $found(...$name);
    }

    function block(string &$source) {
        $l       = strlen($source);
        $opened  = 0;
        $closed  = 0;
        $content = '';
        for ($i = 0; $i < $l; $i++) {
            $character = $source[$i];
            if ('{' === $character) {
                $opened++;
            }

            if ('}' === $character) {
                $closed++;
            }
            
            if ($opened > 0) {
                $content .= $character;
            }

            if (0 !== $opened && 0 !== $closed && $opened === $closed) {
                $content = substr($content, 1, strlen($content) - 2);
                $source  = substr($source, $i + 1);
                return $content;
            }
        }
        $source = substr($source, 0, $i + 1);
        return false;
    }
    
    function structDeclaration(string &$source, callable $found) {
        if (!$name = consume('/^\s*struct\s+([A-z][A-z0-9]*)/', 1, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found($name, $block);
    }

    function structCallableDeclaration(string &$source, callable $found) {
        if (!$name = consume('/^\s*::([A-z0-9]+)\s*=>\s*([A-z][A-z0-9]*)\s*/', 2, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found(...[...$name, $block]);
    }

    function callableDeclaration(string &$source, callable $found) {
        if (!$declaration = consume('/^\s*(const|let)\s+([\w\W]*)\s*=>\s*([A-z][A-z0-9]*)\s*/', 3, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found(...[...$declaration, $block]);
    }

    function callableCall(string &$source, callable $found) {
        if (!$call = consume('/^\s*([A-z0-9][A-z0-9_]*)\(([\w\W]*)\)/', 2, $source)) {
            return null;
        }

        return $found(...$call);
    }

    function callableArguments(string &$source, callable $found) {
        $items = [];
        while ($argument = consume('/^\s*([A-z][A-z0-9_]+):([\w\W]+)(,|$)/U', 1, $source)) {
            if (null !== ($value = expression($argument[1], fn ($value) => $value))) {
                $items[] = $found($argument[0], $value);
            }
        }

        return $items;
    }

    function oneLineComment(string &$source, callable $found) {
        if (!$comment = consume('/^\s*\\/\\/([\w\W]*)$/Um', 1, $source)) {
            return null;
        }

        return $found($comment);
    }

    function integerValue(string &$source, callable $found) {
        if (null === ($integer = consume('/^\s*([0-9]+)/', 1, $source))) {
            return null;
        }

        return $found($integer);
    }

    function floatValue(string &$source, callable $found) {
        if (null === ($float = consume('/^\s*([0-9]+\.[0.9]+)/', 1, $source))) {
            return null;
        }

        return $found($float);
    }

    function booleanValue(string &$source, callable $found) {
        if (null === ($boolean = consume('/^\s*(true|false)/', 1, $source))) {
            return null;
        }

        return $found($boolean);
    }

    function stringId(string &$source, callable $found) {
        if (null === ($stringID = consume('/^\s*(string#[0-9]+)/', 1, $source))) {
            return null;
        }

        return $found($stringID);
    }
}


namespace OLang {
    use Error;

    function parse(
        string $source,
    ) {
        $source       = $source;
        $instructions = [];
        while ($source) {
            $copy = "$source";
            // ######### struct callable
            if ($structCallableDeclaration = Internal\structCallableDeclaration($source, fn ($name, $return, $block) => [
                "name"   => trim($name),
                "return" => trim($return),
                "block"  => parse($block),
            ])) {
                $instructions[] = [
                    "meta" => "structCallableDeclaration",
                    "data" => $structCallableDeclaration,
                ];
                continue;
            }


            // ######### callable
            if ($callableDeclaration = Internal\callableDeclaration($source, fn ($mutability, $name, $return, $block) => [
                'mutability' => match ($mutability) {
                    'const' => 'constant',
                    'let'   => 'variable',
                    default => 'constant',
                },
                'name'   => Internal\name($name, fn ($prefix, $name) => $name),
                'return' => trim($return),
                'block'  => parse($block),

            ])) {
                $instructions[] = [
                    'meta' => 'callableDeclaration',
                    'data' => $callableDeclaration,
                ];
                continue;
            }

            // ######### call
            if ($callableCall = Internal\callableCall($source, fn ($name, $arguments) => [
                "name"      => $name,
                "arguments" => Internal\callableArguments($arguments, fn ($key, $value) => [
                    "key"   => trim($key),
                    "value" => $value,
                ]),
            ])) {
                $instructions[] = [
                    'meta' => 'callableCall',
                    'data' => $callableCall,
                ];
                continue;
            }

            // ######### struct
            if ($structDeclaration = Internal\structDeclaration($source, fn ($name, $block) => [
                'name'  => Internal\name($name, fn ($prefix, $name) => $name),
                'block' => parse($block),
            ])) {
                $instructions[] = [
                    'meta' => 'structDeclaration',
                    'data' => $structDeclaration,
                ];
                continue;
            }

            // ######### parameter
            if ($parameter = Internal\parameter($source, fn ($name, $type, $default) => [
                "availability" => "required",
                "type"         => trim($type),
                "name"         => trim($name),
                "default"      => $default,
            ])) {
                $instructions[] = [
                    'meta' => 'parameter',
                    'data' => $parameter,
                ];
                continue;
            }

            // ######### valueEqualityCheck
            if ($comment = Internal\valueEqualityCheck($source, fn () => true)) {
                $instructions[] = [
                    "meta" => "operation",
                    "data" => "valueEqualityCheck"
                ];
                continue;
            }

            // ######### pointerEqualityCheck
            if ($comment = Internal\pointerEqualityCheck($source, fn () => true)) {
                $instructions[] = [
                    "meta" => "operation",
                    "data" => "pointerEqualityCheck"
                ];
                continue;
            }

            // ######### stringId
            if ($stringId = Internal\stringId($source, fn ($id) => [
                "id" => $id
            ])) {
                $instructions[] = [
                    "meta" => "stringId",
                    "data" => trim($stringId),
                ];
                continue;
            }

            // ######### name
            if ($name = Internal\name($source, fn ($prefix, $name) => [
                "prefix" => trim($prefix),
                "name"   => trim($name),
            ])) {
                $instructions[] = [
                    "meta" => "name",
                    "data" => trim($name),
                ];
                continue;
            }

            // ######### one line comment
            if ($comment = Internal\oneLineComment($source, fn ($comment) => [
                "content" => $comment,
            ])) {
                $instructions[] = [
                    'meta' => 'oneLineComment',
                    'data' => $comment,
                ];
                continue;
            }

            if ($copy === $source) {
                throw new Error("Invalid syntax.");
            }
        }

        return $instructions;
    }
}