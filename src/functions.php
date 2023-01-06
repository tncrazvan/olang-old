<?php

namespace Olang\Internal {
    use Error;

    /**
     * @param int $x will be ignored if lesser than 0
     * @param int $y will be ignored if lesser than 0
     * @return int{0:int,1:int} the current position x and y.
     */
    function position(int $x = -1, int $y = -1) {
        static $stateX = 0;
        static $stateY = 0;

        if ($x > 0) {
            $stateX = $x;
        }

        if ($y > 0) {
            $stateY = $y;
        }

        return [$stateX, $stateY];
    }

    function consume(
        string $pattern,
        null|int $groups,
        string &$source,
    ):null|string|array {
        if (preg_match($pattern, $source = $source, $matches) && (null === $groups || isset($matches[$groups]))) {
            $source = preg_replace($pattern, '', $source, 1);
            if (null !== $groups) {
                if (2 === ($c = count($matches))) {
                    return $matches[1] ?? '';
                }
                if (1 === $c) {
                    return null;
                }
            }
            return array_slice($matches, 1);
        }
        return null;
    }

    function expression(
        string &$source,
        callable $found,
        bool $throw = false,
        string $throwSnippet = '',
    ) {
        $copy      = "$source";
        $localCopy = "$source";
        $items     = [];

        $previousIsUsable = false;
        while (
            (null !== ($value = stringId($source, fn ($value) => $value)))
            || (null !== ($value = integerValue($source, fn ($value) => $value)))
            || (null !== ($value = floatValue($source, fn ($value) => $value)))
            || (null !== ($value = addition($source, fn () => 'addition')))
            || (null !== ($value = subtraction($source, fn () => 'subtraction')))
            || (null !== ($value = booleanValue($source, fn ($value) => $value)))
            || (null !== ($value = andOperation($source, fn () => 'andOperation')))
            || (null !== ($value = orOperation($source, fn () => 'orOperation')))
            || (null !== ($value = valueEqualityCheck($source, fn () => 'valueEqualityCheck')))
            || (null !== ($value = pointerEqualityCheck($source, fn () => 'pointerEqualityCheck')))
            || (null !== ($value = valueNotEqualityCheck($source, fn () => 'valueNotEqualityCheck')))
            || (null !== ($value = pointerNotEqualityCheck($source, fn () => 'pointerNotEqualityCheck')))
            
            || (null !== ($value = usableName($source, fn ($prefix, $name) => [
                "meta" => "usableName",
                "data" => [
                    "prefix" => $prefix,
                    "name"   => $name,
                ]
            ])))

        ) {
            $currentIsUsable = 'usableName' === ($value['meta'] ?? '') 
            || (is_string($value) && str_starts_with($value, "string#"));

            if ($previousIsUsable && $currentIsUsable) {
                $source = "$localCopy";
                break;
            }

            $items[] = $found($value);

            $previousIsUsable = 'usableName' === ($value['meta'] ?? '') 
            || (is_string($value) && str_starts_with($value, "string#"));

            $localCopy = "$source";
        }        

        if (!$items) {
            $source = $copy;
            if ($throw) {
                $throwSnippet = $throwSnippet?$throwSnippet:$copy;
                throw new Error("Invalid syntax, expecting a valid expression.\n$throwSnippet");
            }
        }

        return $items;
    }

    function parameterDeclaration(
        string &$source,
        callable $found
    ) {
        $copy = "$source";
        if (!$parameter = consume('/^\s*([A-z][A-z0-9_]+)?\s*:\s*([A-z][A-z0-9_]+)?\s*(=)?/', null, $source)) {
            return null;
        }

        if (!($parameter[0] ?? '')) {
            throw new Error("Invalid syntax, expecting a name when declaring a parameter.\n$copy");
        }
        if (!($parameter[1] ?? '')) {
            throw new Error("Invalid syntax, expecting a type when declaring a parameter.\n$copy");
        }
        if (!($parameter[2] ?? '')) {
            throw new Error("Parameter \"$parameter[0]\" must define a default value.\n$copy");
        }

        $default = expression($source, fn ($default) => $default, true, "Invalid syntax, expecting a valid expression for parameter \"$parameter[0]\".\n$copy");

        consume('/^\s*(,)/', 1, $source);
        return $found($parameter[0], $parameter[1], $default);
    }
    
