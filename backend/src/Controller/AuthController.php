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

    public function login(): void
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
            $_SESSION['notifyOnComment'] = $user->isNotifyOnComment();

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
    public function register(): void
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

    public function status(): void
    {
        session_start();

        if (!empty($_SESSION['user_id'])) {
            $this->json([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'email' => $_SESSION['email'],
                    'notifyOnComment' => $_SESSION['notifyOnComment'],
                ]
            ]);
        } else {
            $this->json([
                'authenticated' => false
            ]);
        }
    }

    public function confirm(): void
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
    public function logout(): void
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

    public function updateProfile(): void
    {
        try {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $userId = $_SESSION['user_id'] ?? null;
            if ($userId === null) {
                throw new ApiException('Not authenticated', 400);
            }

            $newUsername = $_POST['username'] ?? null;
            $newEmail = $_POST['email'] ?? null;
            $newPassword = $_POST['password'] ?? null;
            $notifyFlagRaw      = $_POST['notifyOnComment']  ?? null;
            $notifyOnComment    = null;
            if ($notifyFlagRaw !== null) {
                $notifyOnComment = ($notifyFlagRaw === '1' || $notifyFlagRaw === 'true');
            }

            $this->authService->updateProfile($userId, $newUsername, $newEmail, $newPassword, $notifyOnComment);
            if ($newUsername !== null) {
                $_SESSION['username'] = $newUsername;
            }
            if ($newEmail !== null) {
                $_SESSION['email'] = $newEmail;
            }
            if ($notifyOnComment !== null) {
                $_SESSION['notifyOnComment'] = $notifyOnComment;
            }
            $this->json([
                'status' => 'success',
                'message' => 'profile updated successfully.',
            ]);
        } catch (ApiException $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ]);
        }
    }

    /**
     * POST /api/recover
     * body: { email }
     */
    public function recover(): void
    {
        try {
            $email = $_POST['email'] ?? '';
            $this->authService->requestPasswordReset($email);
            $this->json([
                'status'  => 'success',
                'message' => 'Password reset link sent if email exists.'
            ]);
        } catch (ApiException $e) {
            $code = $e->getStatusCode();
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], $code);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status'  => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * POST /api/reset
     * body: { token, password }
     */
    public function reset(): void
    {
        try {
            $token       = $_POST['token']    ?? '';
            $newPassword = $_POST['password'] ?? '';
            $this->authService->resetPassword($token, $newPassword);
            $this->json([
                'status'  => 'success',
                'message' => 'Password has been reset.'
            ]);
        } catch (ApiException $e) {
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->json([
                'status'  => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
}