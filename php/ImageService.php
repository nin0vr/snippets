<?php

class ImageService
{
    private string $decode;
    private string $base64;
    private string $fileName = "";
    private string $filePath = "tmp";
    private string $relativeOriginalPath = "";
    private string $relativeThumbPath = "";
    private int $desiredSize = 150;
    private int $lowestQuality = 65;
    private int $highestQuality = 80;

    public function __construct()
    {

    }

    public function getRelativeOriginalPath() : string
    {
        return $this->relativeOriginalPath;
    }

    public function getRelativeThumbPath() : string
    {
        return $this->relativeThumbPath;
    }

    public function setFileName(string $name) : void
    {
        $now = new DateTime();
        $this->fileName = $now->format('U') . '-' . $name . '.jpg';
    }

    public function setImageData($imageData) : void
    {
        $this->base64 = $imageData;
        $encode = explode(',', $this->base64, 2);
        $this->decode = base64_decode($encode[1]);
        unset($encode);
    }

    public function setImageDataString($base64): void
    {
        $this->decode = base64_decode($base64);
    }

    public function generateImage() : void
    {
        try{
            $img = imagecreatefromstring($this->decode);
            if($this->filePath == "" || $this->fileName == ""){
                throw new Exception("File path or file name are not defined");
            } else {
                $this->saveImage($img, false);
            }
        } catch (Exception $e)
        {
            $this->loggerService->logException($e);
        }
    }

    public function generateThumbnail(?int $size = null) : void
    {
        if($size){
            $this->desiredSize = $size;
        }
        try {
            $desiredWidth = 0;
            $desiredHeight = 0;
            $img = imagecreatefromstring($this->decode);
            $width = imagesx($img);
            $height = imagesy($img);
            $orientation = $this->getOrientationOnHeight($width, $height);
            switch($orientation) {
                case "p" : 
                    $desiredWidth = $this->desiredSize;
                    $desiredHeight = $this->calculateSize($height, $width); 
                    break;
                case "l" :
                    $desiredHeight = $this->desiredSize;
                    $desiredWidth = $this->calculateSize($width, $height);
                    break;
                case "s" :  
                    $desiredWidth = $this->desiredSize;
                    $desiredHeight = $this->desiredSize;
                    break;
            }
            if ($width < $desiredWidth || $height < $desiredHeight) {
                $copy = imagecreatefromstring($this->decode);
            } else {
                $copy = imagecreatetruecolor($desiredWidth, $desiredHeight);
                imagecopyresampled($copy, $img, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);
            }
            if ($this->filePath == "" || $this->fileName == "") {
                throw new Exception("File path or file name are not defined");
            } else {
                 $this->saveImage($copy, true);
            }
        } catch (Exception $e){
            $this->loggerService->logException($e);
        }
    }

    private function saveImage($img, $isThumb) : void
    {
        $compress = ($isThumb) ? $this->lowestQuality : $this->highestQuality;
        $path = ($isThumb) ? $this->filePath . '/thumb/' : $this->filePath . '/original/';
        $path = 'images/' . $path;
        if(!is_dir($path)){
            mkdir($path,  0774, true);
        }
        $dirPath = $path . $this->fileName;
        imagejpeg($img, $dirPath, $compress);
        $exif = exif_read_data($dirPath);
        if($exif) $this->orientation($exif, $img, $dirPath);
        imagedestroy($img);
        $this->createPaths($isThumb);
    }

    private function createPaths($isThumb) : void
    {
        if ($isThumb) {
            $this->relativeThumbPath = $this->filePath . '/thumb/' . $this->fileName;
        } else {
            $this->relativeOriginalPath = $this->filePath . '/original/' . $this->fileName;
        }
    }

    private function orientation($exif, $img, $path): void
    {
        try {
            if ($exif) {
                if (array_key_exists("Orientation", $exif)) {
                    switch ($exif['Orientation']) {
                        case 8:
                            $img = imagerotate($img, -90, 0);
                            imagejpeg($img, $path, 100);
                            break;
                        case 3:
                            $img = imagerotate($img, 180, 0);
                            imagejpeg($img, $path, 100);
                            break;
                        case 6:
                            $img = imagerotate($img, 90, 0);
                            imagejpeg($img, $path, 100);
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->loggerService->logException($e);
        }
    }

    public function getImage($path) : string|null
    {
       return $this->getItem($path);
    }

    private function calculateSize($a, $b) : float
    {
        return floor($a * ($this->desiredSize / $b));
    }

    private function getOrientationOnHeight(int $x, int $y) : string
    {
        if($x > $y){
            return 'l';
        } elseif($y > $x){
            return 'p';
        } else {
            return 's';
        }
    }
}