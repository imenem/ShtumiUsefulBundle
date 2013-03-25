<?php

namespace Shtumi\UsefulBundle\Controller;

use Sonata\AdminBundle\Admin\FieldDescriptionInterface,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Response,
    InvalidArgumentException;

class DependentFilteredEntityController extends Controller
{
    /**
     * Класс сущности, для которой ведется фильтрация
     *
     * @var string
     */
    protected $class;

    /**
     * Свойство и направление для сортировки.
     * - property - имя свойства
     * - direction - направление
     *
     * @var array
     */
    protected $order_by;

    /**
     * Имя метода репозитория, которому будет передан QueryBuilder
     *
     * @var string
     */
    protected $callback;

    /**
     * Менеджер сущностей
     *
     * @var Sonata\AdminBundle\Model\ModelManagerInterface
     */
    protected $model_manager;

    /**
     * Сервис для создания запроса
     *
     * @var Doctrine\ORM\QueryBuilder
     */
    protected $query_builder;

    /**
     * Сервис для определения типа свойства
     *
     * @var Sonata\AdminBundle\Guesser\TypeGuesserInterface
     */
    protected $type_guesser;

    /**
     * Сервис для создания фильтров
     *
     * @var Sonata\AdminBundle\Filter\FilterFactoryInterface
     */
    protected $filter_factory;

    /**
     * Описание фильтра
     *
     * @var array
     */
    protected $entity_info;

    /**
     * Данные для фильтрации переданные с клиента
     *
     * @var array
     */
    protected $request_info;

    /**
     * Метод производит фильтрацию списка сущности по ее свойствам
     *
     * @return      \Symfony\Component\HttpFoundation\Response       Список сущностей в виде HTML-тегов <option>
     */
    public function getOptionsAction()
    {
        $this->configure();
        $this->applyFilter($this->entity_info['parent_property'], $this->request_info['parent_id']);
        $this->applyCallback();
        $this->applyOrderBy();

        $results = $this->query_builder->getQuery()->getResult();

        if (empty($results))
        {
            return new Response('<option value="">' . $this->translator->trans($this->entity_info['no_result_msg']) . '</option>');
        }

        $html = '';

        if ($this->request_info['empty_value'])
        {
            $html .= '<option value="">' . $this->translator->trans($this->request_info['empty_value']) . '</option>';
        }

        foreach($results as $result)
        {
            if ($this->entity_info['property'])
            {
                $res = $this->property_accessor->getValue($result, $this->entity_info['property']);
            }
            else
            {
                $res = (string) $result;
            }

            $html = $html . sprintf("<option value=\"%d\">%s</option>",$result->getId(), $res);
        }

        return new Response($html);
    }

    /**
     * Метод подготавливает окружение для фильтрации
     *
     * @throws AccessDeniedException        Пользователю запрещено использовать фильтр
     */
    protected function configure()
    {
        $this->request      = $this->getRequest();

        $entities           = $this->get('service_container')->getParameter('shtumi.dependent_filtered_entities');
        $entity_alias       = $this->request->get('entity_alias');

        $this->entity_info  = $entities[$entity_alias];

        if (!$this->get('security.context')->isGranted($this->entity_info['role']))
        {
            throw new AccessDeniedException();
        }

        $this->class                = $this->entity_info['class'];
        $this->model_manager        = $this->get('sonata.admin.manager.orm');
        $this->type_guesser         = $this->get('sonata.admin.guesser.orm_datagrid');
        $this->filter_factory       = $this->get('sonata.admin.builder.filter.factory');
        $this->property_accessor    = $this->get('property_accessor');
        $this->translator           = $this->get('translator');
        $this->query_builder        = $this->model_manager->createQuery($this->class);

        $this->order_by['property']     = $this->entity_info['order_property'];
        $this->order_by['direction']    = $this->entity_info['order_direction'];

        $this->callback                 = $this->entity_info['callback'];

        $this->request_info['parent_id']    = $this->request->get('parent_id');
        $this->request_info['empty_value']  = $this->request->get('empty_value');
    }

    /**
     * Метод добавляет к запросу условия для фильтрации
     *
     * @param       string      $property           Полное имя свойства (могут быть использованы связанные сущности)
     * @param       scalar      $property_value     Значение свойства
     */
    protected function applyFilter($property, $property_value)
    {
        $field_name = substr(strrchr(".{$property}", '.'), 1);
        $field_desc = $this->model_manager
                           ->getNewFieldDescriptionInstance($this->class, $property, ['field_name' => $field_name]);

        $this->applyType($field_desc);
        $this->applyMetadata($field_desc);

        $filter  = $this->filter_factory
                        ->create($field_desc->getName(), $field_desc->getType(), $field_desc->getOptions());

        $filter->apply($this->query_builder, ['value' => $property_value]);
    }

    /**
     * Метод добавляет к описанию свойства его тип и параметры, необходимые для запроса
     *
     * @param   Sonata\AdminBundle\Admin\FieldDescriptionInterface    $field_desc   Описание свойства
     */
    protected function applyType(FieldDescriptionInterface $field_desc)
    {
        $guess_type = $this->type_guesser
                           ->guessType($this->class, $field_desc->getName(), $this->model_manager);

        $field_desc->setType($guess_type->getType());

        foreach ($guess_type->getOptions() as $option_name => $option_value)
        {
            if (is_array($option_value))
            {
                $field_desc->setOption($option_name, array_merge($option_value, $field_desc->getOption($option_name, array())));
            }
            else
            {
                $field_desc->setOption($option_name, $field_desc->getOption($option_name, $option_value));
            }
        }
    }

    /**
     * Метод добавляет к описанию свойства метаданные ORM
     *
     * @param   Sonata\AdminBundle\Admin\FieldDescriptionInterface    $field_desc   Описание свойства
     */
    protected function applyMetadata(FieldDescriptionInterface $field_desc)
    {
        list($metadata, $name, $parent) = $this->model_manager
                                               ->getParentMetadataForProperty($this->class,
                                                                              $field_desc->getName());

        // set the default field mapping
        if (isset($metadata->fieldMappings[$name]))
        {
            $field_desc->setOption('field_mapping',
                                   $field_desc->getOption('field_mapping',
                                                          $metadata->fieldMappings[$name]));
        }

        // set the default association mapping
        if (isset($metadata->associationMappings[$name]))
        {
            $field_desc->setOption('association_mapping',
                                   $field_desc->getOption('association_mapping',
                                                          $metadata->associationMappings[$name]));
        }

        $field_desc->setOption('parent_association_mappings',
                               $field_desc->getOption('parent_association_mappings',
                                                      $parent));
    }

    /**
     * Метод передает запрос в метод репозитория сущности
     *
     * @throws InvalidArgumentException     Метод репозитория не найден
     */
    protected function applyCallback()
    {
        if (empty($this->callback))
        {
            return;
        }

        $repository = $this->get('doctrine')
                           ->getEntityManager()
                           ->getRepository($this->class);

        $callback = [$repository, $this->callback];

        if (!is_callable($callback))
        {
            throw new InvalidArgumentException(sprintf('Callback function "%s" in Repository "%s" does not exist.',
                                                       $callback, get_class($repository)));
        }

        $callback($this->query_builder);
    }

    /**
     * Метод добавляет к запросу параметры для сортировки
     */
    protected function applyOrderBy()
    {
        $field = "{$this->query_builder->getRootAlias()}.{$this->order_by['property']}";

        $this->query_builder->orderBy($field, $this->order_by['direction']);
    }
}
