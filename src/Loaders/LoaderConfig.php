<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Intoy\HebatFactory\Loader;
use Intoy\HebatSupport\Str;

class LoaderConfig extends Loader
{
    public function boot()
    {
        $config = [];
        $config_folder=path_config();
        
        if(is_dir($config_folder))
        {
            $folder = scandir(path_config());
            $config_files = array_slice($folder, 2, count($folder));
            $settings=null;
            foreach($config_files as $file)
            {
                throw_when(Str::after($file,'.')!=='php','Config files must be .php files');
                $require_config=require path_config($file);
                if(!$settings && $require_config && $file==='settings.php')
                {
                    $settings=$require_config;
                }
                //set in data
                data_set($config,Str::before($file,'.php'),$require_config);
            }

            $this->app->bind('config',$config); // config into container
            if($settings)
            {
                $this->app->bind('settings',$settings); // settings into container
            }
        }
        
        //set base path app
        $this->app->setBasePath(url_base());
        // set time zone
        $timeZone=config("app.timezone")??"Asia/Makassar";
        date_default_timezone_set($timeZone);
    }
}