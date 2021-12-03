<?php

namespace Intoy\HebatApp\Twig\Parser;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Error\LoaderError;
use Twig\Node\TextNode;
use Twig\Token;

class EntryTokenParseCss extends AbstractTokenParser
{
    /**
     * @var string
     */
    private $manifestFile;

    /**
     * @var string
     */
    private $publicDir;


    public function __construct(string $manifestFile, string $publicDir)
    {
        $this->manifestFile = $manifestFile;
        $this->publicDir = $publicDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'webpack_css';
    }


    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();
        $entryName = $stream->expect(Token::STRING_TYPE)->getValue();
        $inline = $stream->nextIf(/* Token::NAME_TYPE */ 5, 'inline');
        $stream->expect(Token::BLOCK_END_TYPE);

        if (!\file_exists($this->manifestFile)) {
            return new TextNode(sprintf('<!-- Webpack manifest "%s" file not exists. -->',$this->manifestFile), $token->getLine());
        }

        $manifest = \json_decode(\file_get_contents($this->manifestFile), true);
        $manifestIndex = $entryName.'.css';

        if (!isset($manifest[$manifestIndex])) {
            return new TextNode(sprintf('<!-- Webpack css entry "%s" file not exists. -->',$entryName), $token->getLine());
        }

        $entryFile = $manifest[$manifestIndex];
        $is_webpack_dev_server=filter_var($entryFile,FILTER_VALIDATE_URL);
        $entryFile =(!$is_webpack_dev_server && $this->publicDir)?rtrim($this->publicDir,"/")."/".$entryFile:$entryFile;

        return $this->getEntryContent($token, $entryFile,$inline);
    }


    /**
     * @return TextNode
     */
    private function getEntryContent(Token $token, string $entryFile, $inline)
    {
        if(!$inline)
        {
            $tag = \sprintf('<link type="text/css" href="%s" rel="stylesheet">',$entryFile);
            return new TextNode($tag, $token->getLine());
        }

        if (!\file_exists($entryFile)) {
            return new TextNode(\sprintf('<!-- Entry file "%s" does not exists.. -->',$entryFile), $token->getLine());
        }

        $content = \file_get_contents($entryFile);
        if (false === $content) {
            return new TextNode(\sprintf('<!-- Unable to read file "%s" file not exists. -->',$entryFile), $token->getLine());
        }

        $tag = \sprintf(
            '<style>%s</style>',
            $content
        );
        return new TextNode($tag, $token->getLine());
    }
}