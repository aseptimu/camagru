<?php
namespace Camagru\Service;

use Camagru\Exception\DatabaseException;
use Camagru\Exception\ValidationException;
use Camagru\Repository\ImageRepository;
use Camagru\Model\Image;
use Exception;
use finfo;

class ImageService
{
    private ImageRepository $imageRepository;
    private string $uploadDir;

    public function __construct(ImageRepository $repo, string $uploadDir)
    {
        $this->imageRepository = $repo;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @throws ValidationException
     * @throws DatabaseException
     */
    public function upload(): int
    {
        if (!isset($_FILES['image'])) {
            throw new ValidationException('No file uploaded');
        }
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error code: {$file['error']}");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg','image/png','image/gif'];
        if (!in_array($mimeType, $allowed)) {
            throw new Exception("Invalid MIME type: $mimeType");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $ext;
        $destDir  = $this->uploadDir;
        $dest = "{$this->uploadDir}/{$filename}";

        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                throw new \RuntimeException("Не удалось создать директорию для загрузки: {$destDir}");
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Failed to move uploaded file" . $dest);
        }
        
        return $this->imageRepository->save($filename, $file['name']);
    }

    /**
     * Returns all images
     * @return array<Image>
     */
    public function listAll(): array
    {
        return $this->imageRepository->findAll();
    }
}