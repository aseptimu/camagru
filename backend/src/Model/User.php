<?php
namespace Camagru\Model;

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private bool $isConfirmed;
    private ?string $confirmationToken;
    private string $createdAt;

    public function __construct(
        ?int $id,
        string $username,
        string $email,
        string $passwordHash,
        bool $isConfirmed,
        ?string $confirmationToken,
        string $createdAt
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->isConfirmed = $isConfirmed;
        $this->confirmationToken = $confirmationToken;
        $this->createdAt = $createdAt;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): void
    {
        $this->isConfirmed = $isConfirmed;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function __toString()
    {
        return sprintf(
            'User[id=%s username=%s email=%s isConfirmed=%s createdAt=%s]',
            $this->id,
            $this->username,
            $this->email,
            $this->isConfirmed,
            $this->createdAt
        );
    }


}
