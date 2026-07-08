<?php

namespace Sentience\Ai\Apis;

use Sentience\Ai\Attachments\Base64Attachment;
use Sentience\Ai\Attachments\UrlAttachment;
use Sentience\Ai\Messages\SystemMessage;
use Sentience\Ai\Schema\Schemable;


abstract class ApiAbstract implements ApiInterface
{
    protected function isImageExtension(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'],
            true
        );
    }

    protected function isImageFilename(?string $filename): bool
    {
        if (!$filename) {
            return false;
        }

        return $this->isImageExtension(pathinfo($filename, PATHINFO_EXTENSION));
    }

    protected function mimeTypeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => 'image/png',
        };
    }

    protected function urlExtension(string $url): string
    {
        return strtolower(
            pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)
        );
    }

    protected function buildContent(array $content): array|string
    {
        if (count($content) === 1 && is_string($content[0])) {
            return $content[0];
        }

        $inputs = [];

        foreach ($content as $input) {
            if (is_string($input)) {
                $inputs[] = [
                    'type' => 'text',
                    'text' => $input,
                ];

                continue;
            }

            $inputs[] = match (true) {
                $input instanceof Base64Attachment => $this->buildBase64Content($input),
                $input instanceof UrlAttachment => $this->buildUrlContent($input),
            };
        }

        return $inputs;
    }

    abstract protected function buildBase64Content(Base64Attachment $attachment): array;

    abstract protected function buildUrlContent(UrlAttachment $attachment): array;

    protected function buildStructuredOutputMessage(Schemable $schema): SystemMessage
    {
        return new SystemMessage(
            'The final response to the user should only be in minified JSON (no spaces or \n newlines). '
            . 'Follow the JSON standard (ISO/IEC 21778, ECMA-404, https://www.json.org/). '
            . 'Strictly adhere to the following schema: '
            . json_encode($schema->schema())
        );
    }
}