    function andOperation(string &$source, callable $found) {
        if (!consume('/^\s*(and)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function orOperation(string &$source, callable $found) {
        if (!consume('/^\s*(or)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function addition(string &$source, callable $found) {
        if (!consume('/^\s*(\+)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function subtraction(string &$source, callable $found) {
        if (!consume('/^\s*(-)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function valueNotEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*(!=)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function pointerNotEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*(!==)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function valueEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*(==)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function pointerEqualityCheck(string &$source, callable $found) {
        if (!consume('/^\s*(===)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function usableName(string &$source, callable $found) {
        $copy = "$source";
        if (!$usableName = consume('/^\s*(:{2})?([A-z][A-z0-9_]+)(:)?/', 2, $source)) {
            return null;
        }

        if (isset($usableName[2])) {
            $source = $copy;
            return null;
        }

        return $found(...$usableName);
    }

    function name(string &$source, callable $found) {
        if (!$name = consume('/^\s*(:{2})?([A-z][A-z0-9_]+)/', 2, $source)) {
            return null;
        }

        return $found(...$name);
    }

    function block(string &$source, bool $throw = false, string $throwSnippet = '') {
        $copy    = "$source";
        $l       = strlen($source);
        $opened  = 0;
        $closed  = 0;
        $content = '';
        if (!preg_match('/^\s*{+/', $source)) {
            if ($throw) {
                $throwSnippet = $throwSnippet?$throwSnippet:$copy;
                throw new Error("Invalid syntax, expecting \"{\" after structure name declaration.\n$throwSnippet");
            }
            return null;
        }
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
        $source = $copy;
        return null;
    }
    
    function structDeclaration(string &$source, callable $found) {
        $copy = "$source";
        if (!$name = consume('/^\s*struct\s+([A-z][A-z0-9_]+)/', 1, $source)) {
            if (consume('/^\s*(struct)/', 1, $source)) {
                throw new Error("Invalid syntax, expecting a name when declaring a structure.\n$copy");
            }
            return null;
        }

        $block = block(source: $source, throw: true, throwSnippet: $copy);

        return $found($name, $block);
    }

    function callableDeclaration(string &$source, callable $found) {
        $copy = "$source";
        if (!$callable = consume('/^\s*([A-z][A-z0-9_]+)?\s*=>\s*([A-z][A-z0-9_]+)?\s*/', null, $source)) {
            return null;
        }

        if (!($callable[0] ?? '')) {
            throw new Error("Invalid syntax, expecting a name when declaring a callable.\n$copy");
        }
        if (!($callable[1] ?? '')) {
            throw new Error("Invalid syntax, expecting a return type when declaring a callable.\n$copy");
        }

        if ($block = block($source)) {
            return $found(...[...$callable, $block, null]);
        }

        if ($expression = expression($source, fn ($value) => $value)) {
            return $found(...[...$callable, null, $expression]);
        }

        
        $source = $copy;
        throw new Error("Invalid syntax, expecting a block or expression when declaring callable \"$callable[0]\".\n$copy");
    }

    function callableCall(string &$source, callable $found) {
        if (!$call = consume('/^\s*([A-z0-9][A-z0-9_]+)\(([\w\W]*)\)/', 2, $source)) {
            return null;
        }

        return $found(...$call);
    }

    function callableArguments(string &$source, callable $found) {
        $items = [];
        while ($argument = consume('/^\s*([A-z][A-z0-9_]+):([A-z][A-z0-9_]+)(,|$)/U', 1, $source)) {
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
        $instructions = [];
        while (trim($source)) {
            $copy = "$source";
            // ######### callable declaration
            if ($callableDeclaration = Internal\callableDeclaration($source, fn (
                $name,
                $return,
                $block,
                $expression
            ) => [
                "meta" => "callableDeclaration",
                "data" => [
                    "name"       => trim($name),
                    "returnType" => trim($return),
                    "block"      => parse($block ?? ''),
                    "expression" => $expression,
                ]
            ])) {
                $instructions[] = $callableDeclaration;
                continue;
            }

            // ######### callable call
            if ($callableCall = Internal\callableCall($source, fn ($name, $arguments) => [
                "meta" => "callableCall",
                "data" => [
                    "name"      => $name,
                    "arguments" => Internal\callableArguments($arguments, fn ($key, $value) => [
                        "key"   => trim($key),
                        "value" => $value,
                    ]),
                ]
            ])) {
                $instructions[] = $callableCall;
                continue;
            }

            // ######### struct declaration
            if ($structDeclaration = Internal\structDeclaration($source, fn ($name, $block) => [
                'meta' => 'structDeclaration',
                'data' => [
                    'name' => Internal\name($name, fn ($prefix, $name) => [
                        "meta" => "name",
                        "data" => [
                            "prefix" => $prefix,
                            "name"   => $name,
                        ],
                    ]),
                    'block' => parse($block),
                ],
            ])) {
                $instructions[] = $structDeclaration;
                continue;
            }

            // ######### parameter declaration
            if ($parameter = Internal\parameterDeclaration($source, fn ($name, $type, $default) => [
                "meta" => "parameter",
                "data" => [
                    "availability" => "required",
                    "type"         => trim($type),
                    "name"         => trim($name),
                    "default"      => $default,
                ]
            ])) {
                $instructions[] = $parameter;
                continue;
            }

            // ######### value equality check
            if ($valueEqualityCheck = Internal\valueEqualityCheck($source, fn () => [
                "meta" => "operation",
                "data" => "valueEqualityCheck"
            ])) {
                $instructions[] = $valueEqualityCheck;
                continue;
            }

            // ######### pointer equality check
            if ($pointerEqualityCheck = Internal\pointerEqualityCheck($source, fn () => [
                "meta" => "operation",
                "data" => "pointerEqualityCheck"
            ])) {
                $instructions[] = $pointerEqualityCheck;
                continue;
            }

            // ######### string id
            if ($stringId = Internal\stringId($source, fn ($id) => [
                "meta" => "stringId",
                "data" => trim($id),
            ])) {
                $instructions[] = $stringId;
                continue;
            }

            // expression 
            if ($expression = Internal\expression($source, fn ($expression) => [
                "meta" => "expression",
                "data" => $expression,
            ])) {
                $instructions[] = $expression;
                continue;
            }

            // ######### name
            if ($name = Internal\name($source, fn ($prefix, $name) => [
                "meta" => "name",
                "data" => [
                    "prefix" => trim($prefix),
                    "name"   => trim($name),
                ]
            ])) {
                $instructions[] = $name;
                continue;
            }

            // ######### one line comment
            if ($comment = Internal\oneLineComment($source, fn ($comment) => [
                'meta' => 'oneLineComment',
                'data' => $comment,
            ])) {
                $instructions[] = $comment;
                continue;
            }

            if ($copy === $source && !$instructions) {
                throw new Error("Invalid syntax.");
            }
        }

        return $instructions;
    }
}