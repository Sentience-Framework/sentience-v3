<?php

namespace Sentience\Ai\Attachments;

class UrlAttachment
{
    public function __construct(public string $url)
    {
    }

    public static function fromUrl(string $url): static
    {
        return new static($url);
    }
}
