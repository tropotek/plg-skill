<?php
namespace Skill\Ui\Pdf;

use Dom\Renderer\Renderer;
use Dom\Template;
use Tk\ConfigTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 *
 * @note This file uses the mpdf lib
 * @link https://mpdf.github.io/
 */
class Entry extends \Dom\Renderer\Renderer implements \Dom\Renderer\DisplayInterface
{
    use ConfigTrait;

    /**
     * @var \Skill\Db\Entry
     */
    protected $entry = null;

    /**
     * @var \Mpdf\Mpdf
     */
    protected $mpdf = null;

    /**
     * @var string
     */
    protected $watermark = '';

    /**
     * @var bool
     */
    private $rendered = false;


    /**
     * HtmlInvoice constructor.
     * @param \Skill\Db\Entry $entry
     * @param string $watermark
     * @throws \Exception
     */
    public function __construct($entry, $watermark = '')
    {
        $this->entry = $entry;
        $this->watermark = $watermark;

        $this->initPdf();
    }

    /**
     * @param \Skill\Db\Entry $entry
     * @param string $watermark
     * @return Entry
     * @throws \Exception
     */
    public static function create($entry, $watermark = '')
    {
        $obj = new self($entry, $watermark);
        return $obj;
    }

    /**
     * @return \Skill\Db\Entry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @throws \Exception
     */
    protected function initPdf()
    {
        $html = $this->show()->toString();

        $tpl = \Tk\CurlyTemplate::create($html);
        $parsedHtml = $tpl->parse(array());
        $this->mpdf = new \Mpdf\Mpdf(array(
			'format' => 'A4-P',
            'orientation' => 'P',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 15,
            'margin_header' => 0,
            'margin_footer' => 3,
            'use_kwt' => 1,
            'tempDir' => $this->getConfig()->getTempPath()
        ));
        $mpdf = $this->mpdf;
        //$mpdf->setBasePath($url);
        $mpdf->use_kwt = true;
        //$mpdf->shrink_tables_to_fit = 0;
        //$mpdf->useSubstitutions = true;       // optional - just as an example
        //$mpdf->CSSselectMedia='mpdf';         // assuming you used this in the document header
        //$mpdf->SetProtection(array('print'));

        $mpdf->SetTitle($this->getEntry()->title);
        $mpdf->SetAuthor($this->getEntry()->assessor);

        if ($this->watermark) {
            $mpdf->SetWatermarkText($this->watermark);
            $mpdf->showWatermarkText = true;
            $mpdf->watermark_font = 'DejaVuSansCondensed';
            $mpdf->watermarkTextAlpha = 0.1;
        }
        $mpdf->SetDisplayMode('fullpage');


        $mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align: bottom; font-family: serif; font-size: 8pt; 
      color: #000000; font-weight: bold; font-style: italic;border-top: 1px solid #000;" cellpadding="10">
  <tr>
    <td width="33%">{DATE j-m-Y}</td>
    <td width="33%" align="center">{PAGENO}/{nbpg}</td>
    <td width="33%" style="text-align: right;">'.$this->getEntry()->getCollection()->name.'</td>
  </tr>
</table>');

        $mpdf->WriteHTML($parsedHtml);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFilename()
    {
        return $this->getEntry()->getCollection()->name . '-' . $this->getEntry()->getId() . '.pdf';
    }

    /**
     * Output the pdf to the browser
     *
     * @throws \Exception
     */
    public function download()
    {
        $this->mpdf->Output($this->getFilename(), \Mpdf\Output\Destination::DOWNLOAD);
    }

    /**
     * Output the pdf to the browser
     *
     * @throws \Exception
     */
    public function output()
    {
        $filename = $this->getEntry()->getCollection()->name . '-' . $this->getEntry()->getId() . '.pdf';
        $this->mpdf->Output($this->getFilename(), \Mpdf\Output\Destination::INLINE);
    }

    /**
     * Retun the PDF as a string to attache to an email message
     *
     * @param string $filename
     * @return string
     * @throws \Exception
     */
    public function getPdfAttachment($filename = '')
    {
        if (!$filename)
            $filename = $this->getFilename();
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Execute the renderer.
     * Return an object that your framework can interpret and display.
     *
     * @return null|Template|Renderer
     * @throws \Exception
     */
    public function show()
    {
        $template = $this->getTemplate();
        if ($this->rendered) return $template;
        $this->rendered = true;

        $css = <<<CSS
.doc {
/*  padding-top: 5px; */
}
h1 {
  text-align: center;
}
.head table { 
  margin: 0 auto;
  width: 80%;
  background: #EFEFEF;
}
.head table td, .head table th {
  padding: 5px;
}

.content {
  padding: 10px 20px;
}
.items {
  margin: 0px auto;
  width: 100%;
}
.items .t-id {
  width: 5%;
  text-align: center;
}
.items .t-data {
  width: 15%;
  text-align: center;
}
.items td {
  padding: 10px 5px;
}
.items tr:nth-child(odd) .td,
.items tr.odd td {
  background-color: {$this->getEntry()->getCollection()->color};
}
.category h3 {
  margin-top: 20px;
}
.tag {
  font-size: 0.7em;
}

CSS;
        $template->appendCss($css);

        $template->insertText('heading', $this->getEntry()->getCollection()->name . ' View');

        $this->addTableRow('head-row', 'Title', $this->getEntry()->title);
        $this->addTableRow('head-row', 'Status', ucwords($this->getEntry()->status));
        $this->addTableRow('head-row', 'Assessor', $this->getEntry()->assessor);
        $this->addTableRow('head-row', 'Days Absent', (int)$this->getEntry()->absent.'');
        $this->addTableRow('head-row', 'Comments', $this->getEntry()->notes);

        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->getEntry()->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));

        /** @var \Skill\Db\Item $item */
        $catId = 0;
        /** @var null|\Dom\Repeat $catRepeat */
        $catRepeat = null;
        $scaleList = \Skill\Db\ScaleMap::create()->findFiltered(array('collectionId' => $this->getEntry()->collectionId))->toArray('name');
        foreach ($items as $i => $item) {
            if ($item->categoryId != $catId) {
                if ($catRepeat) $catRepeat->appendRepeat();
                $catRepeat = $template->getRepeat('category');
                $catRepeat->insertText('category-name', $item->getCategory()->getLabel());
                $catId = $item->categoryId;
            }
            $value = 0;
            $itemVal = \Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId());
            if ($itemVal)
                $value = $itemVal->value;

            $repeat = $catRepeat->getRepeat('item');
            if ($i%2) {
                $repeat->addCss('item', 'odd');
            }
            $repeat->insertHtml('id', $i+1);
            $repeat->insertHtml('label', $item->question);
            $repeat->insertHtml('data', $value . '/' . ($item->getCollection()->getScaleCount()));
            $repeat->insertHtml('tag', $scaleList[$value]);
            $repeat->appendRepeat();
        }
        if ($catRepeat) $catRepeat->appendRepeat();

