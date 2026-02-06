<?php

namespace JonesRussell\NorthCloud\Contracts;

interface ArticleModel
{
    public function getExternalId(): string;

    public function getTitle(): string;

    public function getUrl(): ?string;

    public function getStatus(): string;

    public function isPublished(): bool;
}
