# Intoy\HebatApp
Base HomeStead PHP App

## Develop Arsitektur
- OS; Windows_NT x64 6.3.9600
- vsCode 1.52.1
- Git 2.29.2.windows.3
- Node 12.14.1
- PHP 7.4

### Requirements
- PHP 7.4 or higher
- Composer for installation

### Composer Dependensi
> <b>composer require intoy/hebat-app</b>

### <b>Path Config</b>
Path config adalah folder untuk config aplikasi. Didalam folder config hanya terdiri dari file-file configurasi php dengan extension php. Tidak dibenarkan menaruh file-file selain extensi php, karena akan direquire oleh loader app.

### <b>config/app.php</b>
<b>app.php</b> harus ada dalam folder config. Contoh script yang ada dalam file <b>app.php</b> :
```php
<?php
return [
    /**
     * Application name
     */
    'name'=>'App',

    /**
     * Application Instance
     */
    'instance'=>'Hebat Corporation',

    /**
     * Application title
     */
    'title'=>'App - Hebat Corporation',

    /**
     * Application description
     */
    'description'=>'Application Hebat Corporation',

    /**
     * Application version
     */
    'version'=>'0.1',

    /**
     * Development Build
     * development | dev | production  | prod
     */
    'env'=>'dev',    

    /**
     * Cors allow origin
     * Allow all set "*" value
     * Example app response :
     * Access-Control-Allow-Origin : "*"
     */
    "cors_origin"=>null,

    /**
     * Register Timezone
     */
    'timezone'=>'Asia/Makassar',    

    'providers'=>[
        \App\Providers\AuthProvider::class,
    ],
]
```


### <b>config/database.php</b>
contoh script untuk konfigurasi php
```php
<?php
return [    
    "default"=> "postgres",
    'postgres'=>[
        'driver' =>'pgsql',
        "host" =>"localhost",
        'port' => 5432,
        'database' => 'home_stead',
        'username' => 'postgres',
        'password' => 'home_stead_password',
        'charset' => 'utf8', // MySQL = utf8mb4 , Postgres = utf8
        'collation' => 'utf8mb4_unicode_ci', // MySQL = utf8mb4_unicode_ci , Postgres = utf8_unicode_ci
        'timezone'=>'Asia/Makassar',
        'options' => [
            // Turn off persistent connections
            PDO::ATTR_PERSISTENT => false,
            // Enable exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Emulate prepared statements
            PDO::ATTR_EMULATE_PREPARES => true,
            // Set default fetch mode to object
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,              
            // Set character set
            //PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'   // for mysql
        ],
    ],
    'mysql'=>[
        'driver' =>'mysql',
        "host" => "localhost",
        'port' => 3306,
        'database' => 'home_stead_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4', // MySQL = utf8mb4 , Postgres = utf8
        'collation' => 'utf8mb4_unicode_ci', // MySQL = utf8mb4_unicode_ci , Postgres = utf8_unicode_ci
        'options' => [
            // Turn off persistent connections
            PDO::ATTR_PERSISTENT => false,
            // Enable exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Emulate prepared statements
            PDO::ATTR_EMULATE_PREPARES => true,
            // Set default fetch mode to object
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,              
            // Set character set
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'   // for mysql
        ],
    ],

    "oracle"=>[
        'driver'        => 'oracle',
        'tns'           => '',
        'host'          => '192.168.90.201',
        'port'          => '1521',
        'database'      => 'sismiop_homestead',
        'username'      => 'homestead_user',
        'password'      => 'homestead_pass',
        'charset'       => 'AL32UTF8',
        'prefix'        => '',
        'prefix_schema' => '',        
        'options'       =>[
            // Enable exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Set default fetch mode to object
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ],
    ],
];

### <b>config/twig.php</b>
Contoh script configurasi untuk twig
```php
<?php

return [
    // path template twig
    'path'=>path_view(),

    // twig configuration
    'twig'=>[
        'debug' => !is_production(),
        'charset' => 'UTF-8',
        'strict_variables' => false,
        'autoescape' => 'html',
        'cache' => is_production()?path_base("_cache/twig"):false,
        'auto_reload' => null,
    ],
];
```

### <b>config/routes.php</b>
Contoh konfigurasi routing :
```php
<?php

