<?php

namespace Sentience\Ai;

enum Role: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
}
