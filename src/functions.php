<?php

namespace Olang\Internal {
    function parameters(string &$source, callable $found) {
        static $required = '/^(required|optional)<([\w\W]*)>/U';
        static $name     = '/^([A-z]+[A-z0-9]*)/';
    
        $items = [];
        while (preg_match($required, $source = trim($source), $matches) && isset($matches[2])) {
            $source = preg_replace($required, '', $source, 1);
            if (preg_match($name, $source = trim($source), $matches2) && isset($matches2[1])) {
                $source  = preg_replace($name, '', $source, 1);
                $items[] = $found(...[...array_slice($matches, 1), ...array_slice($matches2, 1)]);
            }
        }
        return $items?$items:null;
    }
    
    function name(string &$source, callable $found) {
        static $name = '/^([A-z]+[A-z0-9]*)/';
        $items       = [];
        while (preg_match($name, $source = trim($source), $matches) && isset($matches[1])) {
            $source  = preg_replace($name, '', $source, 1);
            $items[] = $found(...array_slice($matches, 1));
        }
        return $items?$items:null;
    }
    
    function structDeclaration(string &$source, callable $found) {
        static $structDeclaration = '/^struct\s+([\w\W]*)\s*{([\w\W]*)}/U';
        $items                    = [];
        while (preg_match($structDeclaration, $source = trim($source), $matches) && isset($matches[2])) {
            $source  = preg_replace($structDeclaration, '', $source, 1);
            $items[] = $found(...array_slice($matches, 1));
        }
        return $items?$items:null;
    }

    function callableDeclaration(string &$source, callable $found) {
        static $invokableDeclaration = '/^(const|let)\s+([\w\W]*)\s*=\s*::{([\w\W]*)}/U';
        $items                       = [];
        while (preg_match($invokableDeclaration, $source = trim($source), $matches) && isset($matches[3])) {
            $source  = preg_replace($invokableDeclaration, '', $source, 1);
            $items[] = $found(...array_slice($matches, 1));
        }
        return $items?$items:null;
    }

    function callableCall(string &$source, callable $found) {
        static $callableCall = '/^([A-z0-9][A-z0-9_]*)\(([\w\W]*)\)/';
        $items               = [];
        while (preg_match($callableCall, $source = trim($source), $matches) && isset($matches[2])) {
            $source  = preg_replace($callableCall, '', $source, 1);
            $items[] = $found(...array_slice($matches, 1));
        }
        return $items?$items:null;
    }

    function callableArguments(string &$source, callable $found) {
        static $callableArguments = '/^([A-z][A-z0-9_]+):([\w\W]+)(,|$)/U';
        $items                    = [];
        while (preg_match($callableArguments, $source = trim($source), $matches) && isset($matches[2])) {
            $source  = preg_replace($callableArguments, '', $source, 1);
            $items[] = $found(...array_slice($matches, 1));
        }
        return $items?$items:null;
    }
}


namespace OLang {
    function target(string $name = '') {
        static $language = '';
        if (!$language) {
            $language = $name;
        }

        return $language;
    }

    function transpile(
        string $source,
    ) {
        $instructions = [];
        while ($source) {
            if ($invokableDeclaration = match (target()) {
                'php' => Internal\callableDeclaration($source, fn ($mutability, $name, $block) => [
                    'mutability' => match ($mutability) {
                        'const' => 'constant',
                        'let'   => 'variable',
                        default => 'constant',
                    },
                    'name'  => Internal\name($name, fn ($name) => $name)[0] ?? false,
                    'block' => transpile($block),
                ]),
                default => false,
            }) {
                $instructions[] = [
                    'meta' => 'invokableDeclaration',
                    'data' => $invokableDeclaration[0] ?? false,
                ];
            }

            if ($structDeclaration = Internal\structDeclaration($source, fn ($name, $block) => [
                'name'  => Internal\name($name, fn ($name) => $name)[0] ?? false,
                'block' => transpile($block),
            ])) {
                $instructions[] = [
                    'meta' => 'structDeclaration',
                    'data' => $structDeclaration[0] ?? false,
                ];
            }

            if ($parameters = Internal\parameters($source, fn ($availability, $type, $name) => [
                "availability" => $availability,
                "type"         => $type,
                "name"         => $name,
            ])) {
                $instructions[] = [
                    'meta' => 'parameters',
                    'data' => $parameters,
                ];
            }

            if ($callableCall = Internal\callableCall($source, fn ($name, $arguments) => [
                "name"      => $name,
                "arguments" => Internal\callableArguments($arguments, fn ($key, $value) => [
                    "key"   => $key,
                    "value" => $value,
                ]),
            ])) {
                $instructions[] = [
                    'meta' => 'callableCall',
                    'data' => $callableCall[0] ?? false,
                ];
            }
        }

        return $instructions;
    }
}