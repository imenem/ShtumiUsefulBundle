<?php

/**
 * Description of DateRangeType
 *
 * @author shtumi
 */

namespace Shtumi\UsefulBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\ValueToStringTransformer;
use Shtumi\UsefulBundle\Form\DataTransformer\DateRangeToValueTransformer;

use Shtumi\UsefulBundle\Model\DateRange;

class DateRangeType extends AbstractType
{
    private $date_format;
    private $default_interval;
    private $daterange_separator;
    private $container;

    public function __construct($container, $parameters)
    {
        $this->date_format          = $parameters['date_format'];
        $this->default_interval     = $parameters['default_interval'];
        $this->daterange_separator  = $parameters['daterange_separator'];
        $this->container            = $container;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'field_options' => array(),
            'default'       => null,
            'compound'      => false,
        ));
    }

    public function getParent()
    {
        return 'field';
    }

    public function getName()
    {
        return 'shtumi_daterange';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        if (!isset($options['default'])){
            if ($options['required']){
                $dateRange = new DateRange($this->date_format);
                $dateRange->createToDate(new \DateTime, $this->default_interval);
            } else {
                $dateRange = null;
            }

        }
        else {
            $dateRange = $options['default'];
        }

        $options['default'] = $dateRange;


        $builder->appendClientTransformer(new DateRangeToValueTransformer(
            $this->date_format
        ));

        $builder->setData($options['default']);

        // Datepicker date format
        $searches = array('d', 'm', 'y', 'Y');
        $replaces = array('dd', 'MM', 'yy', 'yyyy');

        $datepicker_format = str_replace($searches, $replaces, $this->date_format);

        $builder->setAttribute('datepicker_date_format', $datepicker_format);
        $builder->setAttribute('datepicker_daterange_separator', $this->daterange_separator);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->set('datepicker_date_format', $form->getAttribute('datepicker_date_format'));
        $view->set('datepicker_daterange_separator', $form->getAttribute('datepicker_daterange_separator'));
        $view->set('locale', $this->container->get('request')->getLocale());

    }
}