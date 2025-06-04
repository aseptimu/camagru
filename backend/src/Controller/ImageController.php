<?php
namespace Camagru\Controller;

use Camagru\Core\Controller;
use Camagru\Core\Database;
use Camagru\Repository\ImageRepository;
use Camagru\Service\ImageService;
use Camagru\Core\Config;
use Camagru\Exception\ApiException;
use Camagru\Core\Logger;
use Exception;

class ImageController extends Controller
{
    public function list(): void
    {
        $pdo       = Database::getConnection();
        $repo      = new ImageRepository($pdo);
        $service   = new ImageService($repo, Config::uploadDir());

        $images = $service->listAll();
        $data = array_map(fn($img) => $img->toArray(), $images);
        $this->json($data);
    }

    public function upload(): void
    {
        try {
            $pdo = Database::getConnection();
            $repo = new ImageRepository($pdo);
            $uploadDir = Config::uploadDir();

            $service = new ImageService($repo, $uploadDir);
            $id = $service->upload();

            $this->json(['status' => 'success', 'id' => $id], 201);
        } catch (ApiException $e) {
            $this->json([
                'status'  => 'error',
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
}
