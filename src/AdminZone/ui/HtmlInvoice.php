<?php

namespace AdminZone\ui;

/**
 * Description of htmlInvoice
 *
 * @author vitex
 */
class HtmlInvoice extends \Ease\Container
{
    /**
     * Gives you html AbraFlexi invoice
     *
     * @param \AbraFlexi\RO $invoice
     */
    public function __construct($invoice)
    {
        $pdfFile = $invoice->downloadInFormat(
            'pdf',
            '/var/lib/abraflexi-adminzone/tmp/'
        );
        if (strlen($pdfFile)) {
            \Gufy\PdfToHtml\Config::set(
                'pdftohtml.output',
                '/var/lib/abraflexi-adminzone/tmp'
            );
            $pdf = new \Gufy\PdfToHtml\Pdf($pdfFile);
            preg_match(
                "/<body[^>]*>(.*?)<\/body>/is",
                $pdf->html(),
                $initialContent
            );
        }
        parent::__construct($initialContent);
        //   \AdminZone\ui\WebPage::singleton()->includeCss('css/abraflexi.css');
    }
}
