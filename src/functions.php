<?php
namespace CatPaw\Q;

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
    public function __construct(
        public string $previous,
        public string $token,
    ) {
    }
}

/**
 * @param  array<string>             $tokens
 * @param  callable(TokenMatch):void $callback
 * @return void
 */
function next($tokens, $callback) {
    static $tail = '';
    $tail .= source();
    
    foreach ($tokens as $token) {
        if (preg_match('/'.$token.'$/', $tail, $matches)) {
            return;
        }
    }
}

class StaticDictionary {
    /**
     * @param  string $execute
     * @param  string $assignment
     * @param  string $greaterThan
     * @param  string $lesserThan
     * @param  string $greaterThanOrEquals
     * @param  string $lesserThanOrEquals
     * @param  string $let
     * @param  string $constant
     * @param  string $character
     * @param  string $number
     * @param  string $type
     * @param  string $typeIndicator
     * @param  string $variableTypeDefinition
     * @param  string $trueNameContinued
     * @param  string $trueName
     * @param  string $typedName
     * @param  string $name
     * @param  string $listOfCharactersContinued
     * @param  string $listOfCharacters
     * @param  string $string
     * @param  string $sign
     * @param  string $signedInt
     * @param  string $int
     * @param  string $float
     * @param  string $boolean
     * @param  string $value
     * @param  string $structDefinitionProperty
     * @param  string $structDefinitionContinued
     * @param  string $structDefinitionBody
     * @param  string $structDefinition
     * @param  string $structCreationProperty
     * @param  string $structCreationBodyContinued
     * @param  string $structCreationBody
     * @param  string $structCreation
     * @param  string $variableDefinition
     * @return void
     */
    public function __construct(
        public $execute,
        public $assignment,
        public $greaterThan,
        public $lesserThan,
        public $greaterThanOrEquals,
        public $lesserThanOrEquals,
        public $let,
        public $constant,
        public $character,
        public $number,
        public $type,
        public $typeIndicator,
        public $variableTypeDefinition,
        public $trueNameContinued,
        public $trueName,
        public $typedName,
        public $name,
        public $listOfCharactersContinued,
        public $listOfCharacters,
        public $string,
        public $sign,
        public $signedInt,
        public $int,
        public $float,
        public $boolean,
        public $value,
        public $structDefinitionProperty,
        public $structDefinitionContinued,
        public $structDefinitionBody,
        public $structDefinition,
        public $structCreationProperty,
        public $structCreationBodyContinued,
        public $structCreationBody,
        public $structCreation,
        public $variableDefinition,
    ) {
    }
}

class DynamicDictionary {
    /**
     * @param  string $execute
     * @param  string $assignment
     * @param  string $greaterThan
     * @param  string $lesserThan
     * @param  string $greaterThanOrEquals
     * @param  string $lesserThanOrEquals
     * @param  string $let
     * @param  string $constant
     * @param  string $character
     * @param  string $number
     * @param  string $typeIndicator
     * @param  string $trueName
     * @param  string $type
     * @param  string $variableTypeDefinition
     * @param  string $typedName
     * @param  string $name
     * @param  string $listOfCharacters
     * @param  string $string
     * @param  string $sign
     * @param  string $signedInt
     * @param  string $int
     * @param  string $float
     * @param  string $boolean
     * @param  string $structDefinitionProperty
     * @param  string $structDefinitionBody
     * @param  string $structDefinition
     * @param  string $structCreationProperty
     * @param  string $structCreationBody
     * @param  string $structCreation
     * @param  string $value
     * @param  string $variableDefinition
     * @return void
     */
    public function __construct(
        public $execute,
        public $assignment,
        public $greaterThan,
        public $lesserThan,
        public $greaterThanOrEquals,
        public $lesserThanOrEquals,
        public $let,
        public $constant,
        public $character,
        public $number,
        public $typeIndicator,
        public $trueName,
        public $type,
        public $variableTypeDefinition,
        public $typedName,
        public $name,
        public $listOfCharacters,
        public $string,
        public $sign,
        public $signedInt,
        public $int,
        public $float,
        public $boolean,
        public $structDefinitionProperty,
        public $structDefinitionBody,
        public $structDefinition,
        public $structCreationProperty,
        public $structCreationBody,
        public $structCreation,
        public $value,
        public $variableDefinition,
    ) {
    }
}

