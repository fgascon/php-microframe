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
	'url'=>array(
	
	),
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
