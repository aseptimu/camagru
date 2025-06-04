<?php

namespace Camagru\Repository;

use Camagru\Core\Logger;
use Camagru\Exception\ApiException;
use Camagru\Model\User;

use PDO;
use PDOException;

class UserRepository
{
    private PDO $pdo;

    private const CREATE_USER_QUERY = '
        INSERT INTO users (username, email, password_hash, is_confirmed, confirmation_token)
        VALUES (:username, :email, :hash, :confirmed, :token)
        RETURNING id, created_at;
    ';

    private const FIND_BY_EMAIL_QUERY = '
        SELECT id, username, email, password_hash, is_confirmed, confirmation_token, created_at
        FROM users
        WHERE email = :email;
    ';

    private const FIND_BY_USERNAME_QUERY = '
        SELECT id, username, email, password_hash, is_confirmed, confirmation_token, created_at
        FROM users
        WHERE username = :username;
    ';

    private const FIND_BY_TOKEN_QUERY = '
        SELECT id, username, email, password_hash, is_confirmed, confirmation_token, created_at
        FROM users
        WHERE confirmation_token = :token;
    ';

    private const CONFIRM_USER_QUERY = '
        UPDATE users
        SET is_confirmed = TRUE,
            confirmation_token = NULL
        WHERE id = :id
    ';

    private const UPDATE_PROFILE_QUERY = '
        UPDATE users
        SET username = :username,
            email = :email,
            password_hash = :hash
        WHERE id = :id
    ';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Creates new user. Returns array ['id' => int, 'created_at' => string]
     *
     * @return array{id:int, created_at:string}
     * @throws ApiException failed to INSERT user
     */
    public function create(User $user): array
    {
        try {
            $stmt = $this->pdo->prepare(self::CREATE_USER_QUERY);
            $stmt->execute([
                ':username' => $user->getUsername(),
                ':email' => $user->getEmail(),
                ':hash' => $user->getPasswordHash(),
                ':confirmed' => $user->isConfirmed() ? 1 : 0,
                ':token' => $user->getConfirmationToken(),
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'id' => (int)$row['id'],
                'created_at' => $row['created_at'],
            ];
        } catch (PDOException $e) {
            Logger::error($e->getMessage() . $user);
            throw new ApiException('Failed to create user');
        }
    }

    /**
     * Find user by email
     *
     * @param string $email user email
     * @return ?User
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(self::FIND_BY_EMAIL_QUERY);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }
        return $this->mapRowToUser($row);
    }

    /**
     * Find user by username
     *
     * @param string $username
     * @return ?User
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare(self::FIND_BY_USERNAME_QUERY);
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }
        return $this->mapRowToUser($row);
    }

    /**
     * Find user by confirmation token
     *
     * @param string $token email confirmation token
     * @return ?User
     */
    public function findByConfirmationToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare(self::FIND_BY_TOKEN_QUERY);
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }
        return $this->mapRowToUser($row);
    }

    /**
     * Confirm user email
     *
     * @param int $userId
     * @throws ApiException failed to update confirmation status
     */
    public function confirmUser(int $userId): void
    {
        try {
            $stmt = $this->pdo->prepare(self::CONFIRM_USER_QUERY);
            $stmt->execute([':id' => $userId]);
        } catch(PDOException $e) {
            Logger::error($e->getMessage());
            throw new ApiException('Failed to confirm user');
        }
    }

    /**
     * Update user profile
     *
     * @param User $user username, email, password, id
     * @throws ApiException failed to update user profile
     */
    public function updateProfile(User $user): void
    {
        try {
            $stmt = $this->pdo->prepare(self::UPDATE_PROFILE_QUERY);
            $stmt->execute([
                ':username' => $user->getUsername(),
                ':email'    => $user->getEmail(),
                ':hash'     => $user->getPasswordHash(),
                ':id'       => $user->getId(),
            ]);
        } catch (PDOException $e) {
            Logger::error($e->getMessage());
            throw new ApiException('Failed to update profile');
        }
    }

    private function mapRowToUser(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['username'],
            $row['email'],
            $row['password_hash'],
            $row['is_confirmed'],
            $row['confirmation_token'],
            $row['created_at'],
        );
    }
}