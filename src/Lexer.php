<?php

//    Copyright (c) 2016 Denis BEURIVE
//
//    This work is licensed under the Creative Commons Attribution 3.0
//    Unported License.
//
//    A summary of the license is given below.
//
//    --------------------------------------------------------------------
//
//    You are free:
//
//    * to Share - to copy, distribute and transmit the work
//    * to Remix - to adapt the work
//
//    Under the following conditions:
//
//    Attribution. You must attribute the work in the manner specified by
//    the author or licensor (but not in any way that suggests that they
//    endorse you or your use of the work).
//
//        * For any reuse or distribution, you must make clear to others
//          the license terms of this work.
//
//        * Any of the above conditions can be waived if you get
//          permission from the copyright holder.
//
//        * Nothing in this license impairs or restricts the author's moral
//          rights.
//
//    Your fair dealing and other rights are in no way affected by the
//    above.

/**
 * This file implements the lexer.
 */

namespace dbeurive\Lexer;

/**
 * Class Lexer
 *
 * This class implements the lexer.
 *
 * @package dbeurive\Lexer
 */
class Lexer
{
    const INDEX_REGEXP      = 0;
    const INDEX_TYPE        = 1;
    const INDEX_TRANSFORMER = 2;

    /**
     * @var array Tokens specifications.
     */
    private $__specifications = array();

    /**
     * Lexer constructor.
     * @param array $inSpecifications This array represents the tokens specifications.
     *        Each element of this array is an array that specifies a token.
     *        It contains 2 or 3 elements.
     *        - First element: a regular expression that describes the token.
     *        - Second element: the name of the token.
     *        - Third element: an optional callback function.
     *          The signature of this function must be:
     *          null|mixed function(array $inMatches)
     * @throws \Exception
     */
    public function __construct(array $inSpecifications)
    {
        $defaultTransformer = function(array $inMatches) {
            return $inMatches[0];
        };

        /** @var array $_specification */
        foreach ($inSpecifications as $_index => $_specification) {

            if (! is_array($_specification)) {
                throw new \Exception("Invalid token specification index ${_index}: token specification must be an array (array(regexp, name, [processor])).");
            }

            $count = count($_specification);
            if ((2 != $count) && (3 != $count)) {
                throw new \Exception("Invalid token specification index ${_index}: invalid number of element (${count}).");
            }

            if (2 == count($_specification)) {
                $_specification[] = $defaultTransformer;
            }

            $matches = array();
            /** @var string $regexp */
            $regexp = $_specification[self::INDEX_REGEXP];
            if (! preg_match('/^\/(\^?)(.*)\/([imsxeADSUXJu]*)$/', $regexp, $matches)) {
                throw new \Exception("Invalid regular expression \"$regexp\".");
            }

            array_shift($matches);
            $matches[0] = '^';
            $regexp = '/' . array_shift($matches) . implode('/', $matches);

            // Sanity check.
            if (1 === preg_match($regexp, '')) {
                throw new \Exception("Invalid regular expression \"$regexp\". This expression matches an empty string! This may produces infinite loops!");
            }

            $this->__specifications[] = array(
                $regexp,
                $_specification[self::INDEX_TYPE],
                $_specification[self::INDEX_TRANSFORMER]
            );
        }
    }

    /**
     * Explode a given string into a list of tokens.
     * @param string $inString The string to explode into tokens.
     * @return array The method returns a list of tokens.
     *         Each element of the returned list is an instance of the class Token.
     * @throws \Exception
     * @see Token
     */
    public function lex($inString) {

        $result = array();
        $match  = null;

        while(0 != strlen($inString)) {
            $found = false;

            foreach ($this->__specifications as $_specification) {
                $matches = array();
                /** @var string $regexp */
                $regexp = $_specification[self::INDEX_REGEXP];
                /** @var string $type */
                $type = $_specification[self::INDEX_TYPE];
                /** @var array|callable $transformer */
                $transformer = $_specification[self::INDEX_TRANSFORMER];

                if (1 === preg_match($regexp, $inString, $matches)) {
                    $found = true;
                    $match = $matches[0];
                    $token = new Token();
                    $token->value = call_user_func($transformer, $matches);
                    if (! is_null($token->value)) {
                        $token->type = $type;
                        $result[] = $token;
                    }
                    break;
                }
            }

            if (! $found) {
                throw new \Exception("Invalid input: \"$inString\"");
            }

            $inString = substr($inString, strlen($match));
        };

        return $result;
    }
}