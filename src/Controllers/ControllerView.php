<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;

class ControllerView extends Controller
{
    /**
     * @var Twig
     */
    protected $view;

    /**
     * @var array $data push into view
     */
    protected $data=[];

    protected function onCreated()
    {
        $this->view=$this->container->get(Twig::class);
    }

    protected function view(Response $response, string $template, array $data=[]):Response
    {
        $response->getBody()->write($this->view->fetch($template,$data));
        return $response;
    }
}