<?php
namespace CatPaw\Q;

use function CatPaw\Store\writable;

use Catpaw\Store\Writable;
use Error;

/**
 * @param  false|string $code
 * @return mixed
 */
function source($code = false) {
    static $sourceCode = '';
    if (false !== $code) {
        $sourceCode = $code;
    }
    return $sourceCode;
}

class TokenMatch {
    /**
     * @param  string $previous
     * @param  string $token
     * @return void
     */
    public function __construct(
        public $previous,
        public $token,
    ) {
    }
}

/** @return array<string,string>  */
function map() {
    static $map = [];
    if (!$map) {
        $map = [
            "execute" => "\n|;",

            "assignment"          => "=",
            "greaterThan"         => ">",
            "lesserThan"          => "<",
            "greaterThanOrEquals" => ">=",
            "lesserThanOrEquals"  => "<=",
            
            "let"   => "let",
            "const" => "const",

            "character" => "_",
            "number"    => "_",

            "type"                   => "int|float|string|<trueName>::type",
            "typeIndicator"          => "as",
            "variableTypeDefinition" => "<typeIndicator> <type>",

            "trueNameContinued" => "<character><trueName>",
            "trueName"          => "<character>|<trueNameContinued>",
            "typedName"         => "<truename><variableTypeDefinition>",
            "name"              => "<trueName>|<typedMame>",

            "listofCharactersContinued" => "<character><listOfCharacters>",
            "listOfCharacters"          => "<character>|<listOfCharactersContinued>",
            "string"                    => "\"<listOfCharacters>\"|'<listOfCharacters>'",

            "sign"      => "-|+",
            "signedInt" => "<sign><number>",
            "int"       => "<number>|<signedInt>",
            "float"     => "<int>.<number>",
            "<boolean>" => "true|false",

            "value" => "<string>|<int>|<float>|<boolean>|<trueName>|<structCreation>",
        ];
    }
    return $map;
}

/**
 * @param  string $alias
 * @return string
 */
function unalias($alias) {
    $map = map();
    if (preg_match('/\<(.+)\>/i', $alias, $match) && count($match) > 1) {
        $unaliased = unalias($map[$match[1] ?? '']);
        return preg_replace('/\<(.+)\>/i', $unaliased, $alias);
    }
    return $alias;
}

/**
 * @param  string           $key
 * @param  string           $stack
 * @return false|TokenMatch
 */
function findKeyFromStack($key, $stack) {
    $map  = map();
    $keys = explode("|", $key);
    foreach ($keys as $key) {
        $key    = trim($key);
        $tokens = explode("|", $map[$key] ?? '');
        foreach ($tokens as $token) {
            $token = unalias(preg_replace('/ +$/', '', preg_replace('/^ +/', '', $token)));
            if (str_ends_with($stack, $token)) {
                $tokenLength = strlen($token);
                $stackLength = strlen($stack);
                $previous    = substr($stack, 0, $stackLength - $tokenLength);
                return new TokenMatch(
                    previous: $previous,
                    token: $token,
                );
            }
        }
    }
    return false;
}

/**
 * @param  string                    $key
 * @param  callable(TokenMatch):void $callback
 * @return void
 */
function next($key, $callback) {
    static $source = '';
    static $i      = 0;
    static $length = 0;

    if (!$source) {
        $source = source();
        $length = strlen($source);
        $i      = 0;
    }

    $stack = '';
    while ($i < $length) {
        $stack .= $source[$i];
        if ($match = findKeyFromStack($key, $stack)) {
            $i++;
            $callback($match);
            break;
        }
        $i++;
    }
}

class State {
    /** @var false|Writable */
    private static $state = false;

    /** @return Writable  */
    public static function use() {
        if (!static::$state) {
            static::$state = writable([]);
        }
        return static::$state;
    }
}

/** @return Writable  */
function state() {
    return State::use();
}

/**
 * @param  string $key
 * @param  string $value
 * @throws Error
 * @return void
 */
function set($key, $value) {
    $data = state();
    $data->set([
        ...$data->get(),
        "$key" => $value,
    ]);
}

/**
 * @param  string $key
 * @return string
 */
function get($key) {
    $data = state();
    return $data->get()[$key] ?? '';
}

/**
 * @param  string $code
 * @return void
 */
function parse($code) {
    source("$code\n");

    next("let", function($m) {
        next("assignment", function($m) {
            set("name", trim($m->previous));
            next("execute", function($m) {
                set("value", trim($m->previous));
            });
        });
    });

    $name  = get('name');
    $value = get('value');

    print_r([
        "name"  => $name,
        "value" => $value,
    ]);
}