use App\TokenJwt;

return [
    // array route groups
    // [key,path] 
    // key adalah key pada kernel middlewareGroups
    // path ada path group pada route group $app->group($path);
    // key juga akan di require pada folder Routing sesuai dengan key contoh : web.**.php;
    "prefix"=>[
        "web"=>"",
        "api"=>"/api",
        "xall"=>"", // aliasing after
    ],

    // Psr-4 untuk namespace request
    "request"=>"App\\Requests\\",

    // Psr-4 untuk namespace Controllers
    // Anda bisa menggunakan namespace berupa array, dengan catatan key array harus sesuai dengan key prefix
    // Contoh 
    // "controllers"=>[
    //     "web"=>"App\\WebControllers\\"
    //     "api"=>"App\\ApiControllers\\"
    //     "test"=>"App\\TestControllers\\"
    // ],

    // atau bisa juga menggunnakan array array  
    // "controllers"=>[
    //     "web"=>[
    //              "App\\WebControllers\\"
    //              "App\\Web2Controllers\\"
    //            ],
    //     "api"=>"App\\ApiControllers\\"
    //     "test"=>"App\\TestControllers\\"
    // ],    
    "controllers"=>"App\\Controllers\\",
    
    // attribut yang akan diextract oleh middleware yang akan di bind ke parameter queryParams atau parseBody
    "jwt_apply_params"=>[
        "tahun",
        "id_org"
    ],

    //  konfigurasi auth midleware JWTMiddleware
    'jwt'=>[
        'path'=>url_base('api'),
        "ignore"=>[
            url_base("api/report")
        ],
        'leeway'=>60,
        'secret'=>TokenJwt::SECRET_KEY,
        'algorithm'=>'HS256',
        'cookie'=>TokenJwt::JWT_COOKIE, //attribut di cookie
    ],
];
```

### Dekorasi konfigurasi jwt pada <b>config/routes.php</b>
```php
<?php

use App\TokenJwt;
use Intoy\HebatApp\JWTMiddleware\RequestMethodRule;
use Intoy\HebatApp\JWTMiddleware\RequestPathRule;

return [
    ///... your config prevous

    // line jwt config
    //  konfigurasi auth midleware JWTMiddleware
    'jwt'=>[
        'secret'=>TokenJwt::SECRET_KEY, // key secreen
        'algorithm'=>'HS256', // algoritm token JWT secret
        'leeway'=>60, // leeway time JWT
        'cookie'=>TokenJwt::JWT_COOKIE, //attribut di cookie

        /**
         * Path sebaiknya relative terhadap web sub folder
         * Contoh misalnya path perlu pengecekan authentikasi adalah path "api"
         * Dan folder web BERADA di subfolder "my-app" maka path direkomendasikan relative menjadi "my-app/api"
         * Jika web TIDAK BERADA pada sub-folder maka cukup "api" atau "/api"
         */       
        "rules"=>[
            // setup rule METHOD
            new RequestMethodRule([
                "ignore"=>["OPTIONS","GET"], // allows methods 
            ]),
            // setup secure path
            new RequestPathRule([
                "path"=>"api", // secure path
                "ignore"=>[
                    "api/ping", // not secure this path
                    "api/report", // not secure this path
                ]
            ]),
        ]        
    ],
]
```

### <b>config/session.php</b>
Contoh konfigurasi session :
```php
<?php

return [
    // Lax will sent the cookie for cross-domain GET requests
    'cookie_samesite' => 'Lax',

    // Optional: Sent cookie only over https
    'cache_expire'=>60*24, // 1 hari
];

```

### <b>config/logger.php</b>
Contoh konfigurasi logger :
```php
<?php

return [
    'path'=>path_base('_cache'),
];
```

## Development 
Fork / Clone / Github Cli :<br/>
> <b>gh repo clone intoynto/php-hebat-app</b>

SSH :<br/>
> <b>git@github.com:intoynto/php-hebat-app.git</b>
<br/>Use a password-protected SSH key. 


### PHP (local) development
Seperti biasa tetap menggunakan composer.
> composer install