<?php
/**
 *
 */

namespace OmniTools\Persistence\Entity\Trait;

trait Uid
{
    /**
     *
     */
    public function getUid(string $section): string
    {
        return $this->table . '-' . $this->getId() . '-' . $section;
    }
}
