<?php namespace Forms;

use Helpers\Lexicon;

include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once(MODX_BASE_PATH . 'assets/modules/Forms/lib/model.php');
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/Lexicon.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/Config.php');


class Module
{
    protected $modx;
    protected $params = [];
    protected $DLTemplate;
    protected $lexicon;

    public function __construct (\DocumentParser $modx, $debug = false)
    {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $this->lexicon = new Lexicon($modx);
        $this->lexicon->fromFile('module', '', 'assets/modules/Forms/lang/');
        $ld = new Model($modx);
        $ld->createTable();
    }

    /**
     * @return bool|string
     */
    public function render ()
    {
        $this->DLTemplate->setTemplatePath('assets/modules/Forms/tpl/');
        $this->DLTemplate->setTemplateExtension('tpl');
        $ph = array(
            'lang'        => $this->modx->config['lang_code'],
            'lexicon'     => '<script>var lang=' . json_encode($this->lexicon->getLexicon()). ';</script>',
            'connector'   => $this->modx->config['site_url'] . 'assets/modules/Forms/ajax.php',
            'theme'       => $this->modx->config['manager_theme'],
            'site_url'    => $this->modx->config['site_url'],
            'manager_url' => MODX_MANAGER_URL
        );
        $output = $this->DLTemplate->parseChunk('@FILE:module', $ph);
        $output = $this->lexicon->parse($output);

        return $output;
    }
}
