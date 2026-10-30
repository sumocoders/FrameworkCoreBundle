<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Melbahja\Seo\MetaTags;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;

#[ORM\Embeddable]
class Meta
{
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pageTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    /** @var list<RobotsDirective>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, enumType: RobotsDirective::class)]
    private ?array $robots = null;

    private ?MetaTags $metaTags = null;

    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(?string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /** @return list<RobotsDirective> */
    public function getRobots(): array
    {
        return $this->robots ?? [];
    }

    /** @param list<RobotsDirective> $robots */
    public function setRobots(array $robots): self
    {
        $this->robots = $robots;

        return $this;
    }

    public function getMetaTagsInstance(): MetaTags
    {
        if ($this->metaTags instanceof MetaTags) {
            return $this->metaTags;
        }

        $this->metaTags = new MetaTags();

        if ($this->pageTitle !== null && $this->pageTitle !== '') {
            $this->metaTags->og('title', $this->pageTitle)->twitter('title', $this->pageTitle);
        }

        if ($this->metaDescription !== null && $this->metaDescription !== '') {
            $this->metaTags->description($this->metaDescription);
        }

        $robots = $this->getRobots();
        if (count($robots) >= 1) {
            // @mago-expect analysis:possible-method-access-on-null
            $this->metaTags->robots(array_map(static fn (RobotsDirective $r): string => $r->value, $robots));
        }

        // @mago-expect analysis:nullable-return-statement,invalid-return-statement
        return $this->metaTags;
    }
}
