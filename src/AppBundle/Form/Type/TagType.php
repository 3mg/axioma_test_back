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

use AppBundle\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
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
use Symfony\Component\Routing\Router;

class TagType extends AbstractType
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
        return 'app_tag';
    }

    /** @var $em \Doctrine\ORM\EntityManager */
    protected $em;

    protected $currentLocale;

    public function __construct(\Doctrine\ORM\EntityManager $em, RequestStack $request_stack)
    {
        $this->em = $em;
        $this->currentLocale = $request_stack->getMasterRequest()->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'api_url' => '',
        ));
    }

    const MAX_LOADED_TAGS = 20;

    /** @var \Doctrine\Common\Collections\Collection */
    protected $selectedTags = [];
    
    /** @var \Doctrine\Common\Collections\Collection */
    protected $tagList = [];

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, "onPreSetData"]);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($tags) {
                // return tag names
                return $tags;
            },
            function ($tagNames) {
                $tags = new ArrayCollection();
                foreach ($tagNames as $tagName) {
                    $tag = $this->em->getRepository("AppBundle:Tag")->findOneBy([
                        "name" => $tagName,
                        "locale" =>$this->currentLocale,
                    ]);
                    if (!$tag instanceof Tag) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $tag->setLocale($this->currentLocale);
                    }
                    $tags->add($tag);
                }
                return $tags;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['full_name'] .= '[]';
        $view->vars['selected_tags'] = $this->selectedTags;
        $view->vars['tag_list'] = $this->tagList;
        $view->vars['attr'] += [
            'data-api-url' => $options['api_url'],
        ];
    }

    public function onPreSetData(FormEvent $event)
    {
        $this->selectedTags = $event->getData();

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;
        $qb = $em->getRepository("AppBundle:Tag")->createQueryBuilder("tag");

        if ($this->selectedTags->count() > 0) {
            $qb->where("tag.id NOT IN (:ids)");
            $qb->setParameter(
                "ids",
                $this->selectedTags->map(
                    function($tag) {
                        return $tag->getId();
                    }
                ));
        }
        $qb->andWhere("tag.locale = :locale");
        $qb->setParameter("locale", $this->currentLocale);
        $qb->setMaxResults(self::MAX_LOADED_TAGS);
        
        $this->tagList = $qb->getQuery()->getResult();
    }
}
