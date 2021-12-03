<?php

declare (strict_types=1);

namespace Intoy\HebatApp;

final class ClassFinder
{
    private static $composer=null;

    public function __construct()
    {
        self::$composer=null;
        self::$composer=require path_base("vendor/autoload.php");
    }

    public function getComposer()
    {
        return self::$composer;
    }


    protected function splitNameSpace($nameSpace)
    {
        $split=explode("\\",$nameSpace);
        $main=array_shift($split);
        return [$main."\\",$split];
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

        $dir=array_shift(static::$composer->getPrefixesPsr4()[$main]);
        $scanDir=$dir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$subNameSpaceDirs).DIRECTORY_SEPARATOR;
        $joinNameSpace=implode("\\",$subNameSpaceDirs);
        $classes=[];
        if(is_dir($scanDir))
        {
            $folder = scandir($scanDir);
            $config_files = array_slice($folder, 2, count($folder));
            foreach($config_files as $file)           
            {
                $fullNameSpace=$main.$joinNameSpace."\\".str_replace('.php', '', $file);
                if(class_exists($fullNameSpace))
                {
                    $classes[]=$fullNameSpace;
                }
            }
        }
        return $classes;
    }
}