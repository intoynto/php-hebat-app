<?php

namespace Intoy\HebatApp\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigStringExtension extends AbstractExtension
{

    public function getName()
    {
        return 'hebat.twig.string.extension';
    }

    /**
     * @param string $text
     */
    public function slugify($text, string $divider="-")
    {
        $text=trim((string)$text);
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function toBase64($value)
    {
        return $value!==null?base64_encode((string)$value):null;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters()        
    {
        return [
            new TwigFilter('slug',[$this,'slugify']),
            new TwigFilter('to_slug',[$this,'slugify']),
            new TwigFilter('to_base64',[$this,'toBase64']),
        ];
    }    
}