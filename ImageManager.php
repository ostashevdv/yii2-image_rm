<?php
/**
 * Created by Ostashev Dmitriy <ostashevdv@gmail.com>
 * Date: 12.01.2015 Time: 18:51
 * -------------------------------------------------------------
 */

namespace ostashevdv\image;


use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class ImageManager extends Component
{
    public $driver = 'imagick';

    public $cachePath = '@web/assets/thumbs/';

    /**
     * Initiates an Image instance from different input types
     *
     * @param  mixed $data
     *
     * @return \Intervention\Image\Image
     */
    public function make($data)
    {
        return $this->createDriver()->init($data);
    }

    /**
     * Creates an empty image canvas
     *
     * @param  integer $width
     * @param  integer $height
     * @param  mixed $background
     *
     * @return \Intervention\Image\Image
     */
    public function canvas($width, $height, $background = null)
    {
        return $this->createDriver()->newImage($width, $height, $background);
    }

    /**
     * Creates a driver instance according to config settings
     *
     * @return \Intervention\Image\AbstractDriver
     */
    private function createDriver()
    {
        $drivername = ucfirst($this->driver);
        $driverclass = sprintf('Intervention\\Image\\%s\\Driver', $drivername);

        if (class_exists($driverclass)) {
            return new $driverclass;
        }

        throw new \Intervention\Image\Exception\NotSupportedException(
            "Driver ({$drivername}) could not be instantiated."
        );
    }

    /**
     * Create thumbnail or return src if thumb already created
     *
     * @param mixed $url
     * @param integer $width
     * @param integer $height
     * @param null|string $cacheDir
     * @return null|string
     */
    public function thumb($url, $width, $height, $cacheDir=null)
    {
        if ($cacheDir===null) {
            $cacheDir = $this->cachePath;
        }

        if (parse_url($url, PHP_URL_HOST) == null) {
            $url = Yii::$app->homeUrl.'/'.ltrim($url,'/');
        }

        $urlNorm = new Normalize($url);
        $url = $urlNorm->normalize();

        $dest['name'] = md5($url)."[{$width}x{$height}].".pathinfo($url, PATHINFO_EXTENSION);
        $dest['dir'] = Yii::getAlias($cacheDir).'/'.date("m").'/';


        $dest['path'] = FileHelper::normalizePath(Yii::getAlias('@webroot').DIRECTORY_SEPARATOR.$dest['dir']);

        if (!file_exists($dest['path'].DIRECTORY_SEPARATOR.$dest['name'])) {
            try {
                FileHelper::createDirectory($dest['path']);
                $this->make($url)->fit($width, $height)->save($dest['path'].DIRECTORY_SEPARATOR.$dest['name']);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $dest['dir'].$dest['name'];
    }
} 