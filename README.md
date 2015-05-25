php-microframe
==============

Micro PHP M[V]C Framework

## Usage

### /index.php

```php
<?php

$appPath = dirname(__FILE__).'/app';

require_once(dirname(__FILE__).'/microframe/load_web.php');

```

### /config.php

```php
<?php

return array(
    'include'=>array(
        'services/',
    ),
	'urls'=>array(
		'/'=>'main/index',
		'login'=>'auth/login',
		'logout'=>'auth/logout',
	),
	'databases'=>array(
		'default'=>array(
			'dsn'=>'mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=defaultdb',
			'username'=>'myuser',
			'password'=>'mypassword',
		),
	),
    'services'=>array(
        'states'=>array(
            'type'=>'redis',
        ),
        'redis'=>array(
        	'socket'=>'/tmp/redis.sock',
        ),
    ),
);

```

### /controllers/MainController.php

```php
<?php

class MainController extends MFJsonController
{
	
	public function actionIndex()
	{
		return array(
			'name'=>"My App",
			'version'=>'1.0.0',
		);
	}
}

```

##Aknoledgement

A lot of code is based on parts of [Yii Framework](http://www.yiiframework.com/).
