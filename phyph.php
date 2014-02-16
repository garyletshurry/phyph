<?php

class Phyph
{

    public $boundaries = '<>\t\n\r\0\x0B !"§$%&/()=?….,;:-–_„”«»‘’\'/\\‹›()[]{}*+´`^|©℗®™℠¹²³';
    public $hyphen = '&shy;';
    public $wordmin = 8;
    public $leftmin;
    public $rightmin;
    public $charmin;
    public $charmax;

    private $pattern = false;

    public function loadPattern($file = false)
    {
        if (empty($file) || !file_exists($file) || $this->pattern) {
            return false;
        }

        $pattern = include "$file";

        if (empty($pattern['pattern'])) {
            return false;
        }

        $this->leftmin  = isset($pattern['leftmin'])    ? $pattern['leftmin']   : 2;
        $this->rightmin = isset($pattern['rightmin'])   ? $pattern['rightmin']  : 2;
        $this->charmin  = isset($pattern['charmin'])    ? $pattern['charmin']   : 2;
        $this->charmax  = isset($pattern['charmax'])    ? $pattern['charmax']   : 10;

        $this->parsePattern($pattern['pattern']);
    }

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

    public function hyphenate($text)
    {
        if (!$this->pattern) {
            return $text;
        }

        $return = array();
        $word = '';

        for ($i = 0, $len = mb_strlen($text); $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);
            if (mb_strpos($this->boundaries, $char) === false) {
                $word .= $char;
                continue;
            }
            if ($word !== '') {
                $return[] = $this->hyphenateWord($word);
                $word = '';
                continue;
            }
            $return[] = $char;
        }

        return join('', $return);
    }

    private function hyphenateWord($word)
    {
        if (mb_strlen($word) < $this->wordmin) {
            return $word;
        }
        if (mb_strpos($word, $this->hyphen) !== false) {
            return $word;
        }

        $matchWord = '.' . $word . '.';
        $matchWordChars = mb_split_chars($matchWord);
        $matchWord = mb_strtolower($matchWord);

        $hyphenateWord = array();

        for ($position = 0, $mwl = mb_strlen($matchWord); $position < ($mwl - $this->charmin); $position++) {
            $maxPatternLength = min($this->charmax, $mwl - $position);

            for ($patternLength = $this->charmin; $patternLength < $maxPatternLength; $patternLength++) {

                if (isset($this->pattern[mb_substr($matchWord, $position, $patternLength)])) {
                    $pattern = $this->pattern[mb_substr($matchWord, $position, $patternLength)];
                    $digit = 1;

                    for ($i = 0, $l = mb_strlen($pattern); $i < $l; $i++) {
                        $char = $pattern[$i];

                        if (is_numeric($char)) {
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

        for ($i = 0, $l = $this->leftmin, $wl = mb_strlen($word); $l < ($wl - $this->rightmin); $l++) {
            if (isset($hyphenateWord[$l]) && $hyphenateWord[$l] % 2 !== 0) {
                array_splice($matchWordChars, $l + $i + 1, 0, $this->hyphen);
                $i++;
            }
        }

        return implode('', trim($matchWordChars, '.'));
    }

}