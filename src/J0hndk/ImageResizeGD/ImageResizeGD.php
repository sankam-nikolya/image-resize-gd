<?php

/*!
* hi@j0hn.dk
* No copyrights. Feel free to use this the way you like.
*/

namespace J0hndk\ImageResizeGD;

class ImageResizeGD {

    /**
     * @var resource
     */
    private $image;
    /**
     * @var resource
     */
    private $imageModified;

    /**
     * @var int
     */
    private $sourceWidth;
    /**
     * @var int
     */
    private $sourceHeight;

    /**
     * @var int
     */
    private $sourceType;

    /**
     * @var int
     */
    private $newWidth;
    /**
     * @var int
     */
    private $newHeight;

    /**
     * @var int
     */
    private $defaultJPEGCompression;

    /**
     * @var int
     */
    private $defaultPNGCompression;

    /**
     * @var array
     */
    private $allowedImageTypes = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG);


    /**
     * Image resizing and compressing utility.
     *
     * @author hi@j0hn.dk
     *
     * @param string $imagePath Path to the source image
     * @param int $defaultJPEGCompression integer <0; 100>
     * @param int $defaultPNGCompression integer <0; 9>
     *
     */
    function __construct($imagePath, $defaultJPEGCompression = 80, $defaultPNGCompression = 9) {
        $this->image = $this->openImageFile($imagePath);

        $this->setDefaultJPEGCompression($defaultJPEGCompression);
        $this->setDefaultPNGCompression($defaultPNGCompression);

        $this->sourceWidth = imagesx($this->image);
        $this->sourceHeight = imagesy($this->image);

        if($this->sourceWidth === false || $this->sourceHeight === false) {
            throw new \InvalidArgumentException('Image type is not supported or file is currupted.');
        }
    }

    /**
     * Resize image, so the output does not exceed given width and height.
     * Smaller image will be upscaled.
     * Method maintains the aspect ratio.
     *
     * @param int $newWidth Desired width of the output image
     * @param int $newHeight Desired height of the output image
     * @return void
     */
    public function resizeWithinDimensions($newWidth, $newHeight) {
        if ($newWidth === $this->sourceWidth && $newHeight === $this->sourceHeight) {
            $this->copyImageDataWithoutResampling();
            $this->newWidth = $this->sourceWidth;
            $this->newHeight = $this->sourceHeight;
            return;
        }
        $widthRatio  = $this->sourceWidth / $newWidth;
        $heightRatio = $this->sourceHeight / $newHeight;

        if ($widthRatio > $heightRatio) {
            $this->resizeByWidth($newWidth);
        } else {
            $this->resizeByHeight($newHeight);
        }
    }

    /**
     * Resize image, so the output gets exactly given width.
     * Height will be scaled maintaining the aspect ratio.
     *
     * @param int $newWidth Desired width of the output image
     * @return void
     */
    public function resizeByWidth($newWidth) {
        if ($newWidth === $this->sourceWidth) {
            $this->copyImageDataWithoutResampling();
            $this->newWidth = $this->sourceWidth;
            $this->newHeight = $this->sourceHeight;
            return;
        }
        $this->newWidth = $newWidth;

        $ratio = $this->sourceHeight / $this->sourceWidth;
        $this->newHeight = $ratio * $newWidth;

        $this->copyImageData();
    }

    /**
     * Resize image, so the output gets exactly given height.
     * Width will be scaled maintaining the aspect ratio.
     *
     * @param int $newHeight Desired height of the output image
     * @return void
     */
    public function resizeByHeight($newHeight) {
        if ($newHeight === $this->sourceHeight) {
            $this->copyImageDataWithoutResampling();
            $this->newWidth = $this->sourceWidth;
            $this->newHeight = $this->sourceHeight;
            return;
        }
        $this->newHeight = $newHeight;

        $ratio = $this->sourceWidth / $this->sourceHeight;
        $this->newWidth = $ratio * $newHeight;

        $this->copyImageData();
    }

    /**
     * Resize image, so the output matches exactly given dimensions.
     * Image is scaled, centered and cropped.
     *
     * @param int $newWidth Desired width of the output image
     * @param int $newHeight Desired height of the output image
     * @return void
     */
    public function resizeToFillDimensionsExactly($newWidth, $newHeight) {
        if ($newWidth === $this->sourceWidth && $newHeight === $this->sourceHeight) {
            $this->copyImageDataWithoutResampling();
            $this->newWidth = $this->sourceWidth;
            $this->newHeight = $this->sourceHeight;
            return;
        }
        $widthRatio  = $this->sourceWidth / $newWidth;
        $heightRatio = $this->sourceHeight / $newHeight;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $this->newWidth  = $this->sourceWidth / $optimalRatio;
        $this->newHeight = $this->sourceHeight / $optimalRatio;

        $this->copyImageData();

        $cropStartX = ($this->newWidth / 2) - ($newWidth / 2);
        $cropStartY = ($this->newHeight / 2) - ($newHeight / 2);

        $imageCropped = $this->imageModified;

        $this->imageModified = imagecreatetruecolor($newWidth , $newHeight);

        imagealphablending($this->imageModified, false);
        imagesavealpha($this->imageModified, true);

        imagecopy($this->imageModified, $imageCropped , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight);

        $this->newWidth = $newWidth;
        $this->newHeight = $newHeight;
    }

    /**
     * Saves image after modifications, or a copy if no modifications were done.
     * Allows adding a solid color background to transparent images.
     * Only first two parameters are required. By default output will be saved as the same type as source.
     *
     * @param string $saveImageName Name of the output file. Do not use extension here, it will be added based on $extension parameter.
     * @param int $quality Should be value <0; 100> for IMAGETYPE_JPEG, <0; 9> for IMAGETYPE_PNG
     * @param int $extension Desired output format. Should be one of php predefined constants: IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
     * @param string|null $backgroundColor Should be a hexadecimal RGB color value without hash, like FF0000 or 090909
     * @return string Name of saved output file with extension. For example foo.jpg
     * @throws Exception When installed gd library does not support desired $extension
     * @throws Exception When image could not be saved
     * @throws InvalidArgumentException When $extension is not a value of any of those constants: IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
     *
     */
    public function saveImageFile($saveImageName, $extension = null, $quality = null, $backgroundColor = null) {
        if($this->imageModified === null) {
            $this->copyImageDataWithoutResampling();
        }

        if($extension !== null) {
            if(!in_array($extension, $this->allowedImageTypes)) {
                throw new \InvalidArgumentException('This image type is not supported.');
            }
        } else {
            $extension = $this->sourceType;
        }

        if($backgroundColor !== null) {
            $this->addBackgroundColor($backgroundColor);
        }

        if($quality < 0) {
            $quality = 0;
        }

        if($quality > 100 && $extension === IMAGETYPE_JPEG) {
            $quality = 100;
        }

        if($quality > 9 && $extension === IMAGETYPE_PNG) {
            $quality = 9;
        }

        switch($extension) {
            case IMAGETYPE_GIF:
                if (imagetypes() & IMG_GIF) {
                    $imageFile = $saveImageName.'.gif';
                    $imageResult = imagegif($this->imageModified, $imageFile);
                } else {
                    throw new \Exception('Your GD library does not support png image types.');
                }
                break;
            case IMAGETYPE_JPEG:
                if (imagetypes() & IMG_JPG) {
                    if($quality === null) {
                        $quality = $this->getDefaultJPEGCompression();
                    }
                    $imageFile = $saveImageName.'.jpg';
                    $imageResult = imagejpeg($this->imageModified, $imageFile, $quality);
                } else {
                    throw new \Exception('Your GD library does not support jpg image types.');
                }
                break;
            case IMAGETYPE_PNG:
                if (imagetypes() & IMG_PNG) {
                    if($quality === null) {
                        $quality = $this->getDefaultPNGCompression();
                    }
                    $imageFile = $saveImageName.'.png';
                    $imageResult = imagepng($this->imageModified, $imageFile, $quality);
                } else {
                    throw new \Exception('Your GD library does not support png image types.');
                }
                break;
            default:
                break;
        }

        if($imageResult === false) {
            throw new \Exception('Image could not be saved.');
        }

        imagedestroy($this->imageModified);
        $this->imageModified = null;

        return $imageFile;
    }

    /**
     * @return int
     */
    public function getDefaultJPEGCompression()
    {
        return $this->defaultJPEGCompression;
    }

    /**
     * @param int $defaultJPEGCompression
     */
    public function setDefaultJPEGCompression($defaultJPEGCompression)
    {
        $this->defaultJPEGCompression = $defaultJPEGCompression;
    }

    /**
     * @return int
     */
    public function getDefaultPNGCompression()
    {
        return $this->defaultPNGCompression;
    }

    /**
     * @param int $defaultPNGCompression
     */
    public function setDefaultPNGCompression($defaultPNGCompression)
    {
        $this->defaultPNGCompression = $defaultPNGCompression;
    }

    /**
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @return integer
     */
    public function getSourceWidth()
    {
        return $this->sourceWidth;
    }

    /**
     * @return integer
     */
    public function getSourceHeight()
    {
        return $this->sourceHeight;
    }

    /**
     * Adds solid background to image
     *
     * @param $backgroundColor
     * @return void
     */
    protected function addBackgroundColor($backgroundColor) {
        if(strlen($backgroundColor) !== 6) {
            throw new \InvalidArgumentException('Argument has to be a hexadecimal RGB color value, without hash. For example FFFFFF.');
        }

        $decRed = hexdec(substr($backgroundColor, 0, 2));
        $decGreen = hexdec(substr($backgroundColor, 2, 2));
        $decBlue = hexdec(substr($backgroundColor, 4, 2));

        $imageSolidBackground = imagecreatetruecolor($this->newWidth, $this->newHeight);
        $solidColor = imagecolorallocate($imageSolidBackground, $decRed, $decGreen, $decBlue);
        imagefilledrectangle($imageSolidBackground, 0, 0, $this->newWidth, $this->newHeight, $solidColor);
        imagecopy($imageSolidBackground, $this->imageModified, 0, 0, 0, 0, $this->newWidth, $this->newHeight);

        $this->imageModified = $imageSolidBackground;
    }

    /**
     * @return void
     */
    protected function copyImageDataWithoutResampling() {
        $this->imageModified = imagecreatetruecolor($this->sourceWidth, $this->sourceHeight);

        imagealphablending($this->imageModified, false);
        imagesavealpha($this->imageModified, true);

        imagecopy($this->imageModified, $this->image, 0, 0, 0, 0, $this->sourceWidth, $this->sourceHeight);
    }

    /**
     * @return void
     */
    protected function copyImageData() {
        $this->imageModified = imagecreatetruecolor($this->newWidth, $this->newHeight);

        imagealphablending($this->imageModified, false);
        imagesavealpha($this->imageModified, true);

        imagecopyresampled($this->imageModified, $this->image, 0, 0, 0, 0, $this->newWidth, $this->newHeight, $this->sourceWidth, $this->sourceHeight);
    }

    /**
     * @param string $imagePath
     * @return resource
     * @throws InvalidArgumentException Image type is not supported or file is corrupted
     * @throws InvalidArgumentException Image type is not any of those IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG
     * @throws Exception Image could not be opened
     */
    protected function openImageFile($imagePath) {

        $imageType =  exif_imagetype($imagePath);

        if ($imageType === false) {
            throw new \InvalidArgumentException('Image type is not supported or file is corrupted.');
        }

        if (!in_array($imageType, $this->allowedImageTypes)) {
            throw new \InvalidArgumentException('Image type is not supported.');
        }

        switch ($imageType) {
            case IMAGETYPE_GIF :
                $image = imagecreatefromgif($imagePath);
                $this->sourceType = IMAGETYPE_GIF;
                break;
            case IMAGETYPE_JPEG :
                $image = imagecreatefromjpeg($imagePath);
                $this->sourceType = IMAGETYPE_JPEG;
                break;
            case IMAGETYPE_PNG :
                $image = imagecreatefrompng($imagePath);
                $this->sourceType = IMAGETYPE_PNG;
                break;
            default:
                break;
        }

        if($image === false) {
            throw new \Exception('Image could not be opened.');
        }

        return $image;
    }
}