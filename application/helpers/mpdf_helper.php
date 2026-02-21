<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 *
 * eInvoicing add-ons by Verony
 */

use horstoeko\zugferd\ZugferdPdfWriter;
use horstoeko\zugferd\ZugferdSettings; 
use mikehaertl\pdftk\Pdf;

/**
 * Converts a plain PDF to PDF/A-3b (without attachments).
 * use horstoeko zugpferd library
 * @param string $sourcePdf Source PDF file name
 * @param string $destPdf Destination PDF file name (can be the same as the source)
 * @param string $title Title metadata
 * @param string $author Author metadata
 * @param string $creatorTool Creator tool metadata
 * @return void
 */

function convert_pdf_to_pdfa(string $sourcePdf, string $destPdf, string $title, string $author, string $ass_file_name='', string $ass_file_path='', string $creatorTool='')
{
    $pdfWriter = new ZugferdPdfWriter();

    // Copy pages from the original PDF
    $pageCount = $pdfWriter->setSourceFile($sourcePdf);

    for ($pageNumber = 1; $pageNumber <= $pageCount; ++$pageNumber) {
        $pageContent = $pdfWriter->importPage($pageNumber, '/MediaBox');
        $pdfWriter->AddPage();
        $pdfWriter->useTemplate($pageContent, 0, 0, null, null, true);
    }

    // Set PDF version 1.7 according to PDF/A-3 ISO 32000-1
    $pdfWriter->setPdfVersion('1.7', true);

    // Update meta data (e.g. such as author, producer, title)
    $pdfMetadata = array(
            'author' => $author,
            'keywords' => '',
            'title' => $title,
            'subject' => '',
            'createdDate' => date('Y-m-d\TH:i:s') . '+00:00',
            'modifiedDate' => date('Y-m-d\TH:i:s') . '+00:00',
            );
    $pdfWriter->setPdfMetadataInfos($pdfMetadata);

    $xmp = simplexml_load_file(ZugferdSettings::getFullXmpMetaDataFilename());
    $descriptionNodes = $xmp->xpath('rdf:Description');

    // rdf:Description urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#
    // $descriptionNodes[0] not applicable

    // Factur-X PDFA Extension Schema http://www.aiim.org/pdfa/ns/extension/
    // $descriptionNodes[1] not applicable

    // rdf:Description http://www.aiim.org/pdfa/ns/id/
    // PDF/A-3b declaration
    $descPdfAid = $descriptionNodes[2];
    $pdfWriter->addMetadataDescriptionNode($descPdfAid->asXML());

    // rdf:Description http://purl.org/dc/elements/1.1/
    $descDc = $descriptionNodes[3];
    $descNodes = $descDc->children('dc', true);
    $descNodes->title->children('rdf', true)->Alt->li = $pdfMetadata['title'];
    $descNodes->creator->children('rdf', true)->Seq->li = $pdfMetadata['author'];
    $descNodes->description->children('rdf', true)->Alt->li = $pdfMetadata['subject'];
    $pdfWriter->addMetadataDescriptionNode($descDc->asXML());

    // rdf:Description http://ns.adobe.com/pdf/1.3/
    $descAdobe = $descriptionNodes[4];
    $descAdobe->children('pdf', true)->{'Producer'} = 'Xtra-PDF';
    $pdfWriter->addMetadataDescriptionNode($descAdobe->asXML());

    // rdf:Description http://ns.adobe.com/xap/1.0/
    $descXmp = $descriptionNodes[5];
    $xmpNodes = $descXmp->children('xmp', true);
    $xmpNodes->{'CreatorTool'} = $creatorTool;
    $xmpNodes->{'CreateDate'} = $pdfMetadata['createdDate'];
    $xmpNodes->{'ModifyDate'} = $pdfMetadata['modifiedDate'];
    $pdfWriter->addMetadataDescriptionNode($descXmp->asXML());

    // add zugpferd/erechnung xml again because it is now removed
    if (!empty($ass_file_name)) {
        $pdfWriter->attach($ass_file_path, $ass_file_name);
    }

    $pdfWriter->Output($destPdf, 'F');
}

/**
 * Create a PDF.
 *
 * @param      $html
 * @param      $filename
 * @param bool $stream           (show or download)
 * @param bool $embed_xml        (eInvoicing)
 * @param null $associated_files (eInvoicing)
 *
 * @return string
 *
 * @throws \Mpdf\MpdfException
 */
