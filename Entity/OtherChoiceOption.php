<?php

namespace SumoCoders\FrameworkCoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use RuntimeException;

/**
 * @ORM\Entity(repositoryClass="SumoCoders\FrameworkCoreBundle\Repository\OtherChoiceOptionRepository")
 * @ORM\Table(
 *     name="OtherChoiceOption",
 *     indexes={@ORM\Index(name="category_index", columns={"category"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="unique_name_index", columns={"category", "label"})}
 * )
 */
class OtherChoiceOption
{
    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2)
     */
    private $locale;

    /**
     * Used to create new choice options in the form type
     *
     * @var string
     */
    private $newChoiceOptionCategory;

    public function __construct(string $category, string $label, string $locale)
    {
        if ($category === 'other') {
            throw new InvalidArgumentException('The category "other" is reserved');
        }

        $this->category = $category;
        $this->label = $label;
        $this->locale = $locale;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getValue(): string
    {
        if ($this->category === 'other') {
            return $this->category;
        }

        return $this->label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function __toString(): string
    {
        return $this->label;
    }

    public static function getOtherOption(string $label, string $locale): self
    {
        $otherOption = new self('tmp', $label, $locale);
        // hack to be able to set other while it is restricted;
        $otherOption->category = 'other';

        return $otherOption;
    }

    public function getWithNewChoiceOptionCategory(string $newChoiceOptionCategory): self
    {
        if ($this->category !== 'other') {
            throw new RuntimeException('This method can only be called when the category is other');
        }

        $choiceOption = clone $this;
        $choiceOption->newChoiceOptionCategory = $newChoiceOptionCategory;

        return $choiceOption;
    }

    /**
     * This method is used to transform an other choice option into the actual choice option
     *
     * @param string $label
     */
    public function transformToActualChoiceOption(string $label): void
    {
        if ($this->category !== 'other') {
            throw new RuntimeException('This method can only be called when the category is other');
        }

        $this->category = $this->newChoiceOptionCategory;
        $this->label = $label;
        $this->newChoiceOptionCategory = null;
    }
}
