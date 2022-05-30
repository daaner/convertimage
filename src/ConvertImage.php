<?php

namespace Daaner\ConvertImage;

use Daaner\ConvertImage\Contracts\ConvertImageInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ConvertImage implements ConvertImageInterface
{

    protected $dev;


    protected int $width;
    protected int $height;
    protected ?string $folderName = '';
    protected ?string $imageName = null;
    protected string $format;
    protected int $quality;
    protected bool $webP;
    protected bool $overwrite;

    /**
     * Удаление оригинала после успешной конвертации
     */
    protected bool $deleteAfterConvert;

    /**
     * Является внешней ссылкой
     */
    protected bool $isHTTP;


    /**
     * Constructor main settings.
     */
    public function __construct()
    {
        $this->dev = config('convert.dev');

        $this->width = config('convert.width');
        $this->height = config('convert.height');
        $this->deleteAfterConvert = config('convert.delete_after_convert');

        $this->format = config('convert.format', 'jpg');
        $this->quality = config('convert.quality', 80);
        $this->webP = config('convert.create_webp', false);
        $this->overwrite = config('convert.overwrite', false);
    }


    /**
     * Обрабатываем изображение
     *
     * @param string $image
     * @return string Return converted image or old image
     */
    public function convert(string $image): string
    {
        $this->isExternal($image);

        /**
         * Если внешний линк, возвращаем что давали
         */
        if (!config('convert.convert_external_url') && $this->isHTTP) {
            Log::info('ConvertImage. Not allowed converted external file (config): ' . $image);
            return $image;
        }

        $image = $this->clearLink($image);
        $newImagePath = $this->checkFolder($this->folderName);
        $imageName = $this->getName($image);



        $file = $this->getFile($image);

        /**
         * Файл не получен, возвращаем что давали
         */
        if (!$file) {
            return $image;
        }


        $link = $this->getNewName($newImagePath, $imageName);
        $image_url = Str::replace('//', '/', public_path() . '/' . $link);


        $bgTransparentColor = config('convert.bg_color', '#ffffff');
        $resize = config('convert.resize', false);

        $status = false;

        try {
            $img = Image::make($file);
            $jpg = Image::canvas($img->width(), $img->height(), $bgTransparentColor);
            $jpg->insert($img);

            if ($resize) {
                $jpg->resize($this->width, $this->height, function ($constraint) {
                    if (config('convert.aspect_ratio'))
                    {
                        $constraint->aspectRatio();
                    }

                    if (config('convert.upsize'))
                    {
                        $constraint->upsize();
                    }
                });
            }

            $status = $jpg->save($image_url, $this->quality, $this->format);

        } catch (Exception $e) {
            Log::info('ConvertImage. Cannot save converted image: ' . $link);
            Log::info($e->getMessage());
            $image_url = $image;
        }


        /** create WebP */
        $webP = false;
        if ($this->webP && $status) {

            try {
                $webP = $this->webpImage($image_url);
            } catch (Exception $e) {
                Log::info('ConvertImage. Cannot create webP image: ' . $link);
                Log::info($e->getMessage());
            }

        }

        $deletedOriginal = $this->deletedOriginal($image, $link, (bool) $status);


        if ($this->dev) {

            $logData = "\n" . 'original: ' . $image;
            $logData .= "\n" . 'status: ' . ($status ? 'true' : 'false');
            $logData .= "\n" . 'converted (return): ' . $link;
            $logData .= "\n" . 'overwrite file: ' . ($this->overwrite ? 'true' : 'false');
            $logData .= "\n" . 'deleted original file: ' . ($deletedOriginal ? 'true' : 'false');
            $logData .= "\n" . 'width: ' . $this->width;
            $logData .= "\n" . 'height: ' . $this->height;
            $logData .= "\n" . 'format: ' . $this->format;
            $logData .= "\n" . 'quality: ' . $this->quality;

            $logData .= "\n" . 'isHTTP: ' . ($this->isHTTP ? 'true' : 'false');
            $logData .= "\n" . 'deleteAfterConvert: ' . ($this->deleteAfterConvert ? 'true' : 'false');

            $logData .= "\n" . 'create webP: ' . ($webP ? 'true' : 'false');

            Log::info('ConvertImage. Dev mode ->' . $logData);
        }


        return $status ? $link : $image_url;
    }


    /**
     * Изменение директории сохранения относительно config('convert.dir')
     *
     * @param string $name
     * @return ConvertImage
     */
    public function setFolder(string $name): ConvertImage
    {
        $this->folderName = Str::replace('//', '/', $name);

        return $this;
    }


    /**
     * Установка размеров результата
     *
     * @param int $width
     * @param int $height
     * @return ConvertImage
     */
    public function resize(int $width, int $height): ConvertImage
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }


    /**
     * Удаление оригинала после успешной конвертации.
     * Если изображение по внешней ссылки - удаления не произойдет
     *
     * @param bool $marker
     * @return ConvertImage
     */
    public function deleteAfter(bool $marker): ConvertImage
    {
        $this->deleteAfterConvert = $marker;

        return $this;
    }


    /**
     * Устанавливаем имя для файла
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): ConvertImage
    {
        $this->imageName = Str::slug($name, '-');

        return $this;
    }


    /**
     * Изменение формата изображения (JPG, PNG), отличного от дефолтного в конфиге
     *
     * @param string $format
     * @return ConvertImage
     */
    public function setFormat(string $format): ConvertImage
    {
        $this->format = $format;

        return $this;
    }


    /**
     * Изменение качества изображения, отличного от дефолтного в конфиге
     *
     * @param int $quality
     * @return ConvertImage
     */
    public function setQuality(int $quality): ConvertImage
    {
        $this->quality = $quality;

        if ($this->quality > 100) {
            $this->quality = 100;
        }

        return $this;
    }


    /**
     * Создание WebP, отличного от дефолтного в конфиге
     *
     * @param bool $webp
     * @return ConvertImage
     */
    public function createWebP(bool $webp): ConvertImage
    {
        $this->webP = $webp;

        return $this;
    }


    /**
     * Создание WebP, отличного от дефолтного в конфиге
     *
     * @param bool $overwrite
     * @return ConvertImage
     */
    public function forceOverwrite(bool $overwrite): ConvertImage
    {
        $this->overwrite = $overwrite;

        return $this;
    }




    /**
     * Получаем новое имя файла.
     * Если имя не указано - получаем хеш имени или имя файла без расширения
     *
     * @param string $image
     * @return string
     */
    protected function getName(string $image): string
    {

        if ($this->imageName) {
            $newName = $this->imageName;
        } else {

            try {
                $name = pathinfo($image, PATHINFO_FILENAME);
            } catch (Exception $e) {
                $name = rand(100,999) . '-' . Str::replace('.', '', strval(microtime(true)));

                if ($this->dev) {
                    Log::info('ConvertImage. Error get image name: ' . $image);
                    Log::info($e->getMessage());
                }
            }

            if (config('convert.save_original_name')) {
                $newName = $name;
            } else {
                $newName = md5($name);
            }
        }

        return $newName;
    }


    /**
     * Удаляем оригинал файла
     *
     * @param string $source
     * @param string $link
     * @param bool $status
     * @return bool
     */
    protected function deletedOriginal(string $source, string $link, bool $status): bool
    {
        if ($status && $this->deleteAfterConvert) {
            // Сохранили файл на место оригинала
            if ($link == $source)
            {
                $this->deleteAfterConvert = false;
                return false;
            }

            try {
                unlink($source);
                return true;
            } catch (Exception $e) {
                try {
                    unlink(public_path() . $source);
                    return true;
                } catch (Exception $e) {
                    Log::info('ConvertImage. Error DELETE original image:' . $source);
                }
            }
        }

        return false;
    }


    /**
     * Конвертируем в webP
     *
     * @param string $source
     * @return bool
     */
    protected function webpImage(string $source): bool
    {
        if (!extension_loaded('gd'))
        {
            Log::info('ConvertImage. PHP extension GD missed, image not converted: ' . $source);
            return false;
        }

        $dir = pathinfo($source, PATHINFO_DIRNAME);
        $name = pathinfo($source, PATHINFO_FILENAME) . '.' . $this->format;
        $destination = $dir . DIRECTORY_SEPARATOR . $name . '.webp';
        $info = getimagesize($source);
        $isAlpha = false;

        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($source);
        elseif ($isAlpha = $info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source);
        } elseif ($isAlpha = $info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } else {
            Log::info('ConvertImage. Not supported mime for convert webP: ' . $source);
            return false;
        }

        if ($isAlpha) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        imagewebp($image, $destination, $this->quality);

        return true;
    }


    /**
     * Очищаем параметры URL, если указано в конфиге.
     * Но не чистим, если изображение из внешнего источника.
     *
     * @param string $link
     * @return string
     */
    protected function clearLink(string $link): string
    {
        if (config('convert.clear_url_parameter') && !$this->isHTTP) {
            // Проверка с отсечением
            if (stristr($link, '?', true)) {
                $link = stristr($link, '?', true);
            }
        }

        return $link;
    }


    /**
     * Проверяем, является ли ссылка внешней.
     * Внешняя ссылка не будет удаляться
     *
     * @param string $link
     * @return ConvertImage
     */
    protected function isExternal(string $link): ConvertImage
    {
        $this->isHTTP = true;

        if (stripos($link, 'http', 0) === false) {
            $this->isHTTP = false;
        } else {
            $this->deleteAfterConvert = false;
        }

        return $this;
    }


    /**
     * Проверка на наличие папки
     * или папку создаем
     *
     * @param string|null $folder
     * @return string
     */
    protected function checkFolder(?string $folder): string
    {

        $imagePath = config('convert.dir');

        if ($folder) {
            $imagePath .= '/' . $folder;
        }

        // Замена двух слешей на один
        $imagePath = Str::replace('//', '/', $imagePath);

        if (!is_dir(public_path() . $imagePath)) {
            try {
                mkdir(public_path() . $imagePath, config('convert.dir_permission'), config('convert.dir_recursive'));
                if ($this->dev) {
                    Log::info('ConvertImage. Create folder for convert image: ' . $imagePath);
                }
            } catch (Exception $e) {
                Log::critical('ConvertImage. ERROR: Can not create folder: ' . $imagePath);
                $imagePath = config('convert.dir_temp');
            }
        }

        return $imagePath;
    }


    /**
     * @param string $link
     * @return false|string
     */
    protected function getFile(string $link)
    {
        $file = false;

        if ($this->isHTTP) {
            try {
                $getFile = Http::timeout(config('convert.http_response_timeout', 3))
                    ->withOptions([
                        'verify' => config('convert.http_option_verify'),
                    ])
                    ->retry(config('convert.http_retry_max_time', 2), config('convert.http_retry_delay', 100))
                    ->get($link);

                if ($getFile->successful()) {
                    $file = $getFile->body();
                } else {
                    Log::info('ConvertImage. Image HTTP read error: ' . $link);
                    return false;
                }
            } catch (Exception $e) {
                Log::info('ConvertImage. Error HTTP get image: ' . $link);
                Log::info($e->getMessage());
                return false;
            }
        } else {
            $imageLink = Str::replace('//', '/', public_path() . '/' . $link);

            try {
                $file = file_get_contents($imageLink);
            } catch (Exception $e) {
                Log::info('ConvertImage. Image read (local) error: ' . $link);
                Log::info($e->getMessage());
                return false;
            }
        }

        return $file;

    }


    /**
     * @param string $path
     * @param string $name
     * @return string
     */
    protected function getNewName(string $path, string $name): string
    {
        $link = $path . '/' . $name . '.' . $this->format;

        if (file_exists(public_path() . '/' . $link))
        {
            if ($this->overwrite)
            {
                // файл есть, разрешена перезапись и права на перезапись есть
                if(is_writable(public_path() . '/' . $link))
                {
                    return $link;
                }
            }

            // Файл есть, перезапись не разрешена или нет прав.
            // Меняем имя файла
            Log::info('ConvertImage. Change name for overwrite: ' . $link);
            $newName = $name . '-' . Str::replace('.', '', strval(microtime(true)));
            $link = $path . '/' . $newName . '.' . $this->format;
        }


        return $link;
    }
}
