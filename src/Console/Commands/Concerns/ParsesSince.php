<?php

namespace JonesRussell\NorthCloud\Console\Commands\Concerns;

use Carbon\Carbon;

trait ParsesSince
{
    protected function parseSince(?string $since): ?Carbon
    {
        if (! $since) {
            return null;
        }

        if (preg_match('/^(\d+)h$/', $since, $m)) {
            return now()->subHours((int) $m[1]);
        }

        if (preg_match('/^(\d+)d$/', $since, $m)) {
            return now()->subDays((int) $m[1]);
        }

        return null;
    }
}
