<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Actor;
use AppBundle\Form\ActorType;

/**
 * Actor controller.
 *
 * @Route("/actor")
 */
class ActorController extends Controller
{
    /**
     * Lists all Actor entities.
     *
     * @Route("/", defaults={"page": 1}, name="actor_index")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="actor_index_paginated")
     * @Method("GET")
     */
    public function indexAction($page)
    {
        $em = $this->getDoctrine()->getManager();

        $actors = $em->getRepository('AppBundle:Actor')->getPaginated($page);

        return $this->render('actor/index.html.twig', array(
            'actors' => $actors,
        ));
    }

    /**
     * Creates a new Actor entity.
     *
     * @Route("/new", name="actor_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $actor = new Actor();
        $form = $this->createForm('AppBundle\Form\ActorType', $actor)
            ->add('saveAndCreateNew', 'Symfony\Component\Form\Extension\Core\Type\SubmitType');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $actor->mergeNewTranslations();
            $em->persist($actor);
            $em->flush();

            return $this->redirectToRoute('actor_show', array('id' => $actor->getId()));
        }

        return $this->render('actor/new.html.twig', array(
            'actor' => $actor,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Actor entity.
     *
     * @Route("/{id}", name="actor_show")
     * @Method("GET")
     */
    public function showAction(Actor $actor)
    {
        $deleteForm = $this->createDeleteForm($actor);

        return $this->render('actor/show.html.twig', array(
            'actor' => $actor,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Actor entity.
     *
     * @Route("/{id}/edit", name="actor_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Actor $actor)
    {
        $deleteForm = $this->createDeleteForm($actor);
        $editForm = $this->createForm('AppBundle\Form\ActorType', $actor);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $actor->mergeNewTranslations();
            $em->persist($actor);
            $em->flush();

            return $this->redirectToRoute('actor_edit', array('id' => $actor->getId()));
        }

        return $this->render('actor/edit.html.twig', array(
            'actor' => $actor,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Actor entity.
     *
     * @Route("/{id}", name="actor_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Actor $actor)
    {
        $form = $this->createDeleteForm($actor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($actor);
            $em->flush();
        }

        return $this->redirectToRoute('actor_index');
    }

    /**
     * Creates a form to delete a Actor entity.
     *
     * @param Actor $actor The Actor entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Actor $actor)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('actor_delete', array('id' => $actor->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
