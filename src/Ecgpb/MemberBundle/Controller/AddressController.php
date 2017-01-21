<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ecgpb\MemberBundle\Entity\Address;
use Ecgpb\MemberBundle\Form\AddressType;

/**
 * Address controller.
 * @/Security("has_role('ROLE_ADMIN')")
 */
class AddressController extends Controller
{

    /**
     * Lists all Address entities.
     *
     * @Route("/index", name="ecgpb.member.address.index", defaults={"_locale"="de"})
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $personHelper = $this->get('person_helper'); /* @var $personHelper \Ecgpb\MemberBundle\Helper\PersonHelper */

        $repo = $em->getRepository('EcgpbMemberBundle:Address'); /* @var $repo \Ecgpb\MemberBundle\Repository\AddressRepository */

        $filter = $request->get('filter', array());
        if (!empty($filter['no-photo'])) {
            $filter['no-photo'] = $personHelper->getPersonIdsWithoutPhoto();
        }

        $pagination = $this->get('knp_paginator')->paginate(
            $repo->getListFilterQb($filter),
            $request->query->get('page', 1)/*page number*/,
            15, /*limit per page*/
            array(
                'wrap-queries' => true,
                'defaultSortFieldName' => array('address.familyName', 'person.dob'),
                'defaultSortDirection' => 'asc',
            )
        );

        return $this->render('EcgpbMemberBundle:Address:index.html.twig', array(
            'pagination' => $pagination,
            'person_ids_without_photo' => $personHelper->getPersonIdsWithoutPhoto(),
            //'persons_with_picture' => $personsWithPicture,
        ));
    }
    /**
     * Creates a new Address entity.
     *
     * @Route("/create", name="ecgpb.member.address.create", defaults={"_locale"="de"})
     * @Method({"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new Address();
        $form = $this->createAddressForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            
            $this->get('session')->getFlashBag()->add('success', 'The entry has been created.');

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $entity->getId())));
        }

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Address entity.
     *
     * @Route("/new", name="ecgpb.member.address.new", defaults={"_locale"="de"})
     */
    public function newAction()
    {
        $entity = new Address();
        $form   = $this->createAddressForm($entity);

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Address entity.
     *
     * @Route("/{id}/edit", name="ecgpb.member.address.edit", defaults={"_locale"="de"})
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EcgpbMemberBundle:Address')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createAddressForm($entity);

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing Address entity.
     *
     * @Route("/{id}/update", name="ecgpb.member.address.update", defaults={"_locale"="de"})
     * @Method({"POST", "PUT"})
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $address = $em->getRepository('EcgpbMemberBundle:Address')->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $form = $this->createAddressForm($address);
        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($address->getRemovedEntities() as $removedEntity) {
                $em->remove($removedEntity);
            }
            
            $em->flush();

            // person picture file
            $personHelper = $this->get('person_helper'); /* @var $personHelper \Ecgpb\MemberBundle\Helper\PersonHelper */
            foreach ($request->files->get('person-picture-file', array()) as $index => $file) {
                /* @var $file UploadedFile */
                if ($file) {
                    $person = $address->getPersons()->get($index);
                    $file->move($personHelper->getPersonPhotoPath(), $personHelper->getPersonPhotoFilename($person));
                }
            }
            
            $this->get('session')->getFlashBag()->add('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $id)));
        }

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity' => $address,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Deletes a Address entity.
     *
     * @Route("/{id}/delete", name="ecgpb.member.address.delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $address = $em->getRepository('EcgpbMemberBundle:Address')->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        foreach ($address->getPersons() as $person) {
            if ($person->getLeaderOf()) {
                $person->getLeaderOf()->setLeader(null);
            }
        }

        $em->remove($address);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'The entry has been deleted.');

        if ($referrer = $request->headers->get('referer')) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->generateUrl('ecgpb.member.address.index'));
    }

    /**
    * Creates a form to create a Address entity.
    *
    * @param Address $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createAddressForm(Address $entity)
    {
        $url = $entity->getId() > 0
            ? $this->generateUrl('ecgpb.member.address.update', array('id' => $entity->getId()))
            : $this->generateUrl('ecgpb.member.address.create')
        ;
        $form = $this->createForm(new AddressType(), $entity, array(
            'action' => $url,
            'method' => 'POST',
            'attr' => array(
                'enctype' => 'multipart/form-data',
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }
}
