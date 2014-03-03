<?php

namespace Ecgpb\MemberBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Ecgpb\MemberBundle\Form\PersonType;
use Ecgpb\MemberBundle\Entity\WorkingGroup;

class WorkingGroupType extends AbstractType
{
    private $workingGroup;

    public function __construct(WorkingGroup $workingGroup)
    {
        $this->workingGroup = $workingGroup;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $workingGroup = $this->workingGroup;
        $builder
            ->add('number', 'integer', array(
                'label' => 'Group Number',
            ))
            ->add('gender', 'choice', array(
                'label' => 'Group of Women/Men',
                'choices' => array('m' => 'Men', 'f' => 'Women'),
                'read_only' => $workingGroup->getId() > 0,
            ))
        ;
        if ($workingGroup->getId()) {
            $builder
                ->add('leader', 'entity', array(
                    'class' => 'Ecgpb\MemberBundle\Entity\Person',
                    'property' => 'lastnameAndFirstname',
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
                ->add('persons', 'collection', array(
                    'type' => 'entity',
                    'label' => 'Persons',
                    'prototype' => true,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'widget_add_btn' => array('label' => 'Add Person'),
                    'widget_form_group' => true,
                    'options' => array(
                        'label' => false,
                        'class' => 'Ecgpb\MemberBundle\Entity\Person',
                        'property' => 'lastnameAndFirstname',
                        'query_builder' => function(EntityRepository $repo) use ($workingGroup) {
                            return $repo->createQueryBuilder('person')
                                ->select('person')
                                ->leftJoin('person.address', 'address')
                                ->where('person.gender = :gender')
                                ->orderBy('address.familyName')
                                ->addOrderBy('person.firstname')
                                ->setParameter('gender', $workingGroup->getGender())
                            ;
                        },
                    )
                ))
            ;
        }
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\WorkingGroup'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_workinggroup';
    }
}