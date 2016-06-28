<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Type;

use AppBundle\Entity\Author;
use AppBundle\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Knp\DoctrineBehaviors\Model\Translatable\Translatable;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'app_person';
    }

    /** @var $em \Doctrine\ORM\EntityManager */
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'entity_class' => Author::class,
            'entity_field' => 'name',
            'api_url' => '',
        ));
    }

    const MAX_LOADED_ENTITIES = 20;

    /** @var \Doctrine\Common\Collections\Collection */
    protected $selectedValues = [];
    
    /** @var \Doctrine\Common\Collections\Collection */
    protected $valuesList = [];

    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, "onPreSetData"]);
        $this->options = $options;

        $builder->addModelTransformer(new CallbackTransformer(
            function ($persons) {
                // return tag names
                return $persons;
            },
            function ($personNames) use ($options) {
                $persons = new ArrayCollection();
                foreach ($personNames as $personName) {
                    $qb = $this->em->getRepository($options['entity_class'])->createQueryBuilder("o");
                    $qb->leftJoin('o.translations', 't');
                    $qb->where($qb->expr()->eq("t.".$options['entity_field'], ":name"));
                    $qb->setParameter("name", $personName);
                    $qb->setMaxResults(1);

                    $person = $qb->getQuery()->getOneOrNullResult();
                    if (!is_object($person)) {
                        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();

                        /** @var Translatable $person */
                        $person = new $options['entity_class']();
                        $propertyAccessor->setValue($person, $options['entity_field'], $personName);
                        $person->mergeNewTranslations();
                    }
                    $persons->add($person);
                }
                return $persons;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['full_name'] .= '[]';
        $view->vars['selected_values'] = $this->selectedValues;
        $view->vars['values_list'] = $this->valuesList;
        $view->vars['attr'] += [
            'data-api-url' => $options['api_url'],
        ];
    }

    public function onPreSetData(FormEvent $event)
    {
        $this->selectedValues = $event->getData();

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;
        $qb = $em->getRepository($this->options['entity_class'])->createQueryBuilder("o");

        if ($this->selectedValues->count() > 0) {
            $qb->where("o.id NOT IN (:ids)");
            $qb->setParameter(
                "ids",
                $this->selectedValues->map(
                    function($o) {
                        return $o->getId();
                    }
                ));
        }
        $qb->setMaxResults(self::MAX_LOADED_ENTITIES);
        
        $this->valuesList = $qb->getQuery()->getResult();
    }
}
