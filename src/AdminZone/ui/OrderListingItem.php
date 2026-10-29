<?php

namespace AdminZone\ui;

/**
 * Description of OrderListingItem
 *
 * @author vitex
 */
class OrderListingItem extends \Ease\TWB\Row
{
    public function __construct($itemdata, $evidence)
    {
        $fmt = new \NumberFormatter('cs_CZ', \NumberFormatter::CURRENCY);
        parent::__construct();
        $this->addTagClass('row-hover');
//        $this->addColumn(2,
//            new \Ease\Html\ATag('order.php?id='.$itemdata['id'],
//            $itemdata['kod']));

        $this->addColumn(
            2,
            new \Ease\Html\StrongTag(new \Ease\Html\ATag(
                'history.php?id=' . $itemdata['id'] . '&evidence=' . $evidence . '&embed=true',
                $itemdata['kod']
            ))
        );

        if (array_key_exists('datSplat', $itemdata)) {
            $ods = \AbraFlexi\FakturaVydana::overdueDays($itemdata['datSplat']);
        } else {
            $ods = 0;
        }

        if (array_key_exists('datSplat', $itemdata) && !empty($itemdata['datSplat']) && ($ods > 0)) {
            $penale = (($itemdata['sumCelkem'] / 100) * 0.05) * $ods;
        } else {
            $penale = 0;
        }

        if (empty($itemdata['juhSum'])) {
            $itemdata['juhSum'] = 0;
        }

        $amount = (( floatval($itemdata['sumCelkem']) != floatval($itemdata['juhSum'])) ? '<small><strong style="color: red">' . $fmt->formatCurrency(($itemdata['juhSum']), \AbraFlexi\Functions::uncode($itemdata['mena'])) . '</strong>  </small> z ' : '' ) .
                $fmt->formatCurrency($itemdata['sumCelkem'], \AbraFlexi\Functions::uncode($itemdata['mena']));

        $this->addColumn(2, $amount);

        if (array_key_exists('datSplat', $itemdata)) {
            $this->addColumn(1, !empty($itemdata['datSplat']) ? $itemdata['datSplat']->format('d.m.Y') : '');
        }
        if ($itemdata['storno'] == 'true') {
            $column2 = new \Ease\TWB\Label('default', _('Storno'));
        } else {
            switch ($evidence) {
                case 'faktura-prijata':
                case 'faktura-vydana':
                    if (strstr($itemdata['stavUhrK'], 'stavUhr.uhrazeno')) {
                        $column2 = new \Ease\TWB\Label('success', _('Settled'));
                    } else {
                        $column2 = self::dueLabel($itemdata['datSplat']) . ' ' . $penale;
                    }
                    break;
                case 'objednavka-vydana':
                case 'objednavka-prijata':
                    if ($itemdata['stavUzivK'] == 'stavDoklObch.hotovo') {
                        $column2 = new \Ease\TWB\Label('success', _('Done'));
                    } else {
                        $column2 = new \Ease\TWB\Label(
                            'warning',
                            _('Open') . ': ' . str_replace(
                                'stavDoklObch.',
                                '',
                                $itemdata['stavUzivK']
                            )
                        );
                    }
                    break;
                default:
                    $column2 = '';
                    break;
            }
        }
        $this->addColumn(2, $column2);

        $links = [
            new \Ease\Html\ATag(
                'document.php?id=' . $itemdata['id'] . '&embed=true&evidence=' . $evidence,
                new \Ease\Html\ImgTag('images/oko.svg', _('Show'), ['height' => '20px']),
                ['title' => _('Show')]
            )
            ,
            new \Ease\Html\ATag(
                'getpdf.php?id=' . $itemdata['id'] . '&evidence=' . $evidence,
                new \Ease\Html\ImgTag('images/download.svg', _('Download'), ['height' => '20px']),
                ['title' => _('Download')]
            )
            ,
            new \Ease\Html\ATag(
                'maildocument.php?id=' . $itemdata['id'] . '&evidence=' . $evidence,
                new \Ease\Html\ImgTag(
                    'images/email.svg',
                    _('Send'),
                    ['height' => '20px']
                ),
                ['title' => _('Send')]
            )
        ];

        if ($evidence == 'faktura-vydana') {
            $links[] = new \Ease\Html\ATag(
                'getisdoc.php?id=' . $itemdata['id'] . '&evidence=' . $evidence,
                new \Ease\Html\ImgTag(
                    'images/ISDOC.png',
                    _('ISDOC'),
                    ['height' => '20px']
                ),
                ['title' => _('ISDOC')]
            );
        }

        switch (get_class(\Ease\Shared::user())) {
            case 'AdminZone\User':
                $links[] = new \Ease\Html\ATag(
                    constant('ABRAFLEXI_URL') . '/c/' . constant('ABRAFLEXI_COMPANY') . '/' . $evidence . '/' . $itemdata['id'],
                    new \Ease\Html\ImgTag(
                        'images/abraflexi.png',
                        _('AbraFlexi'),
                        ['height' => '20px']
                    ),
                    ['title' => _('AbraFlexi')]
                );

                $companyName = $itemdata['firma']->showAs ? $itemdata['firma']->showAs : \AbraFlexi\Functions::uncode($itemdata['firma']);

                $links[] = new \Ease\Html\ATag('adresar.php?id=' . $itemdata['firma'], [
                    new \Ease\Html\ImgTag(
                        'images/company.svg',
                        _('Company'),
                        ['height' => '20px', 'title' => $companyName]
                    ),
                    $companyName
                        ], ['title' => _('Company')]);

                if (
                    array_key_exists(
                        'API',
                        \AbraFlexi\Stitek::listToArray($itemdata['stitky'])
                    )
                ) {
                    $links[] = new \Ease\Html\ATag(
                        'executeapi.php?evidence=' . $evidence . '&id=' . $itemdata['id'],
                        new \Ease\Html\ImgTag(
                            'images/api.svg',
                            _('Exec API Action'),
                            ['height' => '20px', 'title' => 'API'],
                            'success'
                        ),
                        ['title' => _('Exec API')]
                    );
                }

                break;
        }

        $this->addColumn(4, $links);
    }

    static function price($data)
    {
        switch ($data['']) {
            case '':
                break;
        }


        return new \Ease\TWB\Label($type, $content);
    }

    static function dueLabel($date)
    {
        $days = \AbraFlexi\FakturaVydana::overdueDays($date);
        if ($days < 0) {
            $type = 'success';
            $msg = sprintf(
                _(' %s days to due'),
                new \Ease\TWB\Badge(abs($days))
            );
        } else {
            $msg = sprintf(
                _(' %s days after due'),
                new \Ease\TWB\Badge(abs($days))
            );
            if ($days > 14) {
                $type = 'danger';
            } else {
                $type = 'warning';
            }
        }
        return new \Ease\TWB\Label($type, $msg);
    }
}
