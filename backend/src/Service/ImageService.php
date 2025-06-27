<?php

namespace Camagru\Service;

use Camagru\Core\Config;
use Camagru\Core\Logger;
use Camagru\Exception\ApiException;
use Camagru\Exception\DatabaseException;
use Camagru\Exception\ValidationException;
use Camagru\Repository\ImageRepository;
use Camagru\Model\Image;
use Exception;
use finfo;
use PDO;

class ImageService
{
    private ImageRepository $imageRepository;
    private EmailService $emailService;
    private string $uploadDir;
    private PDO $pdo;


    public function __construct(PDO $pdo, ImageRepository $repo, EmailService $emailService, string $uploadDir)
    {
        $this->pdo = $pdo;
        $this->imageRepository = $repo;
        $this->emailService = $emailService;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     * @throws Exception
     */
    public function upload(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId === null) {
            throw new ApiException('Not authenticated', 401);
        }

        if (!isset($_FILES['image'])) {
            throw new ValidationException('No file uploaded');
        }
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Upload error code: {$file['error']}");
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed  = ['image/jpeg','image/png','image/gif'];
        if (!in_array($mimeType, $allowed, true)) {
            throw new \Exception("Invalid MIME type: $mimeType");
        }

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $baseName= uniqid('img_', true) . '.' . $ext;
        $destDir = $this->uploadDir;
        if (!is_dir($destDir) && !mkdir($destDir,0755,true) && !is_dir($destDir)) {
            throw new Exception("Cannot create upload dir: {$destDir}");
        }
        $basePath = "{$destDir}/{$baseName}";
        if (!move_uploaded_file($file['tmp_name'], $basePath)) {
            throw new Exception("Failed to move upload to {$basePath}");
        }

        $finalName = $baseName;
        if (!empty($_POST['overlay'])) {
            $overlayFile = basename($_POST['overlay']);
            $overlayPath = Config::overlayDir() . DIRECTORY_SEPARATOR . $overlayFile;
            if (!file_exists($overlayPath)) {
                throw new Exception("Overlay file not found: {$overlayFile}");
            }
            $composedName = $this->composeWithGD($basePath, $overlayPath, $destDir);
            @unlink($basePath);
            $finalName = $composedName;
        }

        return $this->imageRepository->save(
            $userId,
            $finalName,
            $file['name']
        );
    }

    /**
     * @throws ApiException|DatabaseException
     */
    public function delete(int $imageId): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $_SESSION['user_id'] ?? null;
        if (!$currentUserId) {
            throw new ApiException('Not authenticated', 401);
        }

        $img = $this->imageRepository->findById($imageId);
        if (!$img) {
            return false;
        }
        if ((int)$img['user_id'] !== (int)$currentUserId) {
            throw new ApiException('Forbidden', 403);
        }

        $this->pdo->beginTransaction();
        try {
            $this->imageRepository->delete($imageId);
            $filePath = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . $img['filename'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            Logger::error('Delete image failed: ' . $e->getMessage());
            throw new ApiException('Failed to delete image', 500);
        }
    }


    private function composeWithGD(string $userImgPath, string $overlayPath, string $outDir): string
    {
        $base = @\imagecreatefromstring(file_get_contents($userImgPath));
        $ovl  = @\imagecreatefromstring(file_get_contents($overlayPath));
        if (!$base || !$ovl) {
            throw new \Exception("Invalid image data for compose");
        }

        imagealphablending($base, true);
        imagesavealpha($base, true);

        $wB = imagesx($base);
        $hB = imagesy($base);
        $wO = imagesx($ovl);
        $hO = imagesy($ovl);

        imagecopyresampled(
            $base, $ovl,
            0, 0, 0, 0,
            $wB, $hB,
            $wO, $hO
        );

        $outName = uniqid('comp_', true) . '.png';
        $outPath = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . $outName;
        if (!imagepng($base, $outPath)) {
            throw new \Exception("Failed to save composed image");
        }

        imagedestroy($base);
        imagedestroy($ovl);

        return $outName;
    }


    /**
     * Returns all images
     * @return array<Image>
     */
    public function listAll(): array
    {
        return $this->imageRepository->findAll();
    }

    public function getFeed(int $currentUserId, int $page): array
    {
        return $this->imageRepository->getFeed($currentUserId, $page, 5);
    }

    public function countImages(): int
    {
        return $this->imageRepository->countImages();
    }

    public function getUserImages(int $userId): array
    {
        return $this->imageRepository->getByUser($userId);
    }
    public function like(int $userId, int $imageId): void
    {
        $this->imageRepository->like($userId, $imageId);
    }

    public function unlike(int $userId, int $imageId): void
    {
        $this->imageRepository->unlike($userId, $imageId);
    }

    /**
     * Adds comment to image
     *
     * @param int $userId
     * @param int $imageId
     * @param string $text
     * @return array
     * @throws ApiException
     */
    public function addComment(int $userId, int $imageId, string $text): array
    {
        $this->pdo->beginTransaction();
        try {
            $comment = $this->imageRepository->addComment($userId, $imageId, $text);
            $owner   = $this->imageRepository->getImageOwner($imageId);
            if ($owner && $owner['notify_on_comment']) {
                $resetUrl = $this->getBaseUrl() . '/images/' . $imageId;
                $this->emailService->sendCommentNotification(
                    $owner['email'],
                    $owner['username'],
                    $text,
                    $resetUrl
                );
            }
            $this->pdo->commit();
            return $comment;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            Logger::error($e->getMessage());
            throw new ApiException('Failed to add comment', 500);
        }
    }

    public function getComments(int $imageId, int $page): array
    {
        return $this->imageRepository->getComments($imageId, $page, 10);
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
}