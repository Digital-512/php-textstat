<?php

// TextStat
// Digital-512, MIT licensed.

namespace Digital512;

class TextStat
{
    const _EXCLUDE = "/[!?,.:;\/\\\]+|\s[\#$%&*+\-<=>@^_|~\[\](){}\"'`]+\s/";
    const _LINE_SEPARATOR = "/\n|\r/";
    const _SENTENCE_SEPARATOR = "/(?<=[!?.])\s+/";

    /**
     * Is the MB String extension loaded?
     * @var boolean $mbstring
     */
    protected static $mbstring = null;

    private $text = null;
    private $cleanText = null;

    function __construct()
    {
        self::$mbstring = extension_loaded("mbstring");
    }

    // Setters

    /**
     * Set and clean the text.
     * @param string $text Text to be set
     * @param string $exclude (optional) RegEx rules to clean the text
     */
    public function setText($text, $exclude = self::_EXCLUDE)
    {
        // Set actual text
        $this->text = $text;

        // Set clean text
        $this->cleanText = self::cleanText($text, $exclude);
    }

    // Getters

    /**
     * Returns the original text that has been set.
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Returns the cleaned text.
     * @return string
     */
    public function getCleanText()
    {
        return $this->cleanText;
    }

    /**
     * Returns the quantity of words in the text.
     * @return int
     */
    public function getWordCount()
    {
        return count($this->getWords());
    }

    /**
     * Returns an array of all words in the text.
     * @return array
     */
    public function getWords()
    {
        return explode(" ", $this->cleanText);
    }

    /**
     * Returns the quantity of lines in the text.
     * @param string $separator (optional) RegEx rules to separate lines
     * @return int
     */
    public function getLineCount($separator = self::_LINE_SEPARATOR)
    {
        return count($this->getLines($separator));
    }

    /**
     * Returns an array of lines in the text.
     * @param string $separator (optional) RegEx rules to separate lines
     * @return array
     */
    public function getLines($separator = self::_LINE_SEPARATOR)
    {
        return preg_split($separator, $this->text);
    }

    /**
     * Returns the quantity of sentences in the text.
     * @param string $separator (optional) RegEx rules to separate sentences
     * @return int
     */
    public function getSentenceCount($separator = self::_SENTENCE_SEPARATOR)
    {
        return count($this->getSentences($separator));
    }

    /**
     * Returns an array of sentences in the text.
     * @param string $separator (optional) RegEx rules to separate sentences
     * @return array
     */
    public function getSentences($separator = self::_SENTENCE_SEPARATOR)
    {
        return preg_split($separator, $this->text);
    }

    /**
     * Returns an associative array, where the key is the numeric position of the word
     * in the text and the value is the actual word itself.
     * @return array
     */
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

    /**
     * Returns the length of the text (characters).
     * @param boolean $whitespace (optional) Count or not count whitespaces as characters
     * @return int
     */
    public function getLength($whitespace = true)
    {
        return self::strLength(
            $whitespace ? $this->text : preg_replace("/\s+/", "", $this->text)
        );
    }

    /**
     * Returns the quantity of unique words in the text.
     * @param boolean $caseSensitive (optional) Count words with different capitalization
     * @return int
     */
    public function getUniqueWordCount($caseSensitive = true)
    {
        return count($this->getUniqueWords(0, false, $caseSensitive));
    }

    /**
     * Returns an associative array, where the key is the actual word itself
     * and the value shows how many times this word repeats in the text.
     * @param int $limit (optional) Set the limit of words in array
     * @param boolean $sorted (optional) Sort the array by repeats count
     * @param boolean $caseSensitive (optional) Count words with different capitalization
     * @return array
     */
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

    /**
     * Returns the percentage of unique words in the text.
     * @param boolean $caseSensitive (optional) Count words with different capitalization
     * @return float
     */
    public function getUniqueWordPercentage($caseSensitive = true)
    {
        return ($this->getUniqueWordCount($caseSensitive) /
            $this->getWordCount()) *
            100;
    }

    /**
     * Returns an associative array, where the key is the actual word itself
     * and the value is the length of this word.
     * @return array
     */
    public function getLengthsByWord()
    {
        $wordLengths = [];

        foreach ($this->getWords() as $word) {
            $wordLengths[$word] = self::strLength($word);
        }

        return $wordLengths;
    }

    /**
     * Returns an associative array of shortest or longest words in the text, where the
     * key is the actual word itself and the value is the length of this word.
     * @param string $minMax A function that finds min or max value in array
     * @return array
     */
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

    /**
     * Returns the length of shortest ow longest word, or average length.
     * @param string $minMax A function that finds min, max or average value in array
     * @return float
     */
    public function getWordLength(callable $minMax)
    {
        return $minMax($this->getLengthsByWord());
    }

    /**
     * Returns the length of text section (line, paragraph or sentence) in words.
     * @param string $minMax A function that finds min, max or average value in array
     * @param array $array An array of sections
     * @return float
     */
    public function getSectionLength(callable $minMax, $array)
    {
        return $minMax(array_map("count", self::getWordsBy($array)));
    }

    // Utilities

    /**
     * Returns the cleaned text.
     * @param string $text Text to be cleaned
     * @param string $exclude (optional) RegEx rules to clean the text
     * @return string
     */
    public static function cleanText($text, $exclude = self::_EXCLUDE)
    {
        $text = preg_replace($exclude, " ", $text); // Remove punctuation and separation
        $text = preg_replace("`[ ]+`", " ", $text); // Remove multiple spaces
        $text = trim($text); // Strip whitespace from the beginning and end

        return $text;
    }

    /**
     * Returns an array of text sections (lines, paragraphs or sentences)
     * where the value is an array of words.
     * @param array $array An array of sections
     * @return array
     */
    public static function getWordsBy($array)
    {
        $sections = [];
        $allSections = array_map("self::cleanText", $array);

        foreach ($allSections as $section) {
            array_push($sections, explode(" ", $section));
        }

        return $sections;
    }

    /**
     * Returns the average value of all values in array.
     * @param array $array An array of values
     * @return float
     */
    public static function average($array)
    {
        $array = array_filter($array);
        return array_sum($array) / count($array);
    }

    /**
     * Returns the length of string (UTF-8 compatible).
     * @param string $str The input string
     * @return int
     */
    public static function strLength($str)
    {
        return strlen(utf8_decode($str));
    }

    /**
     * Returns lowercase string (UTF-8 compatible if MB String extension loaded).
     * @param string $str The input string
     * @return string
     */
    public static function toLowerCase($str)
    {
        return self::$mbstring
            ? mb_strtolower($str, "UTF-8")
            : strtolower($str);
    }
}
