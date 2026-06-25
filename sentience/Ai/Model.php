<?php

namespace Sentience\Ai;

use BackedEnum;
use Sentience\Ai\Connectors\ConnectorInterface;

class Model
{
    public function __construct(
        protected ConnectorInterface $connector,
        protected string|BackedEnum $model
    ) {
    }
}
