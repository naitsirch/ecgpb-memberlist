<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ecgpb\MemberBundle\Exception\WorkingGroupWithoutLeaderException;

/**
 * Ecgpb\MemberBundle\Controller\ExportController
 *
 * @author naitsirch
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ExportController extends Controller
{   
    public function pdfAction()
    {
        $generator = $this->get('ecgpb.member.pdf_generator.member_list_generator');
        /* @var $generator \Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator */
        
        $pdf = $generator->generate();
        return new Response($pdf, 200, array(
            'Content-Type' => 'application/pdf',
            //'Content-Type' => 'application/octet-stream',
            //'Content-Disposition' => 'attachment; filename="ECGPB Member List.pdf"',
        ));
    }

    public function birthdayExcelXmlAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('EcgpbMemberBundle:Person');
        $persons = $repo->findAllForBirthdayList();

        $spreadsheet = $this->get('phpexcel')->createPHPExcelObject(); /* @var $spreadsheet \PHPExcel */
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValueByColumnAndRow(0, 1, 'Date of Birth');
        $worksheet->setCellValueByColumnAndRow(1, 1, 'Full Name');
        $worksheet->setCellValueByColumnAndRow(2, 1, 'Age');

        foreach ($persons as $index => $person) {
            $row = $index + 2;
            $worksheet->setCellValueByColumnAndRow(0, $row, $person->getDob()->format('d.m.Y'));
            $worksheet->setCellValueByColumnAndRow(1, $row, $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $worksheet->setCellValueByColumnAndRow(2, $row, date('Y') - $person->getDob()->format('Y'));
        }

        $writer = $this->get('phpexcel')->createWriter($spreadsheet, 'Excel2007');

        return $this->get('phpexcel')->createStreamedResponse($writer, 200, array(
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Birthday List.xlsx"',
        ));
    }
}