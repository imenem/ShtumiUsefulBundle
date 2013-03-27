<?php

namespace Shtumi\UsefulBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Response;

class AjaxAutocompleteJSONController extends Controller
{
    public function getJSONAction()
    {
        $em             = $this->get('doctrine')->getEntityManager();
        $request        = $this->getRequest();
        $entities       = $this->get('service_container')->getParameter('shtumi.autocomplete_entities');
        $entity_alias   = $request->get('entity_alias');
        $entity_inf     = $entities[$entity_alias];

        if (false === $this->get('security.context')->isGranted( $entity_inf['role'] ))
        {
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

        if ($entity_inf['case_insensitive'])
        {
            $where = "WHERE   LOWER(e.{$entity_inf['property']})      {$operator}     LOWER(:parameter)";
        }
        else
        {
            $where = "WHERE         e.{$entity_inf['property']}       {$operator}           :parameter";
        }

        $results = $em->createQuery
            (
                "SELECT   e.{$entity_inf['property']}
                 FROM       {$entity_inf['class']}      e
                 {$where}
                 ORDER BY e.{$entity_inf['property']}"
             )
            ->setParameter('parameter', $parameter )
            ->setMaxResults($maxRows)
            ->getScalarResult();

        $options = array();

        foreach ($results AS $result)
        {
            $options[] = $result[$entity_inf['property']];
        }

        return new Response(json_encode($options));

    }
}
