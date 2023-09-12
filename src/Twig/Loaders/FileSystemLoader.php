<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Twig\Loaders;

use Exception;
use Twig\Loader\FilesystemLoader as BaseFilterSystemLoader;
use Twig\Error\LoaderError;

class FileSystemLoader extends BaseFilterSystemLoader
{
    protected $rootPath;

    /**
     * @param string|array $paths    A path or an array of paths where to look for templates
     * @param string|null  $rootPath The root path common to all relative paths (null for getcwd())
     */
    public function __construct($paths = [], string $rootPath = null)
    {
        $this->rootPath = (null === $rootPath ? getcwd() : $rootPath).\DIRECTORY_SEPARATOR;
        if (null !== $rootPath && false !== ($realPath = realpath($rootPath))) {
            $this->rootPath = $realPath.\DIRECTORY_SEPARATOR;
        }

        if ($paths) {
            $this->setPaths($paths);
        }
    }

    /**
     * @return string|null
     * @throws LoaderError|Exception
     */
    protected function findTemplate(string $name, bool $throw = true)
    {
        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->errorCache[$name])) {
            if (!$throw) {
                return null;
            }

            throw new LoaderError($this->errorCache[$name]);
        }

        try {
            list($namespace, $shortname) = $this->parseName($name);

            $this->validateName($shortname);
        } catch (LoaderError $e) {
            if (!$throw) {
                return null;
            }

            throw $e;
        }

        if (!isset($this->paths[$namespace])) {
            $this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

            if (!$throw) {
                return null;
            }

            throw new LoaderError($this->errorCache[$name]);
        }

        foreach ($this->paths[$namespace] as $path) {
            if (!$this->isAbsolutePath($path)) {
                $path = $this->rootPath.$path;
            }

            $go_shortname=$this->findShortName($path,$shortname);

            if($go_shortname)
            {
                if (false !== $realpath = realpath($path.'/'.$go_shortname)) {
                    return $this->cache[$name] = $realpath;
                }

                return $this->cache[$name] = $path.'/'.$go_shortname;
            }
        }

        $this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths[$namespace]));

        if (!$throw) {
            return null;
        }

        throw new LoaderError($this->errorCache[$name]);
    }

    /**
     * @var string $path
     * @var string|null $shortname
     */
    protected function findShortName($path,$shortname)
    {
        if(is_file($path.'/'.$shortname)) return $shortname;
        else 
        {
            $shortname=(string)$shortname;
            $extensions=['twig','html','htm'];
            foreach($extensions as $ext)
            {
                if(is_file($path.'/'.$shortname.'.'.$ext)) return $shortname.'.'.$ext;
            }

            return null;
        }
    }
    
    protected function isAbsolutePath(string $file): bool
    {
        return strspn($file, '/\\', 0, 1)
            || (\strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, \PHP_URL_SCHEME)
        ;
    }

    protected function normalizeName(string $name): string
    {
        return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
    }


    protected function parseName(string $name, string $default = self::MAIN_NAMESPACE): array
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }

            $namespace = substr($name, 1, $pos - 1);
            $shortname = substr($name, $pos + 1);

            return [$namespace, $shortname];
        }

        return [$default, $name];
    }

    protected function validateName(string $name): void
    {
        if (false !== strpos($name, "\0")) {
            throw new LoaderError('A template name cannot contain NUL bytes.');
        }

        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }
    }
}