<?php

namespace Camagru\Controller;

use Camagru\Core\Controller;
use Camagru\Core\Database;
use Camagru\Model\Comment;
use Camagru\Model\FeedItem;
use Camagru\Repository\ImageRepository;
use Camagru\Service\EmailService;
use Camagru\Service\ImageService;
use Camagru\Core\Config;
use Camagru\Exception\ApiException;
use Camagru\Core\Logger;
use Exception;

class ImageController extends Controller
{
    private ImageService $imageService;


    public function __construct()
    {
        $pdo = Database::getConnection();
        $fromEmail = Config::get('EMAIL_FROM');
        $fromName = Config::get('EMAIL_FROM_NAME');
        $replyTo = Config::get('EMAIL_REPLY_TO', $fromEmail);

        $emailService = new EmailService($fromEmail, $fromName, $replyTo);
        $repo = new ImageRepository($pdo);
        $this->imageService = new ImageService($pdo, $repo, $emailService, Config::uploadDir());
    }

    public function list(): void
    {
        $images = $this->imageService->listAll();
        $data = array_map(fn($img) => $img->toArray(), $images);
        $this->json($data);
    }

    public function upload(): void
    {
        try {
            $id = $this->imageService->upload();

            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (ApiException $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    /**
     * GET /api/feed?page=1
     */
    public function feed(): void
    {
        try {
            session_start();
            $userId = $_SESSION['user_id'] ?? 0;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = max(1, (int)($_GET['size'] ?? 5));

            $models = $this->imageService->getFeed($userId, $page, $perPage);
            $items = array_map(fn($m) => $m->toArray(), $models);

            $total = $this->imageService->countImages();

            $this->json([
                'page' => $page,
                'size' => $perPage,
                'total' => $total,
                'items' => $items,
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to load feed'], 500);
        }

    }

    /**
     * GET /images/user/{userId}?page=1&size=10
     */
    public function listByUser(int $userId): void
    {
        try {
            $models = $this->imageService->getUserImages($userId);
            $items  = array_map(fn($m) => $m->toArray(), $models);

            $this->json([
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to load user images'], 500);
        }
    }

    public function delete(int $id): void
    {
        try {
            $deleted = $this->imageService->delete($id);
            if (!$deleted) {
                $this->json([
                    'error' => 'not found',
                ], 404);
            } else {
                $this->json([
                    'message' => 'success',
                ], 204);
            }
        } catch (ApiException $e) {
            http_response_code($e->getCode());
            echo json_encode(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Server error']);
        }
    }



    /**
     * POST /api/images/{id}/like
     */
    public function like(int $imageId): void
    {
        try {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) throw new ApiException('Not authenticated', 401);
            $this->imageService->like($userId, $imageId);
            $this->json(['status' => 'liked']);
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to like'], 500);
        }
    }

    /**
     * POST /api/images/{id}/unlike
     */
    public function unlike(int $imageId): void
    {
        try {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) throw new ApiException('Not authenticated', 401);
            $this->imageService->unlike($userId, $imageId);
            $this->json(['status' => 'unliked']);
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to unlike'], 500);
        }
    }

    /**
     * POST /api/images/{id}/comments
     */
    public function addComment(int $imageId): void
    {
        try {
            session_start();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) throw new ApiException('Not authenticated', 401);

            $text = trim($_POST['comment'] ?? '');
            if ($text === '') throw new ApiException('Comment cannot be empty', 400);

            $comment = $this->imageService->addComment($userId, $imageId, $text);
            $this->json(['comment' => $comment]);
        } catch (ApiException $e) {
            $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to add comment'], 500);
        }
    }

    /**
     * GET /api/images/{id}/comments?page=1
     */
    public function getComments(int $imageId): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $models = $this->imageService->getComments($imageId, $page);

            $comments = array_map(
                fn(Comment $c) => $c->toArray(),
                $models
            );

            $this->json([
                'page' => $page,
                'comments' => $comments
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json(['error' => 'Failed to load comments'], 500);
        }
    }
}
