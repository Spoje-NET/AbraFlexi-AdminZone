<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 *
 * @author vitex
 */
trait SearcherForInput
{
    /**
     * Add Search ability to Given Input
     *
     * @param \Ease\Html\InputTextTag $input
     *
     * @return \Ease\Html\InputTextTag
     */
    public function searchize(&$input)
    {
        $this->addJs();

        $input->setTagProperties([
            'class' => 'form-control pull-right typeahead input-sm',
            'style' => 'width: 300px; margin-right: 35px, border: 1px solid black; background-color: #e5e5e5; height: 27px',
            'placeholder' => _('Search for'),
        ]);
    }

    public function addJs($searcher = '')
    {
        \AdminZone\ui\WebPage::singleton()->includeJavaScript('js/handlebars.js');
        \AdminZone\ui\WebPage::singleton()->includeJavaScript('js/typeahead.bundle.js');

        /*
          \AdminZone\ui\WebPage::singleton()->addCss('

          .tt-hint {
          }

          .tt-input {
          }

          .tt-hint {
          color: #999
          }

          .tt-dropdown-menu {
          width: 422px;
          margin-top: 12px;
          padding: 8px 0;
          background-color: #fff;
          border: 1px solid #ccc;
          border: 1px solid rgba(0, 0, 0, 0.2);
          border-radius: 8px;
          box-shadow: 0 5px 10px rgba(0,0,0,.2);
          overflow-y: auto;
          max-height: 500px;
          }

          .tt-suggestion {
          padding: 3px 20px;
          }

          .tt-suggestion.tt-cursor {
          color: #fff;
          background-color: #0097cf;

          }

          .tt-suggestion.tt-cursor a {
          color: black;
          }

          .tt-suggestion p {
          margin: 0;
          }
          ');

         */
        \AdminZone\ui\WebPage::singleton()->addJavaScript('


var bestPictures = new Bloodhound({
    limit: 1000,
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace(\'value\'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: \'' . $searcher . '?q=%QUERY' . (empty($searchClass) ? '' : '&class=' . $searchClass) . '\',
      wildcard: \'%QUERY\'
    }
});

bestPictures.initialize();

$(\'input[name="search"]\').typeahead(null, {
    name: \'best-pictures\',
    displayKey: \'name\',
    limit: 1000,
    minLength: 3,
    highlight: true,
    source: bestPictures.ttAdapter(),
     templates: {
        suggestion: Handlebars.compile(\'<p><small>{{type}}</small><br><a href="{{url}}"><strong>{{name}}</strong> – {{what}}</a></p>\')
}
});

            ', null, true);
    }
}
