<?php
namespace Camagru\Model;

class Comment
{
    private int    $id;
    private int    $userId;
    private string $username;
    private string $text;
    private string $createdAt;

    public function __construct(
        int    $id,
        int    $userId,
        string $username,
        string $text,
        string $createdAt
    ) {
        $this->id        = $id;
        $this->userId    = $userId;
        $this->username  = $username;
        $this->text      = $text;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->userId,
            'username'   => $this->username,
            'comment'    => $this->text,
            'created_at' => $this->createdAt,
        ];
    }
}
