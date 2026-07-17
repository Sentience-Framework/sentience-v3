<?php

namespace Sentience\Ai\Apis;

use Sentience\Ai\Apis\ChunkSize;

interface ResponseInterface
{
    public function readStream(bool $untilEof = false, ChunkSize $chunkSize = ChunkSize::M): void;

    public function getContent(): string;
    public function getReasoningContent(): string;
    public function getToolCalls(): array;
    public function getFinishReason(): string;
    public function getStructuredOutput(): ?array;
}
