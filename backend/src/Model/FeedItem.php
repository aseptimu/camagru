<?php
namespace Camagru\Model;

class FeedItem
{
    private int    $id;
    private int    $userId;
    private string $username;
    private string $url;
    private string $createdAt;
    private int    $likeCount;
    private int    $commentCount;
    private bool   $likedByMe;

    public function __construct(
        int    $id,
        int    $userId,
        string $username,
        string $url,
        string $createdAt,
        int    $likeCount,
        int    $commentCount,
        bool   $likedByMe
    ) {
        $this->id           = $id;
        $this->userId       = $userId;
        $this->username     = $username;
        $this->url          = $url;
        $this->createdAt    = $createdAt;
        $this->likeCount    = $likeCount;
        $this->commentCount = $commentCount;
        $this->likedByMe    = $likedByMe;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->userId,
            'username'      => $this->username,
            'url'           => $this->url,
            'created_at'    => $this->createdAt,
            'like_count'    => $this->likeCount,
            'comment_count' => $this->commentCount,
            'liked_by_me'   => $this->likedByMe,
        ];
    }
}
