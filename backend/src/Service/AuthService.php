<?php

namespace Camagru\Service;

use Camagru\Exception\ApiException;
use Camagru\Model\User;
use Camagru\Repository\UserRepository;
use Camagru\Core\Logger;

use Exception;
use PDO;

class AuthService
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private EmailService $emailService;

    public function __construct(PDO $pdo, EmailService $emailService)
    {
        $this->pdo = $pdo;
        $this->userRepo = new UserRepository($pdo);
        $this->emailService = $emailService;
    }

    /**
     * Register new user
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @return string Confirmation url
     *
     * @throws ApiException
     */
    public function register(string $username, string $email, string $password): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Invalid email', 400);
        }

        if (mb_strlen($username) < 3) {
            throw new ApiException('Username too short (min 3 symbols)', 400);
        }

        if (mb_strlen($password) < 6) {
            throw new ApiException('Password too short (min 6 symbols)', 400);
        }

        if ($this->userRepo->findByEmail($email) !== null) {
            throw new ApiException('Email already exists', 409);
        }
        if ($this->userRepo->findByUsername($username) !== null) {
            throw new ApiException('Username already exists', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $token = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            throw new ApiException('Could not generate confirmation token', 500);
        }

        $this->pdo->beginTransaction();

        try {
            $user = new User(
                null,
                $username,
                $email,
                $passwordHash,
                false,
                $token,
                date('Y-m-d H:i:s'),
                true
            );

            $this->userRepo->create($user);
            $confirmUrl = $this->getBaseUrl() . '/confirm?token=' . $token;
            $this->emailService->sendConfirmation($email, $username, $confirmUrl);

            $this->pdo->commit();
        } catch (ApiException $e) {
            $this->pdo->rollBack();
            throw $e;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            Logger::error($e->getMessage());
            throw new ApiException('Failed register user: ' . $e->getMessage(), 500);
        }

        return $confirmUrl;
    }

    /**
     * Authenticate user by username and password.
     *
     * @param string $username
     * @param string $password
     * @return User
     * @throws ApiException
     */
    public function login(string $username, string $password): User
    {
        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            throw new ApiException('Username and password are required', 400);
        }

        $user = $this->userRepo->findByUsername($username);
        if ($user === null) {
            throw new ApiException('Invalid username or password', 401);
        }

        if (!$user->isConfirmed()) {
            throw new ApiException('Account not confirmed', 403);
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            throw new ApiException('Invalid username or password', 401);
        }

        return $user;
    }

    /**
     * Confirm account using token
     *
     * @throws ApiException
     */
    public function confirm(string $token)
    {
        if ($token === '') {
            throw new ApiException('Invalid token', 400);
        }

        $user = $this->userRepo->findByConfirmationToken($token);
        if ($user === null) {
            throw new ApiException('Invalid token', 400);
        }
        if ($user->isConfirmed()) {
            throw new ApiException('User already confirmed', 409);
        }

        $this->userRepo->confirmUser($user->getId());
    }

    /**
     * Update user details
     * @param int $userId
     * @param string|null $newUsername
     * @param string|null $newEmail
     * @param string|null $newPassword
     * @return void
     * @throws ApiException
     */
    public function updateProfile(
        int     $userId,
        ?string $newUsername,
        ?string $newEmail,
        ?string $newPassword,
        ?bool   $notifyOnComment
    ): void
    {
        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            throw new ApiException('User not found', 404);
        }

        if ($newUsername !== null) {
            $newUsername = trim($newUsername);
            if (mb_strlen($newUsername) < 3) {
                throw new ApiException('Username must be at least 3 characters', 400);
            }
            $exists = $this->userRepo->findByUsername($newUsername);
            if ($exists !== null && $exists->getId() !== $userId) {
                throw new ApiException('Username already taken', 409);
            }
            $user->setUsername($newUsername);
        }

        if ($newEmail !== null) {
            $newEmail = trim($newEmail);
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new ApiException('Invalid email format', 400);
            }
            $exists = $this->userRepo->findByEmail($newEmail);
            if ($exists !== null && $exists->getId() !== $userId) {
                throw new ApiException('Email already in use', 409);
            }
            $user->setEmail($newEmail);
        }

        if ($newPassword !== null) {
            if (mb_strlen($newPassword) < 6) {
                throw new ApiException('Password must be at least 6 characters', 400);
            }
            $user->setPasswordHash(password_hash($newPassword, PASSWORD_DEFAULT));
        }

        if ($notifyOnComment !== null) {
            $user->setNotifyOnComment($notifyOnComment);
        }


        $this->userRepo->updateProfile($user);
    }

    /**
     * Generates reset token and sends email.
     *
     * @param string $email
     * @throws ApiException
     */
    public function requestPasswordReset(string $email): void
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Invalid email', 400);
        }

        try {
            $token = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            throw new ApiException('Could not generate reset token', 500);
        }

        $this->userRepo->setResetTokenByEmail($email, $token);

        $user = $this->userRepo->findByEmail($email);
        if ($user === null) {
            throw new ApiException('Email not found', 404);
        }

        $resetUrl = $this->getBaseUrl() . '/reset?token=' . $token;

        $this->emailService->sendPasswordReset($email, $user->getUsername(), $resetUrl);
    }

    /**
     * Resets password: check token, updates password and clear token.
     *
     * @param string $token
     * @param string $newPassword
     * @throws ApiException
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $token = trim($token);
        if ($token === '') {
            throw new ApiException('Token is required', 400);
        }
        if (mb_strlen($newPassword) < 6) {
            throw new ApiException('Password must be at least 6 characters', 400);
        }

        $user = $this->userRepo->findByResetToken($token);
        if ($user === null) {
            throw new ApiException('Invalid or expired token', 400);
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepo->updatePassword($user->getId(), $hash);

        $this->userRepo->clearResetToken($user->getId());
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $scheme . '://' . $host;
    }

}