<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Actor;
use AppBundle\Entity\Author;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Book;
use AppBundle\Form\BookType;

/**
 *
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     *
     * @Route("/tag", name="tag_list")
     * @Method({"GET"})
     */
    public function getTagsAction(Request $request)
    {
        $q = $request->get("q");
        $page = $request->get("page", 1);
        $size = $request->get("size", 30);

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get("doctrine")->getManager();
        $qb = $em->getRepository("AppBundle:Tag")->createQueryBuilder("tag");
        if ($q) {
            $qb->where("tag.name LIKE :q")->setParameter("q", "%{$q}%");
        }
        $qb->andWhere("tag.locale = :locale");
        $qb->setParameter("locale", $request->getLocale());

        $cntQb = clone($qb);
        $cntQb->select("COUNT(tag)");
        $cnt = $cntQb->getQuery()->getSingleScalarResult();

        $qb->setMaxResults($size);
        $qb->setFirstResult(($page - 1) * $size);

        $qb->select("tag.name");
        $items = array_map(
            function($row) {
                return [
                    "id" => $row["name"],
                    "text" => $row["name"],
                ];
            },
            $qb->getQuery()->getArrayResult()
        );

        return new JsonResponse([
            "items" => $items,
            "total_count" => $cnt,
        ]);
    }
    /**
     *
     * @Route("/author", name="authors_list")
     * @Route("/actor", name="actors_list")
     * @Method({"GET"})
     */
    public function getPresonsAction(Request $request)
    {
        if($request->attributes->get("_route") == "authors_list") {
            $class = Author::class;
        } else {
            $class = Actor::class;
        }

        $q = $request->get("q");
        $page = $request->get("page", 1);
        $size = $request->get("size", 30);

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get("doctrine")->getManager();
        $qb = $em->getRepository($class)->createQueryBuilder("o");
        $qb->leftJoin('o.translations', 't');
        if ($q) {
            $qb->where("t.name LIKE :q")->setParameter("q", "%{$q}%");
        }

        $cntQb = clone($qb);
        $cntQb->select("COUNT(DISTINCT o)");
        $cnt = $cntQb->getQuery()->getSingleScalarResult();

        $qb->setMaxResults($size);
        $qb->setFirstResult(($page - 1) * $size);

        $qb->select("t.name");
        $items = array_map(
            function($row) {
                return [
                    "id" => $row["name"],
                    "text" => $row["name"],
                ];
            },
            $qb->getQuery()->getArrayResult()
        );

        return new JsonResponse([
            "items" => $items,
            "total_count" => $cnt,
        ]);
    }
}
