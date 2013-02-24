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

class BetweenType extends BaseChoiceType
{
    const TYPE_BETWEEN = 1;
    const TYPE_NOT_BETWEEN = 2;

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
                self::TYPE_BETWEEN      => $this->translator->trans('label_date_type_between',      array(), 'SonataAdminBundle'),
                self::TYPE_NOT_BETWEEN  => $this->translator->trans('label_date_type_not_between',  array(), 'SonataAdminBundle'),
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
        return 'shtumi_type_between';
    }
}
