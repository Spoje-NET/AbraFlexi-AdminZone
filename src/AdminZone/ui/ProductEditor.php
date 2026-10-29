<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

use Ease\Html\H4Tag;
use Ease\Html\InputFileTag;
use Ease\Html\InputHiddenTag;
use Ease\Sand;
use Ease\TWB\Form;
use Ease\TWB\Panel;
use Ease\TWB\Row;
use Ease\TWB\SubmitButton;
use AbraFlexi\Cenik;
use AbraFlexi\Priloha;
use AbraFlexi\Stitek;
use AdminZone\ProductImager;

/**
 * Description of ProductEditor
 *
 * @author vitex
 */
class ProductEditor extends Panel
{
    public $product = null;

    /**
     * Item editor
     *
     * @param Cenik $product
     */
    public function __construct($product)
    {
        $product->defaultUrlParams['detail'] = 'full';
        $this->product = $product;

        $this->imageUpload('thumbnail');
        $this->imageUpload('image');

        $editForm = new Form(
            'product',
            'cenik.php',
            'POST',
            new InputHiddenTag('id', $product),
            ['enctype' => 'multipart/form-data']
        );

        $product->defaultUrlParams['detail'] = 'custom:poznam,id,stitky';
        $allAttachments = Priloha::getAttachmentsList($product);

        $images = Sand::reindexArrayBy($allAttachments, 'poznam');

        $editForm->addInput(
            new InputFileTag('thumbnail'),
            _('Thumbnail'),
            _('Small 150x150px')
        );

        if (array_key_exists('thumbnail', $images)) {
            $editForm->addItem(new ProductImage($product, 'thumbnail'));
        }

        $editForm->addInput(
            new InputFileTag('image'),
            _('Image'),
            _('Large image')
        );

        if (array_key_exists('image', $images)) {
            $editForm->addItem(new ProductImage($product, 'image'));
        }


        $editForm->addItem(new SubmitButton(_('Save'), 'success'));

        $titlerow = new Row();
        $titlerow->addColumn(8, new H4Tag($product->getDataValue('nazev')));
        $titlerow->addColumn(
            4,
            new LinkToAbraFlexiButton($product, ['style' => 'width:20px'])
        );

        parent::__construct(
            $titlerow,
            'warning',
            $editForm,
            new LabelGroup($product)
        );

        $labels = Stitek::getLabels($product);

        $this->addItem(new LabelSwitches($product));

        foreach ($allAttachments as $attachment) {
            ///  $this->addItem($attachment['data']);
        }

        $atributor = new \AbraFlexi\RW(null, ['evidence' => 'atribut']);
        $atribut = $atributor->getColumnsFromAbraFlexi(
            ['id', 'hodnota'],
            ['cenik' => $product, 'typAtributu' => 'code:API']
        );
        $apiModule = empty($atribut) ? '' : $atribut[0]['hodnota'];

        $argumentor = new \AbraFlexi\RW(null, ['evidence' => 'atribut']);
        $processorArgInfo = $argumentor->getColumnsFromAbraFlexi(
            ['id', 'hodnota'],
            ['cenik' => $product, 'typAtributu' => 'code:ARGUMENT']
        );

        $processorArgument = empty($processorArgInfo) ? '' : $processorArgInfo[0]['hodnota'];

        if (WebPage::isPosted()) {
            $apiModule = WebPage::getRequestValue('processor');
            $processorArgument = WebPage::getRequestValue('processorArg');

            if (!empty($atribut)) {
                $atributor->setMyKey($atribut[0]['id']);
            }

            $atributor->setDataValue('cenik', $product);
            $atributor->setDataValue('typAtributu', 'code:API');
            $atributor->setDataValue('valString', $apiModule);
            $atributor->setDataValue('hodnota', $apiModule);
            $this->addStatusMessage(sprintf(_('Processor %s assigned'), $apiModule), $atributor->sync() ? 'success' : 'error');

            $argumentor->setDataValue('cenik', $product);
            $argumentor->setDataValue('typAtributu', 'code:ARGUMENT');
            $argumentor->setDataValue('valString', $processorArguemnt);
            $argumentor->setDataValue('hodnota', $processorArguemnt);

            $this->addStatusMessage(sprintf(_('Processor argumnet %s save'), $apiModule), $argumentor->sync() ? 'success' : 'error');
        }

        $editForm->addInput(
            new ProcessorChooser('processor', $apiModule),
            _('Processor'),
            null,
            _('PHP module used to process settled invoice item')
        );
        $editForm->addInput(new InputTextTag('processorArg', $processorArgument));
    }

    /**
     *
     * @param type $field
     */
    public function imageUpload($field)
    {
        if (isset($_FILES[$field]) && strlen($_FILES[$field]['tmp_name'])) {
            $attachmentFile = $_FILES[$field]['tmp_name'];
            $attachmentFileName = sys_get_temp_dir() . '/' . $_FILES[$field]['name'];

            move_uploaded_file($attachmentFile, $attachmentFileName);

            $this->removeOldAttachment($field);

            $result = Priloha::addAttachmentFromFile(
                $this->product,
                $attachmentFileName
            );
            if ($result == 201) {
                $this->product->addStatusMessage(sprintf(
                    _('%s File %s was attached'),
                    $field,
                    basename($attachmentFileName)
                ), 'success');
                $this->setNewAttachment($field);
                $imager = new ProductImager($this->product->getMyKey());
                $imager->storeToCache(
                    [$_FILES[$field]['type'] => file_get_contents($attachmentFileName)],
                    $field
                );
            } else {
                $this->product->addStatusMessage(
                    'Attachment ' . $this->product->getAbraFlexiURL() . ' Failed',
                    'error'
                );
            }
            unlink($attachmentFileName);
        }
    }

    public function removeOldAttachment($field)
    {
        $response = null;
        //Remove label with $field note
        $this->product->defaultUrlParams['detail'] = 'full';
        $allAttachments = Priloha::getAttachmentsList($this->product);

        foreach ($allAttachments as $atId => $attachment) {
            if ($attachment['poznam'] == $field) {
                $response = $this->product->doCurlRequest(
                    $this->product->url . '/c/' . $this->product->company . '/priloha/',
                    'DELETE'
                );
            }
        }
        return $response;
    }

    public function setNewAttachment($field)
    {
        $this->product->defaultUrlParams['detail'] = 'id';
        $allAttachments = Priloha::getAttachmentsList($this->product);
        $attachment = end($allAttachments);
        $uploader = new Priloha();
        $response = $uploader->insertToAbraFlexi([
            'id' => $attachment['id'],
            'exportNaEshop' => true,
            'poznam' => $field]);
        return $response;
    }
}
