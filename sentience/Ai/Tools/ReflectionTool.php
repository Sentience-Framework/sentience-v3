<?php

namespace Sentience\Ai\Tools;

class ReflectionTool
{
    public function __construct(
        protected callable|ToolInterface $tool
    ) {
    }
}
