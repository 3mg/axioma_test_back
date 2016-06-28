<?php

namespace AppBundle\Form;

use AppBundle\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;

class BookType extends AbstractType
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
            /*->add('translations', 'a2lix_translations', array(
                'fields' => array(                      // [3]
                    'title' => array(),
                    'description' => array(
                        'attr' => [
                            'rows' => 5,
                        ],
                    ),
                )
            ))*/
            ->add('title')
            ->add('description', 'textarea', [
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->add('authors', 'app_person', [
                'api_url' => $this->router->generate('authors_list', [ '_locale' => $this->currentLocale  ]),
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
            'data_class' => 'AppBundle\Entity\Book'
        ));
    }
}
