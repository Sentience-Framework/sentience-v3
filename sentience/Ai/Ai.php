<?php

namespace Sentience\Ai;

use Sentience\Ai\Connectors\ConnectorInterface;

class Ai
{
    protected ConnectorInterface $connector;

    public static function connect(
        Connector $connector,
        string $baseUri,
        string $apiKey
    ): static {
        return new static(
            $connector,
            $baseUri,
            $apiKey
        );
    }

    public function __construct(
        Connector $connector,
        string $baseUri,
        string $apiKey
    ) {
        $this->connector = $connector->getConnector(
            $baseUri,
            $apiKey
        );
    }
}
