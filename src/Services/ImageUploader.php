<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploader {
    private $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file)
    {
       $newFilename = uniqid().'.'.$file->guessExtension();
       try {
           $file->move($this->getTargetDirectory(), $newFilename);
       } catch (FileException $e) {
          // ... handle exception if something happens during file upload 
       }
       return $newFilename;
    }

    public function getTargetDirectory()
    {
       return $this->targetDirectory;
    }
}
