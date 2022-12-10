<?php

namespace Olang\Internal {
    function consume(string $pattern, int $groups, string &$source):bool|string|array {
        if (preg_match($pattern, $source = trim($source), $matches) && isset($matches[$groups])) {
            $source = trim(preg_replace($pattern, '', $source, 1));
            if (count($matches) === 2) {
                return $matches[1] ?? '';
            }
            return array_slice($matches, 1);
        }
        return false;
    }

    function parameter(string &$source, callable $found) {
        if (!$name = consume('/^([A-z]+[A-z0-9]*)/', 1, $source)) {
            return null;
        }

        if (!$type = consume('/:\s*([\w\W]*)(,|$)/Um', 2, $source)) {
            return null;
        }

        return $found($name, $type[1] ?? '');
    }
    
    function name(string &$source, callable $found) {
        if (!$name = consume('/^(:{2})?([A-z]+[A-z0-9]*)/', 1, $source)) {
            return null;
        }

        return $found(...$name);
    }

    function block(string &$source) {
        $source  = trim($source);
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
        if (!$name = consume('/^struct\s+([A-z][A-z0-9]*)/', 1, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found($name, $block);
    }

    function structCallableDeclaration(string &$source, callable $found) {
        if (!$name = consume('/^::([A-z0-9]+)\s*=>\s*/', 1, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found($name, $block);
    }

    function callableDeclaration(string &$source, callable $found) {
        if (!$declaration = consume('/^(const|let)\s+([\w\W]*)\s*=>\s*([A-z][A-z0-9]*)\s*/', 3, $source)) {
            return null;
        }

        if (!$block = block($source)) {
            return null;
        }

        return $found(...[...$declaration, $block]);
    }

    function callableCall(string &$source, callable $found) {
        if (!$call = consume('/^([A-z0-9][A-z0-9_]*)\(([\w\W]*)\)/', 2, $source)) {
            return null;
        }

        return $found(...$call);
    }

    function callableArguments(string &$source, callable $found) {
        if (!$arguments = consume('/^([A-z][A-z0-9_]+):([\w\W]+)(,|$)/U', 2, $source)) {
            return null;
        }

        return $found(...$arguments);
    }
}


namespace OLang {
    function parse(
        string $source,
    ) {
        $source       = trim($source);
        $instructions = [];
        while ($source) {
            $copy = "$source";
            // ######### struct callable
            if ($structCallableDeclaration = Internal\structCallableDeclaration($source, fn ($name, $block) => [
                "name"  => trim($name),
                "block" => parse(trim($block)),
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
                'block'  => parse(trim($block)),

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
                    "value" => trim($value),
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

            // ######### parameters
            if ($parameter = Internal\parameter($source, fn ($name, $type) => [
                "availability" => "required",
                "type"         => trim($type),
                "name"         => trim($name),
            ])) {
                $instructions[] = [
                    'meta' => 'parameter',
                    'data' => $parameter,
                ];
                continue;
            }

            if ($copy === $source) {
                return $instructions;
            }
        }

        return $instructions;
    }
}