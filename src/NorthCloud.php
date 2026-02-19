<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud;

class NorthCloud
{
    /** @var list<array{title: string, route: string, icon: string}> */
    private array $registeredNavItems = [];

    /**
     * @param  list<array{title: string, route: string, icon: string}>  $items
     */
    public function registerNavigation(array $items): void
    {
        foreach ($items as $item) {
            $this->registeredNavItems[] = $item;
        }
    }

    /**
     * @return list<array{title: string, route: string, icon: string}>
     */
    public function getRegisteredNavigation(): array
    {
        return $this->registeredNavItems;
    }
}