function pdf_create(
    $html,
    string $filename,
    bool $stream = true,
    $password = null,
    $isInvoice = null,
    $is_guest = null,
    bool $embed_xml = false,
    ?array $associated_files = [],

    $pdf_stamp = "",
    $additionalFooter =""
) {
    $CI = & get_instance();


    // Get the invoice from the archive if available
    $invoice_array = [];

    // mPDF loading - original code
    //$mpdf = new \Mpdf\Mpdf([
    //    'tempDir' => UPLOADS_TEMP_MPDF_FOLDER,
    //]);
    // special footer with page numeration
    $invoiceNrAndPageOnFooter = env_bool('INVOICE_PAGE_FOOTER_XTRA');

    // size, margin by special footer
    if ($invoiceNrAndPageOnFooter == true ) {
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4',
    'margin_left'   => 19,
    'margin_right'  => 10,
    'margin_top'    => 40,
    'margin_bottom' => 22,
    'margin_header' => 10,   // <- wichtig
    'margin_footer' => 7,
                'tempDir' => UPLOADS_TEMP_MPDF_FOLDER,
        ]);
    } else {
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4',
                'margin_left'   => 19,
                'margin_right'  => 10,
                'margin_top'    => 10,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 15,
                'tempDir' => UPLOADS_TEMP_MPDF_FOLDER
        ]);
    }    

    // change font by chrissie
    //./vendor/mpdf/mpdf/ttfonts/Raleway-Medium.ttf
    // change default dejavusanscondensed
    // to raleway - your mileage may vary
    $mpdf->fontdata=[];

    if (ip_atac() ) {		// atac uses FaktPro
        $mpdf->fontdata['dejavusanscondensed'] = [
            'R' => 'FaktPro-Normal_bulletmod.ttf',
            'I' => 'FaktPro-SemiBold.ttf',
            'B' => 'Faktatac-SemiBold.ttf' ];
        // dejavuserifcondensed needed for watermark
        $mpdf->fontdata['dejavuserifcondensed'] = [
            'R' => 'FaktPro-Normal_bulletmod.ttf',
            'I' => 'FaktPro-SemiBold.ttf',
            'B' => 'Faktatac-SemiBold.ttf' ];
    } elseif (ip_mari()) {	// marishine uses Raleway
        $mpdf->fontdata['dejavusanscondensed'] = [
            'R' => 'Raleway-Medium.ttf',
            'I' => 'Raleway-Italic.ttf',
            'B' => 'Raleway-Bold.ttf' ];
        // dejavuserifcondensed needed for watermark
        $mpdf->fontdata['dejavuserifcondensed'] = [
            'R' => 'Raleway-Medium.ttf',
            'I' => 'Raleway-Italic.ttf',
            'B' => 'Raleway-Bold.ttf' ];
    } else {	// X-Tra-Designs uses default Arial
        $mpdf->fontdata['dejavusanscondensed'] = [
            'R' => 'arial.ttf',
            'I' => 'ariali.ttf',
            'B' => 'arialbd.ttf' ];
        // dejavuserifcondensed needed for watermark
        $mpdf->fontdata['dejavuserifcondensed'] = [
            'R' => 'arial.ttf',
            'I' => 'ariali.ttf',
            'B' => 'arialbd.ttf' ];
    }

    // mPDF configuration
    $mpdf->useAdobeCJK      = true;
    $mpdf->autoScriptToLang = true;
    $mpdf->autoVietnamese   = true;
    $mpdf->autoArabic       = true;
    $mpdf->autoLangToFont   = true;

    if (IP_DEBUG) {
        // Enable image error logging
        $mpdf->showImageErrors = true;
    }

    // eInvoicing: Include (embedded) XML if enabled for the client
    if ($embed_xml) {
        $CI->load->helper('e-invoice');
        // mpdf only creates PDF/A-1b files and cannot create the required PDF/A-3b files!
        $mpdf->pdf_version = '1.7';
        $mpdf->PDFA        = true;
        $mpdf->PDFAauto    = true;
        $mpdf->SetAssociatedFiles($associated_files);
        $mpdf->SetAdditionalXmpRdf(include_rdf($associated_files[0]['name']));
    }

    // Set a password if set for the voucher
    if ( ! empty($password)) {
        $mpdf->SetProtection(['copy', 'print'], $password, $password);
    }

    // Check if the archive folder is available
    if ( ! is_dir(UPLOADS_ARCHIVE_FOLDER) || is_link(UPLOADS_ARCHIVE_FOLDER) && ( ! mkdir(UPLOADS_ARCHIVE_FOLDER, '0777') && ! is_dir(UPLOADS_ARCHIVE_FOLDER))) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', UPLOADS_ARCHIVE_FOLDER));
    }

    //Set the default footer that shall always be available for mPDF
    $mpdf->DefHTMLFooterByName('defaultFooter', '');

    // Set the footer if voucher is invoice and if set in settings
    if ($isInvoice && ! empty($CI->mdl_settings->settings['pdf_invoice_footer'])) {
        $mpdf->setAutoBottomMargin = 'stretch';
        $mpdf->DefHTMLFooterByName('footerWithPageNumbers', '<div id="footer">' . $CI->mdl_settings->settings['pdf_invoice_footer'] . '</div><div><p align="center">' . str_replace('_', ' ', $filename) . ' - ' . trans('page') . ' {PAGENO} / {nbpg}</p></div>');
        $mpdf->DefHTMLFooterByName('footer', '<div id="footer">' . $CI->mdl_settings->settings['pdf_invoice_footer'] . '</div>');
        $mpdf->DefHTMLFooterByName('defaultFooter', '<div id="footer">' . $CI->mdl_settings->settings['pdf_invoice_footer'] . '</div>');
    }

    // Set the footer if voucher is quote and if set in settings
    if ( ! $isInvoice && ! empty($CI->mdl_settings->settings['pdf_quote_footer'])) {
        $mpdf->setAutoBottomMargin = 'stretch';
        $mpdf->DefHTMLFooterByName('footerWithPageNumbers', '<div id="footer">' . $CI->mdl_settings->settings['pdf_invoice_footer'] . '</div><div id="footer">' . $CI->mdl_settings->settings['pdf_quote_footer'] . '</div>');
        $mpdf->DefHTMLFooterByName('footer', '<div id="footer">' . $CI->mdl_settings->settings['pdf_quote_footer'] . '</div>');
        $mpdf->DefHTMLFooterByName('defaultFooter', '<div id="footer">' . $CI->mdl_settings->settings['pdf_quote_footer'] . '</div>');
    }

    // by chrissie: special page nr footer and addidional footer
    $xtrafooter="";
    if ($isInvoice) {
        if (!empty($additionalFooter)) {
            $xtrafooter .= '<div id="footer"><p align="center">'.$additionalFooter.'</p></div>';
        }
        if ($invoiceNrAndPageOnFooter == true) {
            $my_invoice_nr = "";
            if (!empty($CI->load->_ci_cached_vars['invoice']->invoice_number))
                $my_invoice_nr = "Rechnung Nr. ".$CI->load->_ci_cached_vars['invoice']->invoice_number." / ";
            $xtrafooter .= '<div id="footer"><p align="right">'.$my_invoice_nr.' Seite {PAGENO} von {nbpg}</p></div>';
        }
    }
    // END special page footer
    $mpdf->SetHTMLFooterByName('defaultFooter');


    // Watermark (eInvoicing++ PDFA and PDFX do not permit transparency, so mPDF does not allow Watermarks!)
    if ( ! $embed_xml && get_setting('pdf_watermark')) {
        $mpdf->showWatermarkText = true;
    }

    // html debugging by chrissie - increases your invoice designing speed
    if(0) {
        echo ' <div style="width:210mm; margin:auto; border:1px solid #ccc;">';
        echo $html;
        echo '</div>';
        exit;
    }
    // anotther hardcore debug test
    if(0) {
	$mpdf->SetHTMLFooter('<div style="color:red">FOOTER TEST</div>');
        $mpdf->SetHTMLHeader('<div style="border:1px solid red">HEADER</div>');
        $mpdf->SetHTMLFooter($f);
	$mpdf->WriteHTML('<p></p>');
    }

    // here is a serious new bug in mpdf new version 
    // it works only if i do it this way and send empty p as final
    // maybe i am wrong but this way it works
    if (!empty ($xtrafooter)) {
        $mpdf->SetHTMLFooter($xtrafooter);
	$mpdf->WriteHTML('<p></p>');
    }

    try {
        $mpdf->WriteHTML((string) $html);
    } catch (Exception $e) {
        log_message('error', $e->getMessage());
        show_error($e->getMessage());
    }


    if ($isInvoice) {
        // invoice copy by chrissie with special watermark
        $invoice_copy = env_bool('INVOICE_COPY');
        $invoice_copy_watermark = env('INVOICE_COPY_WATERMARK');

        // only return archived files when no copy
        if ($invoice_copy != true) {

            $pdfFiles = glob(UPLOADS_ARCHIVE_FOLDER . '*' . $filename . '.pdf');

            foreach ($pdfFiles as $file) {
                $invoice_array[] = $file;
            }

            if ($invoice_array !== [] && null !== $is_guest) {
                rsort($invoice_array);

                if ($stream) {
                    return $mpdf->Output($filename . '.pdf', 'I');
                }

                return $invoice_array[0];
            }
        }

        // generate new pdf
        //$archived_file = UPLOADS_ARCHIVE_FOLDER . date('Y-m-d') . '_' . $filename . '.pdf';
	$archived_file = UPLOADS_ARCHIVE_FOLDER . $filename . '.pdf';
        $mpdf->Output($archived_file, 'F');

        if ($invoice_copy == true) {
            //$archived_file_copy = UPLOADS_ARCHIVE_FOLDER . date('Y-m-d') . '_' . $filename . '-copy.pdf';
 	    $archived_file_copy = UPLOADS_ARCHIVE_FOLDER . $filename . '-copy.pdf';
            $xpdf = new \Mpdf\Mpdf([
                    'tempDir' => UPLOADS_TEMP_MPDF_FOLDER
            ]);
            $xpdf->SetWatermarkText($invoice_copy_watermark);
            $xpdf->showWatermarkText = true;
            $pagecount = $xpdf->SetSourceFile($archived_file);
            $tplId = $xpdf->importPage($pagecount);
            $xpdf->useTemplate($tplId);
            $xpdf->Output($archived_file_copy, 'F');
        }

        // pdf stamping invoice by chrissie
        if(!empty($pdf_stamp) && file_exists( UPLOADS_CFILES_FOLDER . $pdf_stamp)) {
            $pdf = new Pdf($archived_file);     // here java-pdftk via mikehaertl is being used
            $error="";
            if(!$pdf->multiStamp( UPLOADS_CFILES_FOLDER . $pdf_stamp)
                ->saveAs($archived_file)
                ) {
                        $error = $pdf->getError();
                        echo "PDFTK Error: <br>\n";
                        echo nl2br($error);
                        die();
                }

            // invoice copy by chrissie with watermark 'COPY'
            if ($invoice_copy == true) {
                // stamping of copy
                if(!empty($pdf_stamp) && file_exists( UPLOADS_CFILES_FOLDER . $pdf_stamp)) {
                    $pdf = new Pdf($archived_file_copy);
                    $pdf->multiStamp( UPLOADS_CFILES_FOLDER . $pdf_stamp)
                        ->saveAs($archived_file_copy);
                }

                // concatenate both pdf
                $pdf = new Pdf();
                $pdf->addFile($archived_file);
                $pdf->addFile($archived_file_copy);
                $pdf->saveAs($archived_file_copy);

                $archived_file = $archived_file_copy;
            }
        }

        // generate a new pdf/3a by chrissie only for invoice.
        $invoide_pdf3a = env('INVOICE_PDF3A');
        if ($invoide_pdf3a == true) {
            //$archived_file_a = UPLOADS_ARCHIVE_FOLDER . date('Y-m-d') . '_' . $filename . '-A.pdf';
            $archived_file_a = UPLOADS_ARCHIVE_FOLDER . $filename . '-A.pdf';

                $zhugferd_invoice = 0; // was just test - fix or remove later chrissie
            if ($zugferd_invoice) {
                convert_pdf_to_pdfa($archived_file, $archived_file_a, "Title", "Author", $associated_files[0]['name'], $associated_files[0]['path']) ;
            } else {
                convert_pdf_to_pdfa($archived_file, $archived_file_a, "Title", "Author");
            }

            // now copy over new generated file
            copy($archived_file_a, $archived_file);
        }
        // end pdf/3a

        // using readfile, setting header!
        if ($stream) {
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');

            @readfile ($archived_file);
            return;
        } else {
            return $archived_file;
        }

    } // END $isInvoice

    // generate new pdf : other files but not invoice
    $t = UPLOADS_TEMP_FOLDER . $filename . '.pdf';
    $mpdf->Output($t, 'F');

    // pdf stamping other by chrissie
    if(!empty($pdf_stamp) && file_exists( UPLOADS_CFILES_FOLDER . $pdf_stamp)) {
        $pdf = new Pdf($t);	// here pdftk is being used
        $pdf->multiStamp( UPLOADS_CFILES_FOLDER . $pdf_stamp)
            ->saveAs($t);
    }

    // If $stream is true (default) the PDF will be displayed directly in the browser
    // otherwise will be returned as a download
    if ($stream) {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        @readfile ($t);
    } else {
        return $t;
    }

}
