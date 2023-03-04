<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Renderer;

use Throwable;
use Slim\Error\Renderers\HtmlErrorRenderer as SlimHtmlErrorRenderer;
use Intoy\HebatDatabase\QueryException;
use Slim\Exception\{
    HttpException,
    HttpNotFoundException,
    HttpForbiddenException,
    HttpNotImplementedException,
    HttpMethodNotAllowedException,
    HttpUnauthorizedException,
};

class HtmlErrorRenderer extends SlimHtmlErrorRenderer
{   
    protected string $defaultErrorTitle = 'Application Error';
    
    protected string $defaultErrorDescription = 'A website error has occurred. Sorry for the temporary inconvenience.';

    protected function resolveApplicationTitle():string
    {
        $title=config('app.name');
        return $title?$title:$this->defaultErrorTitle;
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorTitle(Throwable $exception): string
    {
        return $this->resolveApplicationTitle();
    }


    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $html=$this->getSubtitleDescription($exception);
        if ($displayErrorDetails) {
            $html .= '<h2>Details</h2>';
            $html .= $this->renderFragmentException($exception);
        } else {
            $html = '<p>'.$this->getErrorDescription($exception).'</p>';
        }

        return $this->renderHtmlBody($this->getErrorTitle($exception), $html);
    }

    protected function getErrorDescription(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getDescription();
        }

        $message=$this->defaultErrorDescription;
        if($exception instanceof QueryException && strstr($exception->getMessage(), 'SQLSTATE['))
        {
            $message='Query or database has error occured';
            preg_match('/SQLSTATE\[(\w+)\](.*)/', $exception->getMessage(), $matches); 
            if(isset($matches[1]))
            {
                if($matches[1]==='08006')
                {
                    $message='A website error has occurred. Failure db connection';
                }
                elseif($matches[1]==='42P01')
                {
                    $message='A website error has occurred. Table or view undefined';
                }
            }
        }
        return $message;
    }

    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     * @return string
     */
    public function getSubtitleDescription(Throwable $exception): string
    {
        $sub=[];
        if($exception instanceof HttpNotFoundException)
        {
            $sub[]='Page request not found';
        }
        elseif($exception instanceof HttpNotImplementedException)
        {
            $sub[]='Page request not implementation';
        }
        elseif($exception instanceof HttpMethodNotAllowedException)
        {
            $sub[]='Method not allowed. The request method is not supported for the requested resource';
        }
        elseif($exception instanceof HttpForbiddenException)
        {
            $sub[]='Access denied for this page or request';
        }
        elseif($exception instanceof HttpUnauthorizedException)
        {
            $sub[]='401 Unauthorized. he request requires valid user authentication';
        }
        elseif($exception instanceof QueryException && strstr($exception->getMessage(), 'SQLSTATE['))
        {
            $message='Query or database has error occured';
            preg_match('/SQLSTATE\[(\w+)\](.*)/', $exception->getMessage(), $matches); 
            if(isset($matches[1]))
            {
                if($matches[1]==='08006')
                {
                    $message='Failure db connection';
                }
                elseif($matches[1]==='42P01')
                {
                    $message='Table or view undefined';
                }
            }
            $sub[]=$message;
        }
        else {
            $sub[]='The application could not run because of the following error';        
        }
        return '<p>'.implode('. ',$sub).'.</p>';
    }


    /**
     * @param Throwable $exception
     * @return string
     */
    protected function renderFragmentException(Throwable $exception): string
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }
}