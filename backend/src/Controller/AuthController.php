<?php
namespace Camagru\Controller;
use Camagru\Core\Config;
use Camagru\Core\Controller;
use Camagru\Core\Database;
use Camagru\Core\Logger;
use Camagru\Exception\ApiException;
use Camagru\Service\AuthService;
use Camagru\Service\EmailService;
use Exception;

class AuthController extends Controller
{

    private AuthService $authService;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $fromEmail = Config::get('EMAIL_FROM');
        $fromName  = Config::get('EMAIL_FROM_NAME');
        $replyTo   = Config::get('EMAIL_REPLY_TO', $fromEmail);

        $emailService   = new EmailService($fromEmail, $fromName, $replyTo);
        $this->authService = new AuthService($pdo, $emailService);
    }

    public function login()
    {

    }

    /**
     * POST /register
     * @return void
     */
    public function register()
    {
        try {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            $confirmUrl = $this->authService->register($username, $email, $password);
            $this->json($confirmUrl, 201);

        } catch(ApiException $e) {
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

    public function confirm()
    {
        try {
            $token = $_GET['token'] ?? '';
            $this->authService->confirm($token);
            $this->json([
                'status'  => 'success',
                'message' => 'Confirmed',
            ]);
        }  catch(ApiException $e) {
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

    public function logout()
    {

    }
}