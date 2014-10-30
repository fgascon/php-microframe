php-microframe
==============

Micro PHP M[V]C Framework

## Usage

### /index.php

```php
<?php

$appPath = dirname(__FILE__);

require_once $appPath.'/microframe/MF.php';

MF::init($appPath);

```

### /config.php

```php
<?php

return array(
	'databases'=>array(
		'default'=>array(
			'host'=>'127.0.0.1',
			'username'=>'myuser',
			'password'=>'mypassword',
			'dbname'=>'defaultdb',
		),
	),
);

```

### /controllers/MainController.php

```php
<?php

class MainController extends MFController
{
	
	public function index()
	{
		return array(
			'name'=>"My App",
			'version'=>'1.0.0',
		);
	}
}

```
