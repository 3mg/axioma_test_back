<?php

namespace AppBundle\Entity\Translation;

use Knp\DoctrineBehaviors\Model as ORMBehaviors;

trait TranslatableTrait
{
    use ORMBehaviors\Translatable\Translatable;

    public function __get($name) {
        $translation = $this->translate();
        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

        return $propertyAccessor->getValue($translation, $name);
    }
    public function __set($name, $value) {
        $translation = $this->translate();
        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

        return $propertyAccessor->setValue($translation, $name, $value);
    }

    public function __call($method, $arguments)
    {
        $translation = $this->translate();
        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

        if (count($arguments) == 0) {
            return $propertyAccessor->getValue($translation, $method);
        } else {
            return $propertyAccessor->setValue($translation, $method, $arguments[0]);
        }
    }
}