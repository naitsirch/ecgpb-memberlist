<?php

namespace Ecgpb\MemberBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContactAssignmentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('ministry')
            ->add('person', 'entity', array(
                'class' => 'Ecgpb\MemberBundle\Entity\Person',
                'property' => 'lastnameAndFirstname',
                'required' => false,
            ))
            ->add('group', 'entity', array(
                'class' => 'Ecgpb\MemberBundle\Entity\Ministry\Group',
                'property' => 'name',
                'required' => false,
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['person']['id'])) {
                $data['person'] = $data['person']['id'];
            }
            if (isset($data['group']['id'])) {
                $data['group'] = $data['group']['id'];
            }
            $event->setData($data);
        });
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\Ministry\ContactAssignment'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'contactassignment';
    }
}
