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

/** @return string  */
function advance() {
    return trim(substr(source(), index()));
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

/**
 * @param  string           $key
 * @param  string           $stack
 * @return false|TokenMatch
 */
function findKeyFromStack($key, $stack) {
    $keys = explode("|", $key);
    foreach ($keys as $key) {
        $key    = trim($key);
        $tokens = explode("|", $key);
        foreach ($tokens as $token) {
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
                source(advance());
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
 * @return true
 */
function set($key, $value) {
    $data = state();
    $data->set([
        ...$data->get(),
        "$key" => $value,
    ]);
    return true;
}

/**
 * @param  string $key
 * @return string
 */
function get($key) {
    $data = state();
    return $data->get()[$key] ?? '';
}


class Node {
    /**
     * @param  null|callable():string $toWASM
     * @param  null|callable():string $toPHP
     * @param  null|Node              $next
     * @return void
     */
    public function __construct(
        public $toWASM = null,
        public $toPHP = null,
        public $next = null,
    ) {
    }
}


/**
 * @param bool $reset
 * @return @return array<Node>
 */
function &ast($reset = false) {
    static $ast = [];

    if ($reset) {
        $ast = [];
    }

    return $ast;
}