<?php

namespace AppBundle\Form;

use AppBundle\Entity\Actor;
use AppBundle\Entity\Movie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

class MovieType extends AbstractType
{
    /** @var  Router */
    protected $router;

    /** @var  RequestStack */
    protected $request_stack;

    protected $currentLocale;

    /**
     * BookType constructor.
     * @param Router $router
     * @param RequestStack $request_stack
     */
    public function __construct(Router $router, RequestStack $request_stack)
    {
        $this->router = $router;
        $this->currentLocale = $request_stack->getMasterRequest()->getLocale();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('description', 'textarea', [
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->add('quality', 'choice', [
                'choices' => Movie::getQualities(),
            ])
            ->add('actors', 'app_person', [
                'entity_class' => Actor::class,
                'api_url' => $this->router->generate('actors_list', [ '_locale' => $this->currentLocale  ]),
            ])
            ->add('tags', 'app_tag', [
                'api_url' => $this->router->generate('tag_list', [ '_locale' => $this->currentLocale  ]),
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Movie'
        ));
    }
}
