<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

trait NamedMetadataTrait /* implements NamedMetadata */
{
    private string $name;
    private ?string $comment = null;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
