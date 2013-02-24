<?php

/**
 * Date range filter
 *
 * @author Artem Ponomarenko <imenem@inbox.ru>
 */

namespace Shtumi\UsefulBundle\Filter;

use Shtumi\UsefulBundle\Form\Type\BetweenType,
    Sonata\DoctrineORMAdminBundle\Filter\Filter,
    Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

class DateRangeFilter extends Filter
{
    /**
     * @param ProxyQueryInterface $queryBuilder
     * @param string $alias
     * @param string $field
     * @param mixed $data
     *
     * @return
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!isset ($data['value']))
        {
            return;
        }

        //default type for range filter
        if (!isset($data['type']) || !is_numeric($data['type']))
        {
            $data['type'] =  BetweenType::TYPE_BETWEEN;
        }

        $start_param    = $this->getNewParameterName($queryBuilder);
        $end_param      = $this->getNewParameterName($queryBuilder);
        $aliased_field  = "{$alias}.{$field}";

        if ($data['type'] == BetweenType::TYPE_NOT_BETWEEN)
        {
            $this->applyWhere($queryBuilder, "{$aliased_field} < :{$start_param} OR {$aliased_field} > :{$end_param}");
        }
        else
        {
            $this->applyWhere($queryBuilder, "{$aliased_field} >= :{$start_param} AND {$aliased_field} <= :{$end_param}");
        }

        $queryBuilder->setParameter($start_param,   $data['value']->dateStart);
        $queryBuilder->setParameter($end_param,     $data['value']->dateEnd);
    }

    public function getDefaultOptions()
    {
        return array
        (
            'field_type'        => 'shtumi_daterange',
            'field_options'     =>  array(),
            'operator_type'     => 'shtumi_type_between',
            'operator_options'  =>  array(),
            'callback'          =>  null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array('sonata_type_filter_default', array
        (
            'field_type'        => $this->getFieldType(),
            'field_options'     => $this->getFieldOptions(),
            'operator_type'     => $this->getOption('operator_type'),
            'operator_options'  => $this->getOption('operator_options'),
            'label'             => $this->getLabel()
        ));
    }
}