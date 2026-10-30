<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\DataTransferObject;

use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;

final class SeoDataTransferObject
{
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        public bool $overridePageTitle = false,
        public ?string $pageTitle = null,
        public bool $overrideMetaDescription = false,
        public ?string $metaDescription = null,
        public ?RobotsDirective $robotsIndex = null,
        public ?RobotsDirective $robotsFollow = null,
        /** @var list<RobotsDirective> */
        public array $robotsAdditional = [],
    ) {
    }

    public static function fromMeta(Meta $meta): self
    {
        $robots = $meta->getRobots();

        return new self(
            overridePageTitle: $meta->getPageTitle() !== null,
            pageTitle: $meta->getPageTitle(),
            overrideMetaDescription: $meta->getMetaDescription() !== null,
            metaDescription: $meta->getMetaDescription(),
            robotsIndex: self::firstOf($robots, [RobotsDirective::Index, RobotsDirective::NoIndex]),
            robotsFollow: self::firstOf($robots, [RobotsDirective::Follow, RobotsDirective::NoFollow]),
            robotsAdditional: array_values(array_filter(
                $robots,
                static fn (RobotsDirective $robot): bool => !in_array(
                    $robot,
                    [
                        RobotsDirective::Index,
                        RobotsDirective::NoIndex,
                        RobotsDirective::Follow,
                        RobotsDirective::NoFollow,
                    ],
                    true,
                ),
            )),
        );
    }

    public function applyTo(Meta $meta): void
    {
        $meta->setPageTitle($this->overridePageTitle ? $this->pageTitle : null);
        $meta->setMetaDescription($this->overrideMetaDescription ? $this->metaDescription : null);
        $meta->setRobots(array_values(array_filter([
            $this->robotsIndex,
            $this->robotsFollow,
            ...$this->robotsAdditional,
        ])));
    }

    /**
     * @param list<RobotsDirective> $robots
     * @param list<RobotsDirective> $candidates
     */
    private static function firstOf(array $robots, array $candidates): ?RobotsDirective
    {
        return array_find(
            $candidates,
            static fn (RobotsDirective $candidate): bool => in_array($candidate, $robots, true),
        );
    }
}
