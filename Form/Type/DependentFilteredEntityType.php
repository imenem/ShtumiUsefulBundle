<?php

namespace Shtumi\UsefulBundle\Form\Type;

use Shtumi\UsefulBundle\Form\DataTransformer\EntityToIdTransformer,
    Doctrine\Common\Persistence\ObjectManager,
    Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormInterface,
    Symfony\Component\Form\FormView,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DependentFilteredEntityType extends AbstractType
{
    protected $entity_manager;
    protected $entities_info;

    public function __construct(ObjectManager $entity_manager, array $entities_info)
    {
        $this->entity_manager = $entity_manager;
        $this->entities_info  = $entities_info;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'empty_value'       => '',
            'entity_alias'      => null,
            'compound'          => false
        ));
    }

    public function getParent()
    {
        return 'field';
    }

    public function getName()
    {
        return 'shtumi_dependent_filtered_entity';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $alias          = $options['entity_alias'];
        $class          = $this->entities_info[$alias]['class'];
        $no_result      = $this->entities_info[$alias]['no_result_msg'];
        $depends_from   = $this->entities_info[$alias]['depends_from'];

        $transformer = new EntityToIdTransformer($this->entity_manager, $class);
        $builder->prependClientTransformer($transformer);

        $builder->setAttribute('depends_from',  $depends_from);
        $builder->setAttribute('entity_alias',  $alias);
        $builder->setAttribute('no_result_msg', $no_result);
        $builder->setAttribute('empty_value',   $options['empty_value']);

    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->set('depends_from',  $form->getAttribute('depends_from'));
        $view->set('entity_alias',  $form->getAttribute('entity_alias'));
        $view->set('no_result_msg', $form->getAttribute('no_result_msg'));
        $view->set('empty_value',   $form->getAttribute('empty_value'));
    }

}