<?php

namespace Ecgpb\MemberBundle\PdfGenerator;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\TwigEngine;
use Ecgpb\MemberBundle\Entity\Person;
use Ecgpb\MemberBundle\Helper\PersonHelper;

/**
 * Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator
 *
 * @author naitsirch
 */
class MemberListGenerator extends Generator implements GeneratorInterface
{
    const GRID_ROW_MIN_HEIGHT = 12; // mm
    const GRID_PICTURE_CELL_WIDTH = 10.5; // mm

    private $doctrine;
    private $translator;
    private $templating;
    private $personHelper;
    private $parameters;
    
    public function __construct(
        RegistryInterface $doctrine,
        TranslatorInterface $translator,
        TwigEngine $templating,
        PersonHelper $personHelper,
        array $parameters
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->personHelper = $personHelper;
        $this->parameters = $parameters;
    }
    
    /**
     * @return string
     */
    public function generate()
    {
        $pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetTitle('ECGPB Member List');
        $pdf->SetMargins(9, 9, 9);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 9);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);
        
        $this->addCover($pdf);
        $this->addPage1($pdf);
        $this->addPage2($pdf);
        $this->addAddressPages($pdf);

        return $pdf->Output(null, 'S');
    }
    
    private function addCover(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        // initiate XY positions
        $margins = $pdf->getMargins();
        $pdf->SetX($margins['left']);
        $pdf->SetY($margins['top']);
        
        // logo
        $src = realpath(__DIR__ . '/../Resources/public/img/logo.png');
        //$pdf->Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
        $pdf->Image($src, $pdf->GetX(), $pdf->GetY(), 30, 19, 'PNG', null, 'N', true, 300);
        
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetLineWidth(0.75);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());
        
        $pdf->SetXY($pdf->GetX() + 3, $pdf->GetY() + 2);
        $pdf->Text($pdf->GetX(), $pdf->GetY(), $this->parameters['ecgpb.contact.name'], false, false, true, 0, 1);
        
        $pdf->SetFontSize(40);
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 50, "Mitgliederliste", false, false, true, 0, 1, 'C');
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 10, date('Y'), false, false, true, 0, 1, 'C');
    }
    
    private function addPage1(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        // picture of church front
        $src = realpath(__DIR__ . '/../Resources/public/img/church_front.png');
        $pdf->Image($src, $pdf->GetX() + 10, $pdf->GetY(), 100, 81, 'PNG', null, 'N', true, 300);
        $pdf->SetY($pdf->GetY() + 5);
        
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.name']);
        $this->useFontWeightNormal($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.street']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.zip'] . ' ' . $this->parameters['ecgpb.contact.city']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.main_phone']);
        $this->useFontSizeM($pdf);
        $this->writeText($pdf, 'Homepage: www.ecgpb.de');
        
        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Bankverbindung');
        $this->useFontWeightNormal($pdf);
        $this->writeText($pdf, 'Sparkasse Paderborn');
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Kontonummer:')->setWidth(50)->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.account_number'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Bankleitzahl:')->setWidth(50)->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.code'])->setWidth(50)->end()
                ->end()
            ->end()
        ;

        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Stand: 01.01.' . date('Y'));
        $this->useFontWeightNormal($pdf);
        
        $pdf->SetY(190);
        $this->useFontSizeS($pdf);
        $this->writeText($pdf, 'Alle Änderungen bitte unverzüglich bei ' .
            $this->parameters['ecgpb.contact.memberlist.responsible'] .
            ' melden!'
        );
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'gemeindeliste@ecgpb.de');
        $this->useFontWeightNormal($pdf);
    }
    
    private function addPage2(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Telefonverbindungen des Gemeindehauses');
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Haupteingang')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.main_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Büro')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.office_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('LifeTime')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.lifetime_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Gefährdetenhilfe PB')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.gfh_phone'])->setWidth(50)->end()
                ->end()
            ->end()
        ;
        
        // library logo
        $pdf->SetY($pdf->GetY() + 10);
        $src = realpath(__DIR__ . '/../Resources/public/img/library_logo.png');
        $pdf->Image($src, $pdf->GetX() + 10, $pdf->GetY(), 40, 18, 'PNG', null, 'T', true, 300, 'R');
        
        $pdf->SetY($pdf->GetY() - 1);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Bibliothek');
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Telefon')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Email')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_email'])->setWidth(50)->end()
                ->end()
            ->end()
        ;
        
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontSizeM($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Öffnungszeiten');
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Mittwoch:')->setWidth(35)->end()
                    ->newCell('18:30 - 20:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Donnerstag:')->setWidth(35)->end()
                    ->newCell('18:00 - 20:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Sonntag:')->setWidth(35)->end()
                    ->newCell('11:45 - 12:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('An Ferien- und Feiertagen geschlossen')->setColspan(2)->end()
                ->end()
            ->end()
        ;
        
        $pdf->SetLineWidth(0.25);
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());
        
        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Mitgliederstand am 01.01.' . date('Y'));
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->useFontSizeM($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Gesamtmitgliederzahl')->setWidth(60)->end()
                    ->newCell('?')->setWidth(30)->end()
                ->end()
                ->newRow()
                    ->newCell('Davon männlich:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Davon weiblich:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder ab 65 Jahren:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder bis 25 Jahren:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Altersdurchschnitt:')->end()
                    ->newCell('?')->end()
                ->end()
            ->end()
        ;
    }
    
    public function addAddressPages(\TCPDF $pdf)
    {
        $addresses = $this->getAddresses();
        
        $table = null;
        $totalHeight = 0;
        
        foreach ($addresses as $address) {
            /* @var $address \Ecgpb\MemberBundle\Entity\Address */
            if (0 == $totalHeight) {
                $pdf->AddPage();
                $table = $this->addTable($pdf);
                $table
                    ->setFontSize(self::FONT_SIZE_S)
                    ->newRow()
                        ->newCell()
                            ->setText($this->translator->trans('Name, Address, Phone'))
                            ->setBorder(1)
                            ->setColspan(2)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('First Name'))
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('DOB'))
                            ->setAlign('C')
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('Mobile, Email'))
                            ->setAlign('C')
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                    ->end()
                ;
            }
            
            // calculate address row height and check if address fitts on this page
            $addressRowHeight = 0;
            foreach ($address->getPersons() as $person) {
                $addressRowHeight += 12;
                // TODO: if there is additional text in cell, it must be added here
            }
            if (count($address->getPersons()) == 1) {
                $addressRowHeight += 12;
            }
            $totalHeight += $addressRowHeight;
            
            if ($totalHeight > 185) {
                $table->end();
                $totalHeight = 0;
            }
            
            // add rows and cells to table
            $persons = $address->getPersons();
            if (count($persons) == 1) {
                $persons[] = null; // dummy entry for second row
            }
            foreach ($persons as $index => $person) {
                /* @var $person \Ecgpb\MemberBundle\Entity\Person */
                $row = $table->newRow();
                if (0 == $index) {
                    $row->newCell()
                            ->setText($address->getFamilyName() . "\n" . $address->getPhone())
                            ->setBorder('LTR')
                            ->setFontWeight('bold')
                            ->setLineHeight(1.3)
                            ->setWidth(35.5)
                            ->setPadding(0.75, 0.75, 0, 0.75)
                        ->end()
                    ;
                } else if (1 == $index) {
                    $row->newCell()
                            ->setText($address->getStreet() . "\n" . $address->getZip() . ' ' . $address->getCity())
                            ->setBorder(count($persons) <= 2 ? 'LRB' : 'LR')
                            ->setFontSize(self::FONT_SIZE_XS)
                            ->setFontWeight('normal')
                            ->setLineHeight(1.2)
                            ->setWidth(35.5)
                            ->setPadding(0, 0.75, 0.75, 0.75)
                        ->end()
                    ;
                } else {
                    $row->newCell()
                            ->setText(' ')
                            ->setBorder(count($persons) - 1 == $index ? 'LRB' : 'LR')
                            ->setWidth(35.5)
                        ->end()
                    ;
                }

                $row
                    ->newCell()
                        ->getBackground()
                            ->setDpi(300)
                            ->setFormatter($this->getPersonPictureFormatter($person))
                        ->end()
                        ->setBorder(1)
                        ->setWidth(self::GRID_PICTURE_CELL_WIDTH) // 10.5 mm
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getFirstname() : '')
                        ->setBorder(1)
                        ->setFontWeight('bold')
                        ->setPadding(0.75)
                        ->setWidth(22)
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getDob()->format('d.m.Y') : '')
                        ->setAlign('C')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS)
                        ->setFontWeight('normal')
                        ->setPadding(0.75)
                        ->setWidth(19)
                    ->end()
                    ->newCell()
                        ->setText(
                            ($person && $person->getPhone2() ? $person->getPhone2() . "\n" : '') .
                            ($person && $person->getMobile() ? $person->getMobile() . "\n" : '') .
                            ($person && $person->getEmail() ? $person->getEmail() : '')
                        )
                        ->setAlign('C')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS)
                        ->setFontWeight('normal')
                        ->setMinHeight(self::GRID_ROW_MIN_HEIGHT)
                        ->setPadding(0.75)
                        ->setWidth(44.5)
                    ->end()
                ;
                $row->end();
            }
        }
    }
    
    /**
     * Returns all addresses with the corresponding persons.
     * @return \Ecgpb\MemberBundle\Entity\Address[]
     */
    private function getAddresses()
    {
        $em = $this->doctrine->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address');
        /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        
        return $builder->getQuery()->getResult();
    }

    public function getPersonPictureFormatter(Person $person = null)
    {
        if (!$person) {
            return null;
        }

        $filename = $this->personHelper->getPersonPhotoFilename($person);
        $filenameOriginal = $this->personHelper->getPersonPhotoPath() . '/' . $filename;
        $photoPathOptimized = $this->personHelper->getPersonPhotoPathOptimized();

        return function(\Tcpdf\Extension\Attribute\BackgroundFormatterOptions $options) use ($filename, $filenameOriginal, $photoPathOptimized) {
            if (!file_exists($filenameOriginal)) {
                $options->setImage(null);
                return;
            }

            $filenameOptimized = $photoPathOptimized . '/'
                . number_format(round($options->getMaxWidth(), 4), 4) . 'x'
                . number_format(round($options->getMaxHeight(), 4), 4) . '/' . $filename
            ;
            if (!is_dir(dirname($filenameOptimized)) && !mkdir(dirname($filenameOptimized), 0777)) {
                throw new \RuntimeException('No permissions to create the directory "'.dirname($filenameOptimized).'".');
            }

            $options->setImage($filenameOptimized);
            
            if (!file_exists($filenameOptimized) || filemtime($filenameOriginal) > filemtime($filenameOptimized)) {
                list($widthOriginal, $heightOriginal) = getimagesize($filenameOriginal);
                $dpi = $widthOriginal / ($options->getMaxWidth() / 25.4);
                if ($dpi > 300) {
                    $dpi = 300;
                }

                $dstWidth = $options->getMaxWidth() / 25.4 * $dpi;
                $dstHeight = $options->getMaxHeight() / 25.4 * $dpi;

                $factor = $dstWidth / $dstHeight;

                if ($factor > $widthOriginal / $heightOriginal) {
                    $width = $widthOriginal;
                    $height = $widthOriginal / $factor;
                } else {
                    $width = $heightOriginal * $factor;
                    $height = $heightOriginal;
                }

                $imageOriginal = imagecreatefromjpeg($filenameOriginal);
                $imageSnippet = imagecreatetruecolor($width, $height);
                imagecopy($imageSnippet, $imageOriginal, 0, 0, ($widthOriginal - $width) / 2, ($heightOriginal - $height) / 2, $width, $height);
                imagedestroy($imageOriginal);
                $imageOptimized = imagecreatetruecolor($dstWidth, $dstHeight);
                imagecopyresized($imageOptimized, $imageSnippet, 0, 0, 0, 0, $dstWidth, $dstHeight, $width, $height);
                imagedestroy($imageSnippet);
                imagejpeg($imageOptimized, $filenameOptimized, 95);
                imagedestroy($imageOptimized);

                $options->setDpi($dpi);
            }
        };
    }
}
