<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Person;
use AppBundle\Entity\WorkingGroup;

class WorkingGroupType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $workingGroup = $options['working_group'];

        $builder
            ->add('number', IntegerType::class, array(
                'label' => 'Group Number',
            ))
            ->add('gender', ChoiceType::class, array(
                'label' => 'Group of Women/Men',
                'choices' => array(
                    'Men' => Person::GENDER_MALE,
                    'Women' => Person::GENDER_FEMALE,
                ),
                'disabled' => $workingGroup->getId() > 0,
            ))
        ;
        if ($workingGroup->getId()) {
            $builder
                ->add('leader', EntityType::class, array(
                    'class' => 'AppBundle\Entity\Person',
                    'choice_label' => 'lastnameFirstnameAndDob',
                    'required' => false,
                    'query_builder' => function(EntityRepository $repo) use ($workingGroup) {
                        return $repo->createQueryBuilder('person')
                            ->select('person')
                            ->leftJoin('person.address', 'address')
                            ->where('person.gender = :gender')
                            ->orderBy('address.familyName')
                            ->addOrderBy('person.firstname')
                            ->setParameter('gender', $workingGroup->getGender())
                        ;
                    }
                ))
                ->add('persons', CollectionType::class, array(
                    'entry_type' => EntityType::class,
                    'label' => 'Persons',
                    'prototype' => true,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'widget_add_btn' => array('label' => 'Add Person'),
                    'widget_form_group' => true,
                    'entry_options' => array(
                        'label' => false,
                        'class' => 'AppBundle\Entity\Person',
                        'choice_label' => function (Person $person) {
                            return $person->getAddress()->getFamilyName() . ', ' . $person->getFirstname() . ' (' . $person->getDob()->format('d.m.Y') . ')';
                        },
                        'placeholder' => '',
                        'query_builder' => function(EntityRepository $repo) use ($workingGroup) {
                            return $repo->createQueryBuilder('person')
                                ->select('person')
                                ->leftJoin('person.address', 'address')
                                ->where('person.gender = :gender')
                                ->orderBy('person.workingGroup')
                                ->addOrderBy('address.familyName')
                                ->addOrderBy('person.firstname')
                                ->setParameter('gender', $workingGroup->getGender())
                            ;
                        },
                        'group_by' => 'optgroupLabelInWorkingGroupDropdown',
                    )
                ))
            ;
        }
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => WorkingGroup::class,
        ));

        $resolver->setRequired('working_group');
        $resolver->setAllowedTypes('working_group', WorkingGroup::class);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'workinggroup';
    }
}
