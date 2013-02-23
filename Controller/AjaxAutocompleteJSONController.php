<?php

namespace Shtumi\UsefulBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\HttpFoundation\Response;

class AjaxAutocompleteJSONController extends Controller
{

    public function getJSONAction()
    {
        $em = $this->get('doctrine')->getEntityManager();
        $request = $this->getRequest();

        $entities = $this->get('service_container')->getParameter('shtumi.autocomplete_entities');

        $entity_alias = $request->get('entity_alias');
        $entity_inf = $entities[$entity_alias];

        if (false === $this->get('security.context')->isGranted( $entity_inf['role'] )) {
            throw new AccessDeniedException();
        }

        $letters = $request->get('letters');
        $maxRows = $request->get('maxRows');

        $type = $request->query->get('type', $entity_inf['search']);

        switch ($type){
            case "begins_with":
                $operator   = 'LIKE';
                $parameter  = "{$letters}%";
            break;
            case "ends_with":
                $operator   = 'LIKE';
                $parameter  = "%{$letters}";
            break;
            case "contains":
                $operator   = 'LIKE';
                $parameter  = "%{$letters}%";
            break;
            case "equals":
                $operator   = '=';
                $parameter  = $letters;
            break;
            default:
                throw new \Exception('Unexpected value of parameter "search"');
        }

        $results = $em->createQuery
            (
                "SELECT   e.{$entity_inf['property']}
                 FROM       {$entity_inf['class']}                          e
                 WHERE    e.{$entity_inf['property']}       {$operator}     :parameter
                 ORDER BY e.{$entity_inf['property']}"
             )
            ->setParameter('parameter', $parameter )
            ->setMaxResults($maxRows)
            ->getScalarResult();

        $res = array();
        foreach ($results AS $r){
            $res[] = $r[$entity_inf['property']];
        }

        return new Response(json_encode($res));

    }
}
