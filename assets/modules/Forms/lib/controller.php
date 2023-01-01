<?php namespace Forms;

use Helpers\Lexicon;

include_once(MODX_BASE_PATH . 'assets/modules/Forms/lib/model.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/Lexicon.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/Config.php');

/**
 * Class Controller
 */
class ModuleController
{
    protected $modx;
    protected $data;
    public $isExit = false;
    public $dlParams = [
        'controller'  => 'onetable',
        'table'       => 'forms',
        'idField'     => 'id',
        'api'         => 1,
        'idType'      => 'documents',
        'ignoreEmpty' => 1,
        'makeUrl'     => 0,
        'JSONformat'  => 'new',
        'display'     => 10,
        'offset'      => 0,
        'sortBy'      => 'id',
        'sortDir'     => 'desc',
    ];
    protected $exportChunkSize = 1000;
    protected $exportPath = 'assets/export/forms/';

    /**
     * Controller constructor.
     * @param \DocumentParser $modx
     */
    public function __construct (\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->data = new Model($modx);
        $this->dlInit();
    }

    public function remove ()
    {
        $out = array('success' => false);
        if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array();
            foreach ($_POST['ids'] as $id) {
                $id = (int)$id;
                if ($id) {
                    $ids[] = $id;
                }
            }
            $this->data->delete($ids);
            $out = array('success' => true);
        }

