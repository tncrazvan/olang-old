<?php

namespace Olang\Internal {
    use Error;


    /**
     * @param  int                $x will be ignored if lesser than 0
     * @param  int                $y will be ignored if lesser than 0
     * @return array{0:int,1:int} the current position x and y.
     */
    function position(int $x = -1, int $y = -1):array {
        static $stateX = 0;
        static $stateY = 0;

        if ($x > 0) {
            $stateX = $x;
        }

        if ($y > 0) {
            $stateY = $y;
        }

        /** @var array{0:int,1:int} */
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

    function operations() {
        static $operations = [
            'additionOperation',
            'subtractionOperation',
            'andOperation',
            'orOperation',
            'pointerEqualityCheck',
            'pointerNotEqualityCheck',
            'valueEqualityCheck',
            'valueNotEqualityCheck',
            'greaterThanCheck',
            'lesserThanCheck',
            'greaterThanOrEqualCheck',
            'lesserThanOrEqualCheck',
        ];
        return $operations;
    }

    function expression(
        string &$source,
        callable $found,
        bool $throw = false,
        string $throwSnippet = '',
    ) {
        $operations = operations();

        $copy                = "$source";
        $previousCopy        = "$source";
        $items               = [];
        $numberOfItems       = 0;
        $previousIsOperation = false;
        $previousIsUsable    = false;
        while (
            (null !== ($value = stringId($source, fn ($value) => $value)))

            || (null !== ($value = callableCall($source, fn ($name, $arguments) => [
                "meta" => "callableCall",
                "data" => [
                    "name"      => $name,
                    "arguments" => callableArguments($arguments, fn ($key, $value) => [
                        "key"   => trim($key),
                        "value" => $value,
                    ]),
                ]
            ])))

            || (null !== ($value = integerValue($source, fn ($value) => $value)))
            || (null !== ($value = floatValue($source, fn ($value) => $value)))
            || (null !== ($value = additionOperation($source, fn () => 'additionOperation')))
            || (null !== ($value = subtractionOperation($source, fn () => 'subtractionOperation')))
            || (null !== ($value = booleanValue($source, fn ($value) => $value)))
            || (null !== ($value = andOperation($source, fn () => 'andOperation')))
            || (null !== ($value = orOperation($source, fn () => 'orOperation')))
            || (null !== ($value = pointerEqualityCheck($source, fn () => 'pointerEqualityCheck')))
            || (null !== ($value = pointerNotEqualityCheck($source, fn () => 'pointerNotEqualityCheck')))
            || (null !== ($value = valueEqualityCheck($source, fn () => 'valueEqualityCheck')))
            || (null !== ($value = valueNotEqualityCheck($source, fn () => 'valueNotEqualityCheck')))
            || (null !== ($value = greaterThanCheck($source, fn () => 'greaterThanCheck')))
            || (null !== ($value = lesserThanCheck($source, fn () => 'lesserThanCheck')))
            || (null !== ($value = greaterThanOrEqualCheck($source, fn () => 'greaterThanOrEqualCheck')))
            || (null !== ($value = lesserThanOrEqualCheck($source, fn () => 'lesserThanOrEqualCheck')))
            || (null !== ($value = returnExpression($source, fn ($value) => [
                "meta" => "return",
                "data" => $value,
            ])))
            || (null !== ($value = ifExpression($source, fn ($check, $thenBlock, $thenExpression, $elseBlock, $elseExpression) => [
                "meta" => "if",
                "data" => [
                    "check"          => $check,
                    "thenBlock"      => \Olang\parse($thenBlock ?? ''),
                    "thenExpression" => $thenExpression,
                    "elseBlock"      => \Olang\parse($elseBlock ?? ''),
                    "elseExpression" => $elseExpression,
                ],
            ])))
            
            || (null !== ($value = usableName($source, fn ($prefix, $name) => $name)))

        ) {
            $currentIsUsable = 'usableName' === ($value['meta'] ?? '') 
            || (is_string($value) && str_starts_with($value, "string#"));

            if ($previousIsUsable && $currentIsUsable) {
                $source = "$previousCopy";
                break;
            }


            $previousIsUsable = 'usableName' === ($value['meta'] ?? '') 
            || (is_string($value) && str_starts_with($value, "string#"));

            $currentIsOperation = in_array($value, $operations);

            $isFirst = 0 === $numberOfItems;

            if ($isFirst && $currentIsOperation) {
                throw new Error("Invalid syntax, an expression must not start with an operation.");
            }

            if (!$isFirst && !$previousIsOperation && !$currentIsOperation) {
                $source = "$previousCopy";
                break;
            }

            $previousIsOperation = $currentIsOperation;
            $items[]             = $found($value);
            $numberOfItems++;
            $previousCopy = "$source";
        }

        if (!$items) {
            $source = $copy;
            if ($throw) {
                $throwSnippet = $throwSnippet?$throwSnippet:$copy;
                throw new Error("Invalid syntax, expecting a valid expression.\n$throwSnippet");
            }
        }

        return !$items?null:[
            "meta" => "expression",
            "data" => $items,
        ];
    }

    function parameter(
        string &$source,
        callable $found
    ) {
        $copy = "$source";
        if (!$parameter = consume('/^\s*((const|let|struct)?\s+)?([A-z][A-z0-9_]+)?\s*(:)?\s*([A-z][A-z0-9_]+)?\s*(=)?/', null, $source)) {
            return null;
        }

        if (in_array($parameter[2], [
            "if",
            "match",
            "else",
            "struct",
            "const",
            "let",
            "return",
        ])) {
            $source = $copy;
            return null;
        }

        
        if (!($parameter[2] ?? '')) {
            throw new Error("Invalid syntax, expecting a name when declaring a parameter.\n$copy");
        }

        if (!in_array(trim($parameter[1] ?? ''), [
            "",
            "const",
            "let",
            "static",
        ])) {
            throw new Error("Invalid syntax, prefix \"const\", \"let\" or \"static\" for parameter \"$parameter[2]\".\n$copy");
        }

        // if (!($parameter[3] ?? '') || !($parameter[4] ?? '')) {
        //     throw new Error("Invalid syntax, expecting a type when declaring a parameter.\n$copy");
        // }
        
        $operation = 'assignment';

        if ($parameter[3] ?? '') {
            if (!($parameter[4] ?? '')) {
                throw new Error("Invalid syntax, expecting a type when declaring a parameter.\n$copy");
            } else {
                $operation = 'initialization';
            }
        }
        
        if (!($parameter[5] ?? '')) {
            throw new Error("Parameter \"$parameter[2]\" must define a default value.\n$copy");
        }

        $value = expression($source, fn ($default) => $default, true, $copy)['data'];
        $count = count($value);

        $operations = operations();

        if (
            is_string($element = $value[$count - 1]) 
            // && !str_starts_with($op, "string#")
            && in_array($element, $operations)
        ) {
            $op = match ($element) {
                "additionOperation"       => "+",
                "subtractionOperation"    => "-",
                "andOperation"            => "and",
                "orOperation"             => "or",
                "valueEqualityCheck"      => "==",
                "pointerEqualityCheck"    => "===",
                "valueNotEqualityCheck"   => "!=",
                "pointerNotEqualityCheck" => "!==",
                default                   => $element,
            };
            throw new Error("Invalid syntax, expression for parameter \"$parameter[2]\" must not end with an operation ($op).\n$copy");
        }

        consume('/^\s*(,)/', 1, $source);
        return $found($operation, $parameter[1], $parameter[2], $parameter[4], $value);
    }
    
    function greaterThanCheck(string &$source, callable $found) {
        if (!consume('/^\s*(>)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function lesserThanCheck(string &$source, callable $found) {
        if (!consume('/^\s*(<)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }
    
    function greaterThanOrEqualCheck(string &$source, callable $found) {
        if (!consume('/^\s*(>=)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function lesserThanOrEqualCheck(string &$source, callable $found) {
        if (!consume('/^\s*(<=)/', 1, $source)) {
            return null;
        }

        return $found(true);
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

    function additionOperation(string &$source, callable $found) {
        if (!consume('/^\s*(\+)/', 1, $source)) {
            return null;
        }

        return $found(true);
    }

    function subtractionOperation(string &$source, callable $found) {
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
        $copy = "$source";
        if ($name = consume('/^\s*(:{2})?([A-z][A-z0-9_]+)/', 2, $source)) {
            return $found(...$name);
        }

        $source = $copy;
        return null;
    }

    function arguments(string &$source, bool $throw = false, string $throwSnippet = '') {
        $copy    = "$source";
        $l       = strlen($source);
        $opened  = 0;
        $closed  = 0;
        $content = '';
        if (!preg_match('/^\s*\(+/', $source)) {
            if ($throw) {
                $throwSnippet = $throwSnippet?$throwSnippet:$copy;
                throw new Error("Invalid syntax, expecting \"(\" before arguments declaration.\n$throwSnippet");
            }
            return null;
        }
        for ($i = 0; $i < $l; $i++) {
            $character = $source[$i];
            if ('(' === $character) {
                $opened++;
            }

            if (')' === $character) {
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
        throw new Error("Invalid syntax, could not detect end of arguments declaration.\n$throwSnippet");
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
                throw new Error("Invalid syntax, expecting \"{\" before block declaration.\n$throwSnippet");
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
        throw new Error("Invalid syntax, could not detect end of block declaration.\n$throwSnippet");
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

        return $found($name, \Olang\parse($block ?? ''));
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
            return $found(...[...$callable, \Olang\parse($block ?? ''), null]);
        }

        if ($expression = expression($source, fn ($value) => $value)) {
            return $found(...[...$callable, null, $expression]);
        }

        
        $source = $copy;
        throw new Error("Invalid syntax, expecting a block or expression when declaring callable \"$callable[0]\".\n$copy");
    }

    function callableCall(string &$source, callable $found) {
        $copy = "$source";

        if (!$name = name($source, fn ($prefix, $name) => $name )) {
            $source = $copy;
            return null;
        }

        if ((!$arguments = arguments($source)) && '' !== $arguments) {
            $source = $copy;
            return null;
        }
        
        return $found($name, $arguments);
    }

    function returnExpression(string &$source, callable $found) {
        if (!consume('/^\s*(return)/', 1, $source)) {
            return null;
        }

        if (!$expression = expression($source, fn ($value) => $value)) {
            return null;
        }

        return $found($expression);
    }

    function ifExpression(string &$source, callable $found) {
        $copy = "$source";

        if (!$if = consume('/^\s*(if)\s*/', 1, $source)) {
            return null;
        }

        if (!$check = expression($source, fn ($value) => $value)) {
            throw new Error("Invalid syntax, if expressions require a check expression.\n$copy");
        }

        $thenBlock      = null;
        $thenExpression = null;
        $elseBlock      = null;
        $elseExpression = null;

        if (!$thenBlock = block($source)) {
            if (!consume('/^\s*(=>)\s*/', 1, $source)) {
                throw new Error("Invalid syntax, if expressions must be followed by a block or an expression.\n$copy");
            } else {
                $thenExpression = expression($source, fn ($value) => $value);
            }
        }

        if ($else = consume('/^\s*(else)/', 1, $source)) {
            if (!$elseBlock = block($source)) {
                if (consume('/^\s*(=>)\s*/', 1, $source)) {
                    $elseExpression = expression($source, fn ($value) => $value);
                } else if ($if = consume('/^\s*(if)\s*/', 1, $source)) {
                    $source         = "$if $source";
                    $elseExpression = expression($source, fn ($value) => $value);
                } else {
                    throw new Error("Invalid syntax, else expressions must be followed by a block or an expression.\n$copy");
                }
            }
        }

        return $found($check, $thenBlock, $thenExpression, $elseBlock, $elseExpression);
    }

    function matchExpression(string &$source, callable $found) {
        if (!$match = consume('/^\s*(match)\s*/', 1, $source)) {
            return null;
        }

        if (!$expression = expression($source, fn ($value) => $value)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        $items = [];

        while ($left = expression($block, fn ($value) => $value)) {
            if (!$arrow = consume('/^\s*(=>)\s*/', 1, $block)) {
                return null;
            }
            
            if (!$right = expression($block, fn ($value) => $value)) {
                return null;
            }

            $items[] = [
                "left"  => $left,
                "right" => $right
            ];
        }

        return $found($expression, $items);
    }

    function callableArguments(string &$source, callable $found) {
        $items = [];
        while ($argument = consume('/^\s*([A-z][A-z0-9_]+):\s*([A-z][A-z0-9_#]+)\s*(,|$)/U', 3, $source)) {
            if (null !== ($value = expression($argument[1], fn ($value) => $value))) {
                $items[] = $found($argument[0], $value);
            }
        }

        return $items;
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
    
    /**
     * @param  string                              $source
     * @return array{0:string,1:array<int,string>}
     */
    function strings($source) {
        $strings = [];        
        
        $rebuilt = '';

        $l       = strlen($source);
        $found   = 0;
        $content = '';
        
        $start  = 0;
        $length = 0;

        $stringNumber = 0;

        for ($i = 0; $i < $l; $i++) {
            try {
                $character = $source[$i];
            } catch(\Throwable $e) {
                echo (string)$e;
            }

            if ($found > 0) {
                if ('"' === $character) {
                    for ($j = 1; '\\' === ($prev = $source[$i - $j] ?? ''); $j++);
            
                    if ($j % 2 !== 0) {
                        $found++;
                        $length = $i - 1;
                    }
                }
            } else {
                if ('"' === $character) {
                    $found++;
                    $start = $i + 1;
                }
            }

            if ($found <= 0) {
                $rebuilt .= $character;
            }

            if (2 === $found) {
                $content = preg_replace(['/\\\"/','/\\\\\\\/'], ['"','\\'], substr($source, $start, $length));
                $rebuilt .= "string#$stringNumber";
                $strings[] = $content;
                $found     = 0;
                $stringNumber++;
            }
        }



        /** @var array{0:string,1:array<int,string>} */
        return [ $rebuilt, $strings ];
    }


    /**
     * @param  string                              $source
     * @return array{0:string,1:array<int,string>}
     */
    function comments($source) {
        // TODO: save position of comments, x and y coordinates, will be useful for inline docs

        $comments = [];
        $source   = preg_replace_callback('/\/\/.*$/m', function($group) use (&$comments) {
            $comments[] = $group[0] ?? '';
            return '';
        }, $source);

        /** @var array{0:string,1:array<int,string>} */
        return [ $source, $comments ];
    }

    /**
     * @param  string $source
     * @throws Error
     * @return array
     */
    function parse(
        string $source,
    ) {
        $instructions = [];
        $previous     = "";
        while (trim($source)) {
            if ($previous === $source) {
                throw new Error("Syntax error, unknown syntax.\n$previous");
            }
            $previous = $source;
            $copy     = $source;
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
                    "block"      => $block,
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
                    'name'  => Internal\name($name, fn ($prefix, $name) => $name),
                    'block' => $block,
                ],
            ])) {
                $instructions[] = $structDeclaration;
                continue;
            }

            // ######### parameter declaration
            if ($parameter = Internal\parameter($source, fn ($operation, $mutability, $name, $type, $default) => [
                "meta" => "parameter",
                "data" => [
                    "availability" => "required",
                    "operation"    => $operation,
                    "mutability"   => trim($mutability),
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
                "meta" => "check",
                "data" => "valueEqualityCheck"
            ])) {
                $instructions[] = $valueEqualityCheck;
                continue;
            }

            // ######### pointer equality check
            if ($pointerEqualityCheck = Internal\pointerEqualityCheck($source, fn () => [
                "meta" => "check",
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
            if ($expression = Internal\expression($source, fn ($value) => $value)) {
                $instructions[] = $expression;
                continue;
            }

            // ######### name
            if ($name = Internal\name($source, fn ($prefix, $name) => $name)) {
                $instructions[] = $name;
                continue;
            }

            if ($copy === $source && !$instructions) {
                throw new Error("Invalid syntax.");
            }
        }

        return $instructions;
    }
}