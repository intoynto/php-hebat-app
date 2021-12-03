<?php
declare (strict_types=1);

namespace Intoy\HebatApp\Loaders;

use Intoy\HebatFactory\Loader;
use Intoy\HebatDatabase\DBManager;

class LoaderDatabase extends Loader
{
    public function boot()
    {
        $config=config('database');
        $db=new DBManager($config);
        $db->bootModel();        
        $this->app->bind(DBManager::class,fn()=>$db);
    }
}