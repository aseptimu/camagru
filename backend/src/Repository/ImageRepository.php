<?php
namespace Camagru\Repository;

use Camagru\Exception\DatabaseException;
use Camagru\Core\Logger;
use Camagru\Model\Image;
use PDOException;
use PDO;

class ImageRepository
{
    private PDO $pdo;

    private const UPLOAD_IMAGE_QUERY = '
        INSERT INTO images (filename, original_name, created_at)
        VALUES (:fn, :orig, NOW())
    ';

    private const ALL_IMAGES_QUERY = '
        SELECT id, filename, original_name, created_at
        FROM images
        ORDER BY created_at DESC
    ';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(string $filename, string $originalName): int
    {
        try {
            $stmt = $this->pdo->prepare(self::UPLOAD_IMAGE_QUERY);
            $stmt->execute([
                ':fn' => $filename,
                ':orig' => $originalName,
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            Logger::error($e->getMessage());
            throw new DatabaseException("Failed to save image");
        }
    }

    /**
     *  @return array<Image>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(self::ALL_IMAGES_QUERY);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return array_map(function (array $images) {
            return new Image(
                (int)$images['id'],
                $images['filename'],
                $images['original_name'],
                $images['created_at']
            );
        }, $rows);
    }
}