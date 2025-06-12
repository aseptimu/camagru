<?php
namespace Camagru\Repository;

use Camagru\Exception\ApiException;
use Camagru\Exception\DatabaseException;
use Camagru\Core\Logger;
use Camagru\Model\Comment;
use Camagru\Model\FeedItem;
use Camagru\Model\Image;
use PDOException;
use PDO;

class ImageRepository
{
    private PDO $pdo;

    private const SAVE_IMAGE_QUERY = '
        INSERT INTO images (user_id, filename, original_name)
        VALUES (:user_id, :filename, :original_name)
        RETURNING id
    ';

    private const ALL_IMAGES_QUERY = '
        SELECT id, filename, original_name, created_at
        FROM images
        ORDER BY created_at DESC
    ';

    private const FEED_QUERY = '
      SELECT
        img.id,
        img.user_id,
        img.filename,
        img.created_at,
        u.username,
        COUNT(DISTINCT l.id) AS like_count,
        COUNT(DISTINCT c.id) AS comment_count,
        EXISTS (
          SELECT 1 FROM likes lx 
          WHERE lx.image_id = img.id AND lx.user_id = :currentUserId
        ) AS liked_by_me
      FROM images img
      JOIN users u            ON u.id = img.user_id
      LEFT JOIN likes l       ON l.image_id = img.id
      LEFT JOIN comments c    ON c.image_id = img.id
      GROUP BY img.id, u.username
      ORDER BY img.created_at DESC
      LIMIT :limit OFFSET :offset
    ';


    private const COUNT_IMAGES_QUERY = '
      SELECT COUNT(*) AS total
      FROM images
    ';

    private const GET_USER_IMAGES_QUERY = '
        SELECT id, filename, original_name, created_at
        FROM images
        WHERE user_id = :userId
        ORDER BY created_at DESC
    ';

    private const LIKE_QUERY = '
      INSERT INTO likes (user_id, image_id) VALUES (:uid, :iid)
      ON CONFLICT (user_id, image_id) DO NOTHING
    ';

    private const UNLIKE_QUERY = '
      DELETE FROM likes WHERE user_id = :uid AND image_id = :iid
    ';

    private const ADD_COMMENT_QUERY = '
      INSERT INTO comments (user_id, image_id, comment_text)
      VALUES (:uid, :iid, :text)
      RETURNING id, created_at
    ';

    private const GET_COMMENTS_QUERY = '
      SELECT c.id, c.user_id, u.username, c.comment_text, c.created_at
      FROM comments c
      JOIN users u ON u.id = c.user_id
      WHERE c.image_id = :iid
      ORDER BY c.created_at ASC
      LIMIT :limit OFFSET :offset
    ';

    private const GET_IMAGE_OWNER_QUERY = '
      SELECT u.email, u.username, u.notify_on_comment
      FROM images img
      JOIN users u ON u.id = img.user_id
      WHERE img.id = :iid
    ';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param int    $userId
     * @param string $filename
     * @param string $originalName
     * @return int  created ID
     * @throws ApiException
     */
    public function save(int $userId, string $filename, string $originalName): int
    {
        try {
            $stmt = $this->pdo->prepare(self::SAVE_IMAGE_QUERY);
            $stmt->bindValue(':user_id',      $userId,       PDO::PARAM_INT);
            $stmt->bindValue(':filename',     $filename,     PDO::PARAM_STR);
            $stmt->bindValue(':original_name',$originalName, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['id'];
        } catch (PDOException $e) {
            throw new ApiException('Failed to save image', 500);
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

    public function getFeed(int $currentUserId, int $page, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(self::FEED_QUERY);
        $stmt->bindValue(':currentUserId', $currentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $perPage,       PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,        PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new FeedItem(
                (int)$row['id'],
                (int)$row['user_id'],
                $row['username'],
                $row['filename'],
                $row['created_at'],
                (int)$row['like_count'],
                (int)$row['comment_count'],
                (bool)$row['liked_by_me']
            );
        }
        return $items;
    }

    public function countImages(): int
    {
        $stmt = $this->pdo->query(self::COUNT_IMAGES_QUERY);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    /**
     * User images
     *
     * @param int $userId
     * @return Image[]
     */
    public function getByUser(int $userId): array
    {
        $stmt   = $this->pdo->prepare(self::GET_USER_IMAGES_QUERY);
        $stmt->bindValue(':userId', $userId,     PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = new Image(
                (int)$row['id'],
                $row['filename'],
                $row['original_name'],
                $row['created_at']
            );
        }
        return $result;
    }

    public function like(int $userId, int $imageId): void
    {
        $stmt = $this->pdo->prepare(self::LIKE_QUERY);
        $stmt->execute([':uid' => $userId, ':iid' => $imageId]);
    }

    public function unlike(int $userId, int $imageId): void
    {
        $stmt = $this->pdo->prepare(self::UNLIKE_QUERY);
        $stmt->execute([':uid' => $userId, ':iid' => $imageId]);
    }


    public function addComment(int $userId, int $imageId, string $text): array
    {
        $stmt = $this->pdo->prepare(self::ADD_COMMENT_QUERY);
        $stmt->execute([
            ':uid'  => $userId,
            ':iid'  => $imageId,
            ':text' => $text
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getComments(int $imageId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(self::GET_COMMENTS_QUERY);
        $stmt->bindValue(':iid',    $imageId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        $comments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = new Comment(
                (int)$row['id'],
                (int)$row['user_id'],
                $row['username'],
                $row['comment_text'],
                $row['created_at']
            );
        }
        return $comments;
    }


    public function getImageOwner(int $imageId): ?array
    {
        $stmt = $this->pdo->prepare(self::GET_IMAGE_OWNER_QUERY);
        $stmt->execute([':iid' => $imageId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}