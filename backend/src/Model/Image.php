<?php

namespace Camagru\Model;

class Image
{
    private int $id;
    private string $filename;
    private string $originalName;
    private string $createdAt;

    public function __construct(int $id, string $filename, string $originalName, string $createdAt)
    {
        $this->id = $id;
        $this->filename = $filename;
        $this->originalName = $originalName;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "filename" => $this->filename,
            "original_name" => $this->originalName,
            "created_at" => $this->createdAt,
        ];
    }
}