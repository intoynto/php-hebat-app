<?php

namespace Intoy\HebatApp\Twig;

use Twig\Extension\AbstractExtension;

use Intoy\HebatApp\Twig\Parser\EntryTokenParseJs;
use Intoy\HebatApp\Twig\Parser\EntryTokenParseCss;

class WebpackExtension extends AbstractExtension
{
    /**
     * @var string
     */
    protected $publicDir;


    /**
     * @var string
     */
    protected $manifestFile;


    public function __construct(string $manifestFile, string $publicDir)
    {
        $this->manifestFile = $manifestFile;
        $this->publicDir = $publicDir;
    }

    public function getName()
    {
        return 'hebat.webpack.extension';
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new EntryTokenParseJs($this->manifestFile, $this->publicDir),
            new EntryTokenParseCss($this->manifestFile, $this->publicDir),
        ];        
    }
}