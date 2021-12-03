<?php

namespace Intoy\HebatApp\Twig\Parser;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Error\LoaderError;
use Twig\Node\TextNode;
use Twig\Token;

class EntryTokenParseJs extends AbstractTokenParser
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
        return 'webpack_js';
    }


    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();
        $entryName = $stream->expect(Token::STRING_TYPE)->getValue();
        $defer = $stream->nextIf(/* Token::NAME_TYPE */ 5, 'defer');
        $async = $stream->nextIf(/* Token::NAME_TYPE */ 5, 'async');
        $inline = $stream->nextIf(/* Token::NAME_TYPE */ 5, 'inline');
        $stream->expect(Token::BLOCK_END_TYPE);

        if (!\file_exists($this->manifestFile)) {
            return new TextNode(sprintf('<!-- Webpack manifest "%s" file not exists. -->',$this->manifestFile), $token->getLine());
        }

        $manifest = \json_decode(\file_get_contents($this->manifestFile), true);
        $manifestIndex = $entryName.'.js';

        if (!isset($manifest[$manifestIndex])) {
            return new TextNode(sprintf('<!-- Webpack js entry "%s" file not exists. -->',$entryName), $token->getLine());
        }

        $entryFile = $manifest[$manifestIndex];
        $is_webpack_dev_server=filter_var($entryFile,FILTER_VALIDATE_URL);
        $entryFile =(!$is_webpack_dev_server && $this->publicDir)?rtrim($this->publicDir,"/")."/".$entryFile:$entryFile;

        return $this->getEntryContent($token, $entryFile,$inline,$defer,$async);
    }


    /**
     * @return TextNode
     */
    private function getEntryContent(Token $token, string $entryFile, $inline, $defer=false, $async=false)
    {
        if(!$inline)
        {
            $tag = \sprintf('<script type="text/javascript" src="%s"%s></script>',$entryFile,$defer?' defer':($async ?' async':''));
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
            '<script type="text/javascript">%s</script>',
            $content
        );
        return new TextNode($tag, $token->getLine());
    }
}