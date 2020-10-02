<?php

// TextStat
// Digital-512, MIT licensed.

namespace Digital512;

class TextStat
{
    const _EXCLUDE = "/[!?,.:;\/\\\]+|\s[\#$%&*+\-<=>@^_|~\[\](){}\"'`]+\s/";
    const _LINE_SEPARATOR = "/\n|\r/";
    const _SENTENCE_SEPARATOR = "/(?<=[!?.])\s+/";

    protected static $mbstring = null;

    private $text = null;
    private $cleanText = null;

    function __construct()
    {
        self::$mbstring = extension_loaded("mbstring");
    }

    // Setters

    public function setText($text, $exclude = self::_EXCLUDE)
    {
        // Set actual text
        $this->text = $text;

        // Set clean text
        $this->cleanText = self::cleanText($text, $exclude);
    }

    // Getters

    public function getText()
    {
        return $this->text;
    }

    public function getCleanText()
    {
        return $this->cleanText;
    }

    public function getWordCount()
    {
        return count($this->getWords());
    }

    public function getWords()
    {
        return explode(" ", $this->cleanText);
    }

    public function getLineCount($separator = self::_LINE_SEPARATOR)
    {
        return count($this->getLines($separator));
    }

    public function getLines($separator = self::_LINE_SEPARATOR)
    {
        return preg_split($separator, $this->text);
    }

    public function getSentenceCount($separator = self::_SENTENCE_SEPARATOR)
    {
        return count($this->getSentences($separator));
    }

    public function getSentences($separator = self::_SENTENCE_SEPARATOR)
    {
        return preg_split($separator, $this->text);
    }

    public function getWordsPosition()
    {
        $words = [];
        $text = utf8_decode($this->text);
        $strParts = $this->getWords();

        foreach ($strParts as $part) {
            $words[strpos($text, utf8_decode($part))] = $part;
        }

        return $words;
    }

    public function getLength($whitespace = true)
    {
        return self::strLength(
            $whitespace ? $this->text : preg_replace("/\s+/", "", $this->text)
        );
    }

    public function getUniqueWordCount($caseSensitive = true)
    {
        return count($this->getUniqueWords(0, false, $caseSensitive));
    }

    public function getUniqueWords(
        $limit = 0,
        $sorted = false,
        $caseSensitive = true
    ) {
        $allWords = $this->getWords();

        if (!$caseSensitive) {
            $allWords = array_map("self::toLowerCase", $allWords);
        }

        $unique = array_count_values($allWords);

        if ($sorted) {
            arsort($unique);
        }

        return $limit ? array_slice($unique, 0, $limit) : $unique;
    }

    public function getUniqueWordPercentage($caseSensitive = true)
    {
        return ($this->getUniqueWordCount($caseSensitive) /
            $this->getWordCount()) *
            100;
    }

    public function getLengthsByWord()
    {
        $wordLengths = [];

        foreach ($this->getWords() as $word) {
            $wordLengths[$word] = self::strLength($word);
        }

        return $wordLengths;
    }

    public function getWordsLength(callable $minMax)
    {
        $minMaxWords = [];
        $words = $this->getLengthsByWord();
        $minMaxLength = $minMax($words);

        foreach ($words as $word => $length) {
            if ($length === $minMaxLength) {
                $minMaxWords[$word] = $minMaxLength;
            }
        }

        return $minMaxWords;
    }

    public function getWordLength(callable $minMax)
    {
        return $minMax($this->getLengthsByWord());
    }

    public function getAverageWords($array)
    {
        return self::average(array_map("count", self::getWordsBy($array)));
    }

    // Utilities

    public static function cleanText($text, $exclude = self::_EXCLUDE)
    {
        $text = preg_replace($exclude, " ", $text); // Remove punctuation and separation
        $text = preg_replace("`[ ]+`", " ", $text); // Remove multiple spaces
        $text = trim($text); // Strip whitespace from the beginning and end

        return $text;
    }

    public static function getWordsBy($array)
    {
        $lines = [];
        $allLines = array_map("self::cleanText", $array);

        foreach ($allLines as $line) {
            array_push($lines, explode(" ", $line));
        }

        return $lines;
    }

    public static function average($array)
    {
        $array = array_filter($array);
        return array_sum($array) / count($array);
    }

    public static function strLength($str)
    {
        return strlen(utf8_decode($str));
    }

    public static function toLowerCase($text)
    {
        return self::$mbstring
            ? mb_strtolower($text, "UTF-8")
            : strtolower($text);
    }
}