/** @return DynamicDictionary  */
function dictionary() {
    static $d = null;
    if (!$d) {
        $execute = '(\\n|;)';

        $assignment          = '(=)';
        $greaterThan         = '(>)';
        $lesserThan          = '(<)';
        $greaterThanOrEquals = '(>=)';
        $lesserThanOrEquals  = '(<=)';

        $let      = '(let)';
        $constant = '(const)';

        $character = '(.)';
        $number    = '([1-9][0-9]*)';
        
        $typeIndicator          = 'as';
        $trueName               = '('.$character.'+)';
        $type                   = '((int)|(float)|(string)|('.$trueName.'::type))';
        $variableTypeDefinition = $typeIndicator.'\\s+'.$type;
        $typedName              = $trueName.'\\s+'.$variableTypeDefinition;
        $name                   = '('.$trueName.')|('.$typedName.')';

        $listOfCharacters = '('.$character.'+)';

        $string    = '("'.$character.'")|(\''.$character.'\')';
        $sign      = '(\\-|\\+)';
        $signedInt = '('.$sign.')'.'('.$number.')';
        $int       = '('.$number.')|('.$signedInt.')';
        $float     = '('.$int.'\\.'.$number.')';
        $boolean   = '(true|false)';
        
        $structDefinitionProperty = '(('.$name.')\\s*('.$assignment.')\\s*(.+)\\s*('.$execute.'))';
        $structDefinitionBody     = '('.$structDefinitionProperty.'+)';
        $structDefinition         = '(struct\\s+('.$name.')\\s*\\{\\s*('.$structDefinitionBody.')\\s*\\})';
        
        $structCreationProperty = '(('.$trueName.')\\s*('.$assignment.')\\s*(.+)\\s*('.$execute.'))';
        $structCreationBody     = '('.$structCreationProperty.'+)';
        $structCreation         = '(('.$trueName.')\\s*\\{\\s*('.$structCreationBody.')\\s*})';
        
        $value = '(('.$string.')|('.$int.')|('.$float.')|('.$boolean.')|('.$structCreation.')|('.$trueName.'))';

        $variableDefinition = '(('.$let.')\\s*('.$name.')\\s*('.$assignment.')\\s*('.$value.')\\s*('.$execute.'))';

        $d = new DynamicDictionary(
            execute: $execute,
            assignment: $assignment,
            greaterThan: $greaterThan,
            lesserThan: $lesserThan,
            greaterThanOrEquals: $greaterThanOrEquals,
            lesserThanOrEquals: $lesserThanOrEquals,
            let: $let,
            constant: $constant,
            character: $character,
            number: $number,
            typeIndicator: $typeIndicator,
            trueName: $trueName,
            type: $type,
            variableTypeDefinition: $variableTypeDefinition,
            typedName: $typedName,
            name: $name,
            listOfCharacters: $listOfCharacters,
            string: $string,
            sign: $sign,
            signedInt: $signedInt,
            int: $int,
            float: $float,
            boolean: $boolean,
            structDefinitionProperty: $structDefinitionProperty,
            structDefinitionBody: $structDefinitionBody,
            structDefinition: $structDefinition,
            structCreationProperty: $structCreationProperty,
            structCreationBody: $structCreationBody,
            structCreation: $structCreation,
            value: $value,
            variableDefinition: $variableDefinition,
        );
    }
    return $d;
}

/**
 * @param  string $code
 * @return void
 */
function parse($code) {
    source("$code\n");
    $d = dictionary();

    while (0 < next([$d->variableDefinition], function($_, ) use (&$wat) {
    })) {
        echo "next\n";
    }
}