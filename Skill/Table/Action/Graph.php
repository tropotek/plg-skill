<?php
namespace Skill\Table\Action;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Graph extends \Tk\Table\Action\Link
{
    /** @var null|\Tk\Ui\DialogBox */
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


        $this->dialog = \Tk\Ui\DialogBox::create('skill-avg-graph', 'Average Report Graph');
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

        if ($this->dialog) {
            $this->dialog->setOnShow(function ($dialog) {
                /** @var $dialog \Tk\Ui\DialogBox */
                $template = $dialog->getTemplate();

                $css = <<<CSS
.flot-graph-container {
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
    
    
    
    var d1 = [];
    for (var i = 0; i <= 80; i += 1) {
        d1.push([i, parseInt(Math.random() * 5)]);
    }
    var data = [ {data: d1, label: 'Questions' } ];
     
    //flot options
    var options = {
      series: {
        bars: {
          show: true,
          align: 'center',
          barWidth: 0.6
        }
      },
      grid: {
        hoverable: true,
        clickable:true
      },
  	  legend: {
  	    show: false
      },
      xaxis: {
        mode: 'categories',
        tickLength: 0,
        showTicks: true,
        gridLines: false
      }
    };
    
    
    // var data = [{ data: [ ["January", 10], ["February", 8], ["March", 4], ["April", 13], ["May", 17], ["June", 9]], label: 'Categories' }];
    // var options = {
    //   series: {
    //     bars: {
    //       show: true,
    //       barWidth: 0.6,
    //       align: 'center'
    //     }
    //   },
    //   grid: {
    //     hoverable: true,
    //     clickable: true
    //   },
    //   xaxis: {
    //     mode: 'categories',
		// tickLength: 0,
    //     showTicks: false,
    //     gridLines: false
    //   }
    // };
    
    $.plot(graph, data, options);
    
    
    // graph.on("plothover", function (event, pos, item) {
    //   if (item) {
    //     alert("You clicked an Item!");
    //   }
    // });
    
    // graph.bind("plotclick", function (event, pos, item) {
    //   if (item) {
    //     alert("You clicked a point!");
    //   }
    // });
    
    
    
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
