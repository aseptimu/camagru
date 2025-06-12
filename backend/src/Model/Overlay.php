<?php
namespace Camagru\Model;

class Overlay
{
    private int    $id;
    private string $filename;
    private string $url;

    public function __construct(int $id, string $filename, string $url)
    {
        $this->id       = $id;
        $this->filename = $filename;
        $this->url      = $url;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'filename' => $this->filename,
            'url'      => $this->url,
        ];
    }
}
