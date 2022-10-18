<?php
/**
 *
 */
namespace FishPig\LessPro\Framework\View\Design\Theme\Less;

class MediaQueryPrefixProvider
{
    /**
     *
     */
    public function __construct(
        array $prefixes = [
            'maxXS',
            'maxS',
            'maxM',
            'minM',
            'minL'
        ]
    ) {
        $this->prefixes = $prefixes;
    }

    /**
     *
     */
    public function getAll(): array
    {
        return $this->prefixes;
    }
}
