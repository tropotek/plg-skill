<?php
namespace Skill\Table\Action;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Graph extends \Tk\Table\Action\Link
{
    /** @var null|\Tk\Ui\Dialog\Dialog */
    protected $dialog = null;


    /**
     * @param string $name
     * @param string|\Tk\Uri|null $url
     * @param string|null $icon
     */
    public function __construct($name, $url = null, $icon = null)
    {
        $this->setIcon('fa fa-line-chart');
        parent::__construct($name);


        $this->dialog = \Tk\Ui\Dialog\Dialog::create('Average Report Graph');
        $this->dialog->setLarge(true);

        $this->setAttr('data-toggle', 'modal');
        $this->setAttr('data-target', '#'.$this->dialog->getId());
        $this->setUrl('#');

    }

    /**
     * @param string $name
     * @param string $icon
     * @param string|\Tk\Uri|null $url
     * @return void
     * @throws \Tk\Exception
     * @deprecated use createLink($name, $url, $icon)
     */
    static function create($name, $icon, $url = null)
    {
        throw new \Tk\Exception('Cannot use this here.');
    }

    /**
     * @param string $name
     * @param string|\Tk\Uri|null $url
     * @param string|null $icon
     * @return static
     * @since 2.0.68
     */
    static function createGraph($name, $url = null, $icon = null)
    {
        return new static($name, $url, $icon);
    }


    /**
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $this->dialog->setAttr('data-table-target', '#'.$this->getTable()->getId());

        if ($this->dialog) {
            $this->dialog->addOnShow(function ($dialog) {
                /** @var $dialog \Tk\Ui\DialogBox */
                $template = $dialog->getTemplate();

                $css = <<<CSS
.flot-graph-container {
    position: relative;
    padding: 20px 45px 15px 15px;
    /* margin: 15px auto 30px auto;*/ 
    border: 1px solid #ddd;
    background: #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
.flot-graph {
    height: 420px;
}
.flot-x-axis .flot-tick-label {
    white-space: nowrap;
    transform: translate(-9px, 0) rotate(-60deg);
    text-indent: -100%;
    transform-origin: top right;
    text-align: right !important;
    
}
CSS;
                $template->appendCss($css);


                // include Flot
                \App\Ui\Js::includeFlot($template);
                $js = <<<JS
jQuery(function ($) {
  var dialog = $('#{$this->dialog->getId()}');


  dialog.on('shown.bs.modal', function (e) {

    dialog.find('.modal-body').empty().append('<div class="flot-graph-container"><div class="flot-graph"></div></div>');
    var graph = dialog.find('.flot-graph');


    // TODO: Get this data from the rendered table.
    var d1 = [];
    var table = $(dialog.data('tableTarget'));
    var rowCount = table.find('tbody tr').length - 1;

    for (var i = 0; i <= rowCount; i++) {
      var tr = table.find('tbody tr').eq(i);
      var idx = tr.find('td.mNum').text();
      var average = tr.find('td.mAverage').text();
      var item = tr.find('td.mNum').text() + '. ' + tr.find('td.mItem_question').text();
      if (average === '') continue;
      d1.push([idx, average, item]);
    }

    var data = [{data: d1, label: 'Skill Items'}];

    //flot options
    var options = {
      series: {
        bars: {
          show: true,
          align: 'center',
          barWidth: 0.5
        }
      },
      grid: {
        hoverable: true,
        clickable: true
      },
      legend: {
        show: false
      },
      xaxis: {
        show: true,
        mode: 'categories',
        //tickLength: 5,
        showTicks: true,
        gridLines: false,
        axisLabel: 'Skill Items',
        rotateTicks: 120
      },
      yaxis: {
        min: 0,
        max: 5
      }
    };
    $.plot(graph, data, options);


    var previousPoint = null, previousLabel = null;
    $(this).bind("plothover", function (event, pos, item) {
      if (item) {
        if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
          previousPoint = item.dataIndex;
          previousLabel = item.series.label;
          $("#tooltip").remove();
         
          var x = item.datapoint[0];
          var y = item.datapoint[1];
          var color = item.series.color;
          var tipText = item.series.data[item.dataIndex][2] + ' [Avg: '+item.series.data[item.dataIndex][1]+']';

          showTooltip(item.pageX, item.pageY, color, tipText);
        }
      } else {
        $("#tooltip").remove();
        previousPoint = null;
      }
    });

    function showTooltip(x, y, color, contents) {
      $('<div id="tooltip">' + contents + '</div>').css({
        position: 'absolute',
        display: 'none',
        top: y - 40,
        left: x - 120,
        border: '2px solid ' + color,
        padding: '3px',
        'font-size': '9px',
        'border-radius': '5px',
        'background-color': '#fff',
        'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
        opacity: 0.9,
        zIndex: 9999999
      }).appendTo("body").fadeIn(200);
    }


  });


});
JS;
                $template->appendJs($js);

            });
            $template->appendBodyTemplate($this->dialog->show());
        }


        return $template;
    }

    /**
     * @return \Dom\Template
     */
//    public function __makeTemplate()
//    {
//        $xhtml = <<<XHTML
//<a class="" href="javascript:;" var="btn"><i var="icon"></i> <span var="btnTitle"></span></a>
//XHTML;
//        return \Dom\Loader::load($xhtml);
//    }


}
