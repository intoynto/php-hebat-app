<?php

declare (strict_types=1);

namespace Intoy\HebatApp;

final class ClassFinder
{
    private static $files=null;

    public function __construct()
    {
        self::$files=null;
        $file='vendor/composer/autoload_psr4.php';
        $vendorFile=null;
        $paths=[path_vendor($file),path_base($file)];
        foreach($paths as $path)
        {
            if(file_exists($path))
            {
                $vendorFile=$path;
                break;
            }
        }

        if(!$vendorFile)
        {
            throw new \Exception('Can\'t resolve autoload from config vendor');
        }
        
        self::$files=require $vendorFile;
    }

    public function getFiles()
    {
        return self::$files;
    }


    protected function splitNameSpace($nameSpace)
    {
        $split=explode('\\',$nameSpace);
        if(\end($split)==='\\' || \end($split)==='')
        {
            array_pop($split);
        }
        $main=array_shift($split);
        return [$main.'\\',$split];
    }

    /**
     * Get classes in directory by autoload prefixDirsPsr4
     * @param string $prefixDirPsr4Key contoh "App\\" atau "Api\\" atau "Domain\\"
     * @return string[] of fullNameSpaces by entry class in directory
     */
    public function getClassesInNameSpaces($prefixDirPsr4Key)
    {
        if(!$prefixDirPsr4Key) return [];

        [$main,$subNameSpaceDirs]=$this->splitNameSpace($prefixDirPsr4Key);
        $files=static::$files;
        $dir=array_shift($files[$main]);
        $joinNameSpace=implode('\\',$subNameSpaceDirs);
        $classes=[];
        $scanDir=$dir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$subNameSpaceDirs).DIRECTORY_SEPARATOR;
        if(is_dir($scanDir))
        {
            $folder = scandir($scanDir);
            $config_files = array_slice($folder, 2, count($folder));
            foreach($config_files as $file)           
            {
                $fullNameSpace=$main.$joinNameSpace.'\\'.str_replace('.php', '', $file);
                if(class_exists($fullNameSpace))
                {
                    $classes[]=$fullNameSpace;
                }
            }
        }

        return $classes;
    }
}