<?php
namespace CatPaw\Q;

use function CatPaw\Store\writable;

use Catpaw\Store\Writable;
use Error;


/**
 * @param  int $increase
 * @return int
 */
function id($increase = 0) {
    static $source_id = 0;
    $source_id += $increase;
    return $source_id;
}

/**
 * @param  false|string $code
 * @return mixed
 */
function source($code = false) {
    static $sourceCode = '';
    if (false !== $code) {
        $sourceCode = $code;
        id(1);
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

            "if" => "if",

            "assignment"          => "=",
            "equals"              => "==",
            "greater-than"        => ">",
            "lesser-than"         => "<",
            "greater-than-equals" => ">=",
            "lesser-than-equals"  => "<=",
            
            "let"   => "let",
            "const" => "const",

            "type"          => "int|float|string",
            "typeIndicator" => ":",

            "sign"       => "-|+",
            "signed-int" => "<sign><number>",
            "int"        => "<number>|<signedInt>",
            "float"      => "<int>.<number>",
            "<boolean>"  => "true|false",
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
        $tokens = explode("|", $map[$key] ?? $key);
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
 * @param  int|false $value
 * @return int
 */
function index($value = false) {
    static $i = 0;
    if (false !== $value) {
        $i = $value;
    }

    return $i;
}

class Fallback {
    /**
     * @param  null|Fallback $else
     * @return null|Fallback
     */
    public function else($else) {
        return $else;
    }
}

/**
 * @param string $key
 * @param  false|(callable(TokenMatch):void) $callback
 * @return null|Fallback
 */
function token($key, $callback = false) {
    static $last_id = 0;
    static $source  = '';
    static $i       = 0;
    static $length  = 0;
    $i              = index();
    $new_id         = id();

    if (!$source || $last_id !== $new_id) {
        $last_id = $new_id;
        $source  = source();
        $stack   = '';
        $length  = strlen($source);
        $i       = 0;
    }

    $stack = '';
    while ($i < $length) {
        $stack .= $source[$i];
        if ($match = findKeyFromStack($key, $stack)) {
            $i++;
            index($i);
            if ($callback) {
                $callback($match);
            }
            return null;
        }
        $i++;
        index($i);
    }

    return new Fallback();
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