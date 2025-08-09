<?php

declare(strict_types=1);

$dateTime = new DateTime();

$dateTime->add(DateInterval::createFromDateString('100000000 seconds'));

echo $dateTime->format(DateTime::RFC3339_EXTENDED);
