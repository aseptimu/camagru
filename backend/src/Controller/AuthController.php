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
        try {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->authService->login($username, $password);

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['email'] = $user->getEmail();

            $this->json([
                'status' => 'success',
                'message' => 'Logged in successfully.',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                ]
            ]);
        } catch (ApiException $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /register
     * @return void
     */
    public function register()
    {
        try {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

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

    public function status()
    {
        session_start();

        if (!empty($_SESSION['user_id'])) {
            $this->json([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'email' => $_SESSION['email'],
                ]
            ]);
        } else {
            $this->json([
                'authenticated' => false
            ]);
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

    /**
     * POST /api/logout
     */
    public function logout()
    {
        try {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION = [];
            setcookie(session_name(), '', time() - 3600, '/');
            session_destroy();

            $this->json([
                'status' => 'success',
                'message' => 'Logged out successfully.',
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}