        return $out;
    }

    /**
     * @return string
     */
    public function listing ()
    {
        return $this->modx->runSnippet("DocLister", $this->dlParams);
    }

    public function getFormTypes ()
    {
        $q = $this->modx->db->query("SELECT DISTINCT `type` FROM {$this->modx->getFullTableName('forms')} ORDER BY `type` ASC");
        $out = $this->modx->db->makeArray($q);

        return $out;
    }

    /**
     *
     */
    public function dlInit ()
    {
        if (isset($_POST['rows'])) {
            $this->dlParams['display'] = (int)$_POST['rows'];
        }
        $offset = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $this->dlParams['display'] * abs($offset - 1);
        $this->dlParams['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $this->dlParams['sortBy'] = '`' . preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']) . '`';
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), array("ASC", "DESC"))) {
            $this->dlParams['sortDir'] = $_POST['order'];
        }
        $where = $this->getFilterWhere();
        if ($where) {
            $this->dlParams['addWhereList'] = $where;
        }
        foreach ($this->dlParams as &$param) {
            if (empty($param)) {
                unset($param);
            }
        }
    }

    /**
     * @return string
     */
    protected function getFilterWhere() {
        $where = '';
        $_where = [];
        if (!empty($_POST['type']) && is_scalar($_POST['type'])) {
            $_where[] = "`type`='{$this->modx->db->escape($_POST['type'])}'";
        }
        if (!empty($_POST['begin']) && is_scalar($_POST['begin'])) {
            $date = $this->modx->db->escape(date('Y-m-d', strtotime($_POST['begin'])));
            $_where[] = "`createdon` >= '{$date} 00:00:00'";
        }
        if (!empty($_POST['end']) && is_scalar($_POST['end'])) {
            $date = $this->modx->db->escape(date('Y-m-d', strtotime($_POST['end'])));
            $_where[] = "`createdon` <= '{$date} 23:59:59'";
        }
        if ($_where) {
            $where = implode(' AND ', $_where);
        }

        return $where;
    }

    /**
     * @return array|string
     */
    public function view() {
        $out = ['success' => false];
        if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
            $id = (int)$_POST['id'];
            $this->data->edit($id);
            if ($this->data->getID()) {
                $formdata = array_filter($this->data->toArray());
                $formdata['createdon'] = date('d.m.Y H:i:s', strtotime($formdata['createdon']));
                unset($formdata['id'], $formdata['type']);
                $out = ['success' => true, 'formdata' => $formdata];
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function startExport() {
        $out = ['success' => false];
        $_SESSION['forms']['export'] = [
            'lastId' => 0,
            'minId' => 0,
            'maxId' => 0,
            'exported' => 0,
            'fields' => [],
            'where' => ''
        ];
        $filename = 'export' . time();
        if (!empty($_POST['begin']) && is_scalar($_POST['begin'])) {
            $date = $this->modx->db->escape(date('Y-m-d', strtotime($_POST['begin'])));
            $filename .= '-' . $date;
        }
        if (!empty($_POST['end']) && is_scalar($_POST['end'])) {
            $date = $this->modx->db->escape(date('Y-m-d', strtotime($_POST['end'])));
            $filename .= '-' . $date;
        }
        $filename .= '.csv';
        $_SESSION['forms']['export']['file'] = $filename;
        \Helpers\FS::getInstance()->rmDir(MODX_BASE_PATH . $this->exportPath);
        if (\Helpers\FS::getInstance()->makeDir(MODX_BASE_PATH . $this->exportPath) && ($file = fopen(MODX_BASE_PATH . $this->exportPath . $filename, 'w+'))) {
            $q = "SELECT MIN(`id`) AS `minId`, MAX(`id`) AS `maxId` FROM {$this->modx->getFullTableName('forms')}";
            $where = $this->getFilterWhere();
            if ($where) {
                $_SESSION['forms']['export']['where'] = $where;
                $q .= ' WHERE ' . $where;
            }
            $q = $this->modx->db->query($q);
            $minId = $maxId = 0;
            if ($row = $this->modx->db->getRow($q)) {
                $minId = $_SESSION['forms']['export']['minId'] = (int)$row['minId'];
                $maxId = $_SESSION['forms']['export']['maxId'] = (int)$row['maxId'];
            }
            $q = "SELECT DISTINCT `field` FROM {$this->modx->getFullTableName('forms_fields')} WHERE `form` >= {$minId} AND `form` <= {$maxId}";
            $q = $this->modx->db->query($q);
            $fields = $this->modx->db->getColumn('field', $q);
            $_SESSION['forms']['export']['fields'] = $fields;
            $lexicon = new Lexicon($this->modx);
            $lexicon->fromFile('module', '', 'assets/modules/Forms/lang/');
            $header = [
                'id',
                $lexicon->get('type'),
                $lexicon->get('date'),
                'IP',
                $lexicon->get('sender'),
                'E-mail',
                $lexicon->get('phone')
            ];
            foreach ($fields as $field) {
                $header[] = $field;
            };
            foreach ($header as &$title) {
                $title = iconv('UTF-8', 'cp1251', $title);
            }
            fputcsv($file, $header, ';');
            fclose($file);
            $out = ['success' => true];
        }

        return $out;
    }

    public function processExport() {
        $filename = $_SESSION['forms']['export']['file'];
        $exported = &$_SESSION['forms']['export']['exported'];
        $out = ['success' => false];
        if ($file = fopen(MODX_BASE_PATH . $this->exportPath . $filename, 'a')) {
            $data = $this->getData();
            foreach ($data as $row) {
                foreach ($row as &$value) {
                    $value = iconv('UTF-8', 'cp1251', $value);
                }
                fputcsv($file, $row, ';');
                $exported++;
            }
            $out = ['success' => true, 'exported' => $exported, 'finished' => false];
            if (count($data) < $this->exportChunkSize) {
                $out['finished'] = true;
                $out['filename'] = MODX_SITE_URL . $this->exportPath . $filename;
                unset($_SESSION['forms']);
            }
            fclose($file);
        }

        return $out;
    }

    protected function getData() {
        $data = [];
        $minId = $_SESSION['forms']['export']['minId'];
        $maxId = $_SESSION['forms']['export']['maxId'];
        $lastId = &$_SESSION['forms']['export']['lastId'];
        $q = "SELECT `id`, `type`, `createdon`, `IP`, `name`, `email`, `phone` FROM {$this->modx->getFullTableName('forms')} WHERE `id` > {$lastId} AND `id` >= {$minId} AND `id` <= {$maxId}";
        $where = $_SESSION['forms']['export']['where'];
        if ($where) {
            $q .= ' AND ' . $where;
        }
        $q .= ' LIMIT ' . $this->exportChunkSize;
        $q = $this->modx->db->query($q);
        $ids = [];
        while ($row = $this->modx->db->getRow($q)) {
            $data[$row['id']] = $row;
            $ids[] = $row['id'];
            $lastId = $row['id'];
        }
        $fields = $_SESSION['forms']['export']['fields'];
        if ($ids && $fields) {
            $_fields = [];
            $ids = implode(',', $ids);
            $q = $this->modx->db->query("SELECT * FROM {$this->modx->getFullTableName('forms_fields')} WHERE `form` IN ({$ids})");
            while ($row = $this->modx->db->getRow($q)) {
                $_fields[$row['form']][$row['field']] = $row['value'];
            }
            foreach ($_fields as $id => $row) {
                foreach($fields as $field) {
                    if (isset($row[$field])) {
                        $data[$id][] = $row[$field];
                    } else {
                        $data[$id][] = '';
                    }
                }
            }
        }

        return $data;
    }
}
