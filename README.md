# Laravel ConvertImage

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/daaner/convertimage/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/daaner/novaposhta/?branch=master)
[![Laravel Support](https://img.shields.io/badge/Laravel-7+-brightgreen.svg)]()
[![PHP Support](https://img.shields.io/badge/PHP-7.4+-brightgreen.svg)]()

[![Latest Stable Version](https://poser.pugx.org/daaner/convertimage/v)](//packagist.org/packages/daaner/convertimage)
[![Total Downloads](https://poser.pugx.org/daaner/convertimage/downloads)](//packagist.org/packages/daaner/convertimage)
[![License](https://poser.pugx.org/daaner/convertimage/license)](//packagist.org/packages/daaner/convertimage)


Удобный пакет для конвертирования изображений в нужную папку для Laravel 7+



## Install

```bash
composer require daaner/convertimage
```

Добавьте в шапку
`use Daaner\ConvertImage\ConvertImage;`

Выполните публикацию конфига командой:
``` bash
php artisan vendor:publish --provider="Daaner\ConvertImage\ConvertImageServiceProvider"
```



## Instruction

Передаете изображение - возвращается путь к конвертируемому.
Для удобства есть некоторые методы API

```php
$serv = new ConvertImage;
$output = $serv->convert('/images/foobar.jpg?12345');
dd($output);
```

### setFolder `(string | callback)`

Устанавливает папку для сохранения, относительно значения в конфиге.
При отсутствии папки - создаст ее

```php
$serv = new ConvertImage;
$serv->setFolder('222');
$serv->convert('/images/foobar.jpg?12345');

// config - 'dir' => '/images',
// output /image/222/foobar.jpg
```

### deleteAfter `(bool)`

Позволяет игнорировать конфиг в частном случае.
При изображении из внешнего источника (начинается на http) - удаление оригинала не произойдет.

```php
$serv = new ConvertImage;
$serv->deleteAfter(true);
$serv->convert('/images/foobar.jpg?12345');

// config - 'delete_after_convert' => false,
// output оригинал будет удален
```


### setName `(string | callback)`

Установка имени будущего файла

```php
$serv = new ConvertImage;
$serv->setName('foo baz bar 1');
$serv->convert('/images/foobar.jpg?12345');

// foo-baz-bar-1.jpg
```

### resize `(int $width, int $height)`

Изменение размеров изображения отличного от дефолтного

```php
$serv = new ConvertImage;
$serv->resize(600, 800);
$serv->convert('/images/foobar.jpg?12345');

// config - 'width' => 1200
// config - 'height' => 800
// output 'width' => 600
// output 'height' => 800
```

### setQuality `(int $quality)`

Изменение качества изображения отличного от дефолтного

```php
$serv = new ConvertImage;
$serv->setQuality(50);
$serv->convert('/images/foobar.jpg?12345');

// config - 'quality' => 80
// output 'quality' => 50
```


### createWebP `(bool $create)`

Форсированное создание webP не учитывая значения конфига.
!!! НЕ будет создаваться, если файл внешний и не указана опция `convert_external_url` 

```php
$serv = new ConvertImage;
$serv->createWebP(true);
$serv->convert('/images/foobar.jpg?12345');

// config - 'create_webp' => false
// output 'create_webp' => true
```


### setFormat `(string)`

Изменение формата изображения отличного от дефолтного.
Поддерживаемые форматы [тут](https://image.intervention.io/v2/introduction/formats)

```php
$serv = new ConvertImage;
$serv->setFormat('gif');
$serv->convert('/images/foobar.jpg?12345');

// config - 'format' => 'jpg'
// output file *.gif
```

### forceOverwrite `(bool)`

Перезаписывает файл, если таковой уже имеется.
Если отключено - при наличии файла, создает новый и добавляет метку времени к названию.
Не относится к webP. Изображение webP ВСЕГДА имеет такое же имя, как и файл после обработки

```php
$serv = new ConvertImage;
$serv->forceOverwrite(true);
$serv->convert('/images/foobar.jpg?12345');

// config - 'overwrite' => false
// output overwrite converted file if isset

$serv = new ConvertImage;
$serv->forceOverwrite(false);
$serv->setName('isset-foo-bar');
$serv->convert('/images/foobar.jpg?12345');

// config - 'overwrite' => false
// isset file isset-foo-bar.jpg
// output file isset-foo-bar-1234567.jpg
```





## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits
- [Daan](https://github.com/daaner)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
