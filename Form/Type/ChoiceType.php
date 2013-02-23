<?php
/**
 * Choice mode type for ajax filter
 *
 * @author Artem Ponomarenko <imenem@inbox.ru>
 */

namespace Shtumi\UsefulBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType,
    Symfony\Component\Translation\TranslatorInterface,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceType extends BaseChoiceType
{
    const TYPE_BEGINS_WITH  = 'begins_with';
    const TYPE_ENDS_WITH    = 'ends_with';
    const TYPE_CONTAINS     = 'contains';

    protected $translator;

    /**
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array
        (
            'choices' => array
            (
                self::TYPE_BEGINS_WITH  => $this->translator->trans('label_type_begins_with',   array(), 'SonataAdminBundle'),
                self::TYPE_ENDS_WITH    => $this->translator->trans('label_type_ends_with',     array(), 'SonataAdminBundle'),
                self::TYPE_CONTAINS     => $this->translator->trans('label_type_contains',      array(), 'SonataAdminBundle')
            )
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'shtumi_type_choice';
    }
}
