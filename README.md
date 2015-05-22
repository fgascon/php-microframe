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
	'urls'=>array(
		'/'=>'main/index',
	),
	'databases'=>array(
		'default'=>array(
			'dsn'=>'mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=defaultdb',
			'username'=>'myuser',
			'password'=>'mypassword',
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
