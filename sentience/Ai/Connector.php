<?php

namespace Sentience\Ai;

use Sentience\Ai\Connectors\AntropicConnector;
use Sentience\Ai\Connectors\ConnectorInterface;
use Sentience\Ai\Connectors\OpenAIConnector;

enum Connector: string
{
    case OpenAI = 'openai';
    case Antropic = 'antropic';

    public function getConnector(string $baseUri, string $apiKey): ConnectorInterface
    {
        return match ($this) {
            self::OpenAI => new OpenAIConnector($baseUri, $apiKey),
            self::Antropic => new AntropicConnector($baseUri, $apiKey)
        };
    }
}
