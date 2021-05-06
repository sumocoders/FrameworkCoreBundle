<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Knp\Menu\MenuFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DefaultMenuListener
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $securityAuthorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $securityTokenStorage;

    /**
     * @param AuthorizationCheckerInterface $securityAuthorizationChecker
     * @param TokenStorageInterface         $securityTokenStorage
     */
    public function __construct(
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage
    ) {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getSecurityAuthorizationChecker()
    {
        return $this->securityAuthorizationChecker;
    }

    /**
     * @return TokenStorageInterface
     */
    public function getSecurityTokenStorage()
    {
        return $this->securityTokenStorage;
    }

    /**
     * @param MenuFactory $menuFactory
     * @param string      $label
     * @param int         $order
     * @param array       $childs
     * @return \Knp\Menu\MenuItem
     */
    public function createItemWithChilds(MenuFactory $menuFactory, $label, $order, array $childs)
    {
        $menuItem = $menuFactory->createItem(
            $label,
            [
                'uri' => '#',
                'label' => $label,
            ]
        );

        $menuItem->setExtra('orderNumber', $order);

        // add the childs
        foreach ($childs as $key => $value) {
            // if the value is a string we can expect this is a simple child
            if (is_string($value)) {
                $child = $menuFactory->createItem(
                    $key,
                    [
                        'route' => $value,
                        'label' => $key,
                    ]
                );
            } else {
                $child = $value;
            }

            $menuItem->addChild($child);
        }

        return $menuItem;
    }
}