        return $template;
    }

    /**
     * @param string $var
     * @param string $label
     * @param string $data
     * @param null|\Dom\Template $template
     */
    public function addTableRow($var, $label, $data, $template = null)
    {
        if (!$template) $template = $this->getTemplate();
        $repeat = $template->getRepeat($var);
        $repeat->insertHtml('label', $label);
        $repeat->insertHtml('data', $data);
        $repeat->appendRepeat();
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title></title>
</head>
<body class="" style="" var="body">

<div class="doc">
  <h1 var="heading"></h1>
  
  <div class="head" var="head">
    <table class="items">
      <tr var="head-row" repeat="head-row">
        <th var="label"></th>
        <td var="data"></td>
      </tr>
    </table>
  </div>
  <br/>
  <div class="content" var="content">
    <div class="category" var="category" repeat="category">
    
      <h3 var="category-name"></h3>
      <table class="items" cellspacing="0">
        <tbody>
          <tr var="item" repeat="item">
            <td class="t-id" var="id"></td>
            <td class="t-label" var="label"></td>
            <td class="t-data"><div var="data"></div><div class="tag" var="tag"></div></td>
          </tr>
        </tbody>
      </table>
      
    </div>
    
  </div>
  
  <div class="foot" var="foot"></div>
</div>

</body>
</html>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}