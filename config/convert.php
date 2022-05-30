<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Папка для сохранения по умолчанию
    |--------------------------------------------------------------------------
    |
    | Для изображений, куда будет сохранение при невозможности создать папку.
    | Права на папку.
    */
    'dir' => '/images',
    'dir_temp' => '/images/temp',
    'dir_permission' => 0777,
    'dir_recursive' => true,


    /*
    |--------------------------------------------------------------------------
    | Базовые настройки
    |--------------------------------------------------------------------------
    |
    | convert_external_url - проверяет что файл не внешний, и если внешний, не конвертирует
    | create_webp - создает дополнительно `[name].[ext].webp`
    | delete_after_convert - удаление оригинального изображения после конвертации
    | clear_url_parameter - удаление параметров в ссылке (только, если она не внешняя)
    | save_original_name - сохраняет оригинальное имя и не хеширует его (если не указано имя через `setName`)
    |
    | resize - изменение размеров
    | aspect_ratio - сохранение пропорций при изменении размера
    | upsize - не увеличивать мелкие изображения
    |
    | width - ширина изображения по умолчанию
    | height - высота изображения по умолчанию
    | quality - качество изображения по умолчанию
    | format - расширение изображения по умолчанию (поддерживаемые https://image.intervention.io/v2/introduction/formats)
    | bg_color - цвет фона при конвертировании изображений с прозрачностью
    | overwrite - позволяет перезаписать конвертированный файл, если такой уже имеется
    |
    */

    'convert_external_url' => true,
    'create_webp' => false,
    'delete_after_convert' => false,
    'clear_url_parameter' => true,
    'save_original_name' => true,
    'resize' => false,
    'aspect_ratio' => true,
    'upsize' => true,
    'overwrite' => false,

    'width' => 1200,
    'height' => 800,
    'quality' => 80,
    'format' => 'jpg',
    'bg_color' => '#ffffff',


    /*
    |--------------------------------------------------------------------------
    | Режим отладки
    |--------------------------------------------------------------------------
    |
    | Логирует создание папок и данные конвертации.
    */
    'dev' => (bool) env('APP_DEBUG', false),



    /*
    | Тонкая настройка HTTP client.
    |
    | http_response_timeout - maximum number of seconds to wait for a response
    | http_retry_max_time - the maximum number of times the request should be attempted
    | http_retry_delay - the number of milliseconds that Laravel should wait in between attempts
    */
    'http_response_timeout' => 3,
    'http_retry_max_time' => 2,
    'http_retry_delay' => 200,
    'http_option_verify' => false,

];
