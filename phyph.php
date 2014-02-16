<?php

/**
 * Class Phyph
 *
 * A simple hyphenation class.
 *
 * Licensed under Creative Commons Attribution-ShareAlike 3.0
 * http://creativecommons.org/licenses/by-sa/3.0/
 *
 * Phyph is a fork of phpHyphenator (http://yellowgreen.de/phphyphenator/)
 */
class Phyph
{

    /**
     * these characters that indicate the end/beginning of a word
     * @var string
     */
    public $boundaries = "<>\t\n\r\0\x0B !\"§$%&/()=?….,;:-–_„”«»‘’'/\\‹›()[]{}*+´`^|©℗®™℠¹²³";
    /**
     * string which is inserted as hyphen
     * @var string
     */
    public $hyphen = '&shy;';
    /**
     * minimum length of word, which will be hyphenated
     * @var int
     */
    public $wordmin = 8;

    private $leftmin;
    private $rightmin;
    private $charmin;
    private $charmax;

    private $pattern = false;

    /**
     * This method tries to load the provided pattern-file.
     * It returns FALSE in case of an error, otherwise nothing.
     *
     * @param bool $file
     * @return bool
     */
    public function loadPattern($file = false)
    {
        if (empty($file) || !file_exists($file) || $this->pattern) {
            return false;
        }

        $pattern = include("$file");

        // ensure that we even loaded a pattern-file with a pattern
        if (empty($pattern['pattern'])) {
            return false;
        }

        // if different options were provided, use them, otherwise some default values
        $this->leftmin  = isset($pattern['leftmin'])    ? $pattern['leftmin']   : 2;
        $this->rightmin = isset($pattern['rightmin'])   ? $pattern['rightmin']  : 2;
        $this->charmin  = isset($pattern['charmin'])    ? $pattern['charmin']   : 2;
        $this->charmax  = isset($pattern['charmax'])    ? $pattern['charmax']   : 10;

        $this->parsePattern($pattern['pattern']);
    }

    /**
     * This method builds the internal pattern-array
     *
     * @param $pattern
     */
    private function parsePattern($pattern)
    {
        $this->pattern = array();

        $pattern = explode(' ', $pattern);

        foreach ($pattern as $element) {
            if (trim($element) === '') {
                continue;
            }
            $this->pattern[str_replace(array(1,2,3,4,5,6,7,8,9,0), '', $element)] = $element;
        }
    }

    /**
     * This method hyphenates the given text. It will be split up into words,
     * which then are hyphenated separately.
     *
     * @param $text
     * @return string
     */
    public function hyphenate($text)
    {
        // check if we already have loaded a pattern, otherwise just return the text
        if (!$this->pattern) {
            return $text;
        }

        // prepare some variables
        $text .= ' ';
        $return = array();
        $word = '';

        // go through the text and hyphenate single words
        for ($i = 0, $len = mb_strlen($text); $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);
            if (mb_strpos($this->boundaries, $char) === false) {
                $word .= $char;
                continue;
            }
            if ($word !== '') {
                $return[] = $this->hyphenateWord($word);
                $word = '';
            }
            $return[] = $char;
        }

        return implode('', array_slice($return, 0, -1));
    }

    /**
     * This method returns the hyphenated string of the provided word.
     *
     * @param $word
     * @return string
     */
    private function hyphenateWord($word)
    {
        // is the word long enough?
        if (mb_strlen($word) < $this->wordmin) {
            return $word;
        }
        // does the word already contain the hyphen?
        if (mb_strpos($word, $this->hyphen) !== false) {
            return $word;
        }

        // prepare some variables
        $matchWord = '.' . $word . '.';
        $matchWordLength = mb_strlen($matchWord);

        $matchWordChars = array();
        for ($i = 0; $i < $matchWordLength; $i++) {
            $matchWordChars[] = mb_substr($matchWord, $i, 1);
        }

        $matchWord = mb_strtolower($matchWord);

        $hyphenateWord = array();

        // walk through the word and figure out, where to insert the hyphens
        for ($position = 0; $position < ($matchWordLength - $this->charmin); $position++) {
            $maxPatternLength = min($this->charmax, $matchWordLength - $position);

            for ($patternLength = $this->charmin; $patternLength < $maxPatternLength; $patternLength++) {

                if (isset($this->pattern[mb_substr($matchWord, $position, $patternLength)])) {
                    $pattern = $this->pattern[mb_substr($matchWord, $position, $patternLength)];
                    $digit = 1;

                    for ($i = 0, $l = mb_strlen($pattern); $i < $l; $i++) {
                        $char = $pattern[$i];

                        if (is_numeric($char) && $char < 10) {
                            $index = $i === 0 ? $position - 1 : $position + $i - $digit;

                            if (!isset($hyphenateWord[$index]) || $hyphenateWord[$index] !== $char) {
                                $hyphenateWord[$index] = $char;
                            }

                            $digit++;
                        }
                    }
                }
            }
        }

        // insert the hyphens
        for ($i = 0, $l = $this->leftmin, $wl = mb_strlen($word); $l < ($wl - $this->rightmin); $l++) {
            if (isset($hyphenateWord[$l]) && $hyphenateWord[$l] % 2 !== 0) {
                array_splice($matchWordChars, $l + $i + 1, 0, $this->hyphen);
                $i++;
            }
        }

        return trim(implode('', $matchWordChars), '.');
    }

}