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
     * Application title
     */
    'instansi'=>'Hebat Corporation',

    /**
     * Application title
     */
    'title'=>'App - Hebat Corporation',


    /**
     * Application version
     */
    'version'=>'0.1',

    /**
     * Development Build
     * development | production
     */
    'env'=>'development',

    /**
     * Nama cookie untuk jwt yang akan di bind ke cookie browser
     */
    "jwt_cookie"=>\App\TokenJwt::JWT_COOKIE,


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
];
```


### <b>config/requests.php</b>
Konfigurasi untuk namespace atau file-file request bind.
Contoh script <b>requests.php</b>

```php
<?php

return [
    "web"=>[
        "namespace"=>"App\\Requests",
    ],
    "api"=>[
        "namespace"=>"App\\Requests",
    ],
];
```

contoh jika menggunakan array configurasi request :
```php
<?php

return [
    "web"=>[
        "namespace"=>"App\\Requests",
    ],
    "api"=>[
        "namespace"=>"App\\Requests",
    ],
];
```
namespace hanya alias untuk loader mencari request class yang sesuai ketika Container ketika harus mencari class yang dituju


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
    'controllers'=>[
        'web'=>[
            '\\App\\Controllers\\',
        ],
        'api'=>[
            '\\App\\Controllers\\',
        ],
    ],
    'path'=>[
        'api'=>'',
        'web'=>'',
    ],
    'prefix'=>[
        'api'=>'/api',
        'web'=>'',
    ],
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