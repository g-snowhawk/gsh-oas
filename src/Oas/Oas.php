<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk;

use DateTime;
use Gsnowhawk\Common\Lang;
use Gsnowhawk\Common\Text;
use Gsnowhawk\Base;
use Gsnowhawk\View;

/**
 * Site management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Oas extends User implements PackageInterface
{
    /*
     * Using common accessor methods
     */
    use Accessor;

    /**
     * Application default mode.
     */
    public const DEFAULT_MODE = 'oas.transfer.response';

    protected $command_convert = null;

    protected $tax_rate = null;
    protected $reduced_tax_rate = null;

    protected $pdf;
    protected $pdf_meta;

    /**
     * fonts for TCPDF
     */
    protected $mincho = 'ipamp';
    protected $gothic = 'ipagp';
    protected $mono = 'ocrb';

    protected $oas_config;

    //private $private_save_path = null;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        $this->tax_rate = (float)$this->app->cnf('srm:tax_rate');
        $this->reduced_tax_rate = (float)$this->app->cnf('srm:reduced_tax_rate');

        if (class_exists('Imagick')) {
            $this->command_convert = 'imagick';
        } else {
            $convert = $this->app->cnf('external_command:convert');
            if (!empty($convert)) {
                $disable_functions = Text::explode(',', ini_get('disable_functions'));
                if (!in_array('exec', $disable_functions)) {
                    exec('convert --version', $output, $status);
                    if ($status === 0) {
                        $this->command_convert = $convert;
                    }
                }
            }
        }

        $conf_file = $this->privateSavePath() . '/oas_config.json';
        if (file_exists($conf_file)) {
            $this->oas_config = json_decode(file_get_contents($conf_file));
            if (isset($this->oas_config->pdf_meta)) {
                $this->pdf_meta = $this->oas_config->pdf_meta;
            }
        }
    }

    /**
     * Default Mode
     *
     * @final
     * @param Gsnowhawk\App $app
     *
     * @return string
     */
    final public static function getDefaultMode($app)
    {
        $mode = $app->cnf('application:default_mode');

        return (!empty($mode)) ? $mode : self::DEFAULT_MODE;
    }

    /**
     * This package name
     *
     * @final
     *
     * @return string
     */
    final public static function packageName()
    {
        return strtolower(stripslashes(str_replace(__NAMESPACE__, '', __CLASS__)));
    }

    /**
     * Application name
     *
     * @final
     *
     * @return string
     */
    final public static function applicationName()
    {
        return Lang::translate('APPLICATION_NAME');
    }

    /**
     * Application label
     *
     * @final
     *
     * @return string
     */
    final public static function applicationLabel()
    {
        return Lang::translate('APPLICATION_LABEL');
    }

    /**
     * This package version
     *
     * @final
     *
     * @return string
     */
    final public static function version()
    {
        return System::getVersion(__CLASS__);
    }

    /**
     * This package version
     *
     * @final
     *
     * @return string|null
     */
    final public static function templateDir()
    {
        return __DIR__.'/'.View::TEMPLATE_DIR_NAME;
    }

    /**
     * Unload action
     *
     * Clear session data for package,
     * when unload application
     */
    public static function unload()
    {
        // NoP
    }

    /**
     * Clear application level permissions.
     *
     * @see Gsnowhawk\User::updatePermission()
     *
     * @param Gsnowhawk\Db $db
     * @param int    $userkey
     *
     * @return bool
     */
    public static function clearApplicationPermission(Db $db, $userkey)
    {
        $filter1 = [0];
        $filter2 = [0];
        $statement = 'userkey = ? AND application = ?'
            .' AND filter1 IN ('.implode(',', array_fill(0, count($filter1), '?')).')'
            .' AND filter2 IN ('.implode(',', array_fill(0, count($filter2), '?')).')';
        $options = array_merge([$userkey, self::packageName()], $filter1, $filter2);

        return $db->delete('permission', $statement, $options);
    }

    /**
     * Reference permission.
     *
     * @todo Better handling for inheritance
     *
     * @see Gsnowhawk\User::hasPermission()
     *
     * @param string $key
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    public function hasPermission($key, $filter1 = 0, $filter2 = 0)
    {
        return parent::hasPermission($key, $filter1, $filter2);
    }

    public function init()
    {
        parent::init();
        $config = $this->view->param('config');
        $this->view->bind('config', $config);
    }

    public function availableConvert()
    {
        return !empty($this->command_convert);
    }

    protected function pathToID($path)
    {
        return trim(str_replace(['/','.'], ['-','_'], preg_replace('/\.html?$/', '', $path)), '-_');
    }

    public function receipts()
    {
        $receipts = $this->db->select(
            'id ,title',
            'receipt_template',
            'WHERE userkey = ?',
            [$this->uid]
        );

        foreach ($receipts as &$receipt) {
            $receipt['active'] = ($receipt['id'] === $this->session->param('receipt_id')) ? 1 : 0;
        }
        unset($receipt);

        return $receipts;
    }

    public function callFromTemplate()
    {
        $params = func_get_args();
        $function = array_shift($params);
        $function = Base::lowerCamelCase($function);

        if (method_exists($this, $function)) {
            return call_user_func_array([$this, $function], $params);
        }

        return;
    }

    private function bankList()
    {
        $banks = $this->db->select(
            'account_number,bank,branch,account_type',
            'bank',
            'WHERE userkey = ?',
            [$this->uid]
        );

        $bank_list = [];
        foreach ($banks as $bank) {
            $bank_list[] = [
                'label' => $bank['bank'].' '.$bank['branch'],
                'value' => $bank['account_number'],
            ];
        }

        return $bank_list;
    }

    /**
     * PDF Path
     *
     * @param  number   $year
     * @param  string   $category
     * @param  string   $fileName
     * @return string
     */
    protected function getPdfPath($year, $category, $fileName)
    {
        $format = (!empty($this->oas_config->pdf_save_format))
            ? $this->oas_config->pdf_save_format : null;
        if (empty($format)) {
            $format = $this->privateSavePath() . '/%s/%s/%s';
        }

        return sprintf($format, $year, $category, $fileName);
    }

    public function outputPdf($file, $savePath = null, $display = false, $locked = false)
    {
        // Set Author
        if (!empty($this->pdf_meta)) {
            $this->pdf->setMetaData((array)$this->pdf_meta);
        }

        // Security
        $encrypt_to = ['modify','copy','annot-forms','fill-forms','extract','assemble','print-high'];
        $this->pdf->encrypt($encrypt_to, '', '', 1);

        if (empty($savePath)) {
            $this->pdf->Output($file, 'I');
        } else {
            // Create Directory
            if (!file_exists($savePath) && false === mkdir($savePath, 0777, true)) {
                return false;
            }

            $pdf = "{$savePath}/{$file}";
            // Save File
            if (!file_exists($pdf) || is_writable($pdf)) {
                $this->pdf->Output($pdf, 'F');
            }
            if (false !== $locked) {
                chmod($pdf, 0440);
            }
            if ($display !== true) {
                return file_exists($pdf);
            }
            header('Content-Type: application/pdf');
            readfile($pdf);
        }
        exit;
    }

    public static function toWareki($date, $with_gengo = false)
    {
        $timestamp = (is_string($date)) ? strtotime($date) : $date;
        $year = date('Y', $timestamp);
        $end = date('Ymd', $timestamp);
        $gengo = '';
        $offset = 0;
        if ($end > 20190430) {
            $gengo = 'R.';
            $offset = 2018;
        } elseif ($end > 19890107) {
            $gengo = 'H.';
            $offset = 1988;
        } elseif ($end > 19261225) {
            $gengo = 'S.';
            $offset = 1925;
        }
        if (!$with_gengo) {
            $gengo = '';
        }

        return $gengo . ($year - $offset);
    }

    protected function saveAcceptedDocument(string $ifexists = null): int|false
    {
        $userkey = $this->uid;

        $valid = [];
        $valid[] = ['vl_file', 'file', 'upload'];
        $valid[] = ['vl_price', 'price', 'int'];
        $valid[] = ['vl_sender', 'sender', 'empty'];
        $valid[] = ['vl_category', 'category', 'empty'];
        $valid[] = ['vl_receipt_date', 'receipt_date', 'empty'];

        if (!empty($this->request->param('tax_a'))) {
            $valid[] = ['vl_tax_a', 'tax_a', 'int'];
        }
        if (!empty($this->request->param('tax_b'))) {
            $valid[] = ['vl_tax_b', 'tax_b', 'int'];
        }

        if (!$this->validate($valid)) {
            return false;
        }

        $file = $this->request->FILES('file');
        $checksum = hash_file('sha256', $file['tmp_name']);
        $mimetype = mime_content_type($file['tmp_name']);
        $extension = ($mimetype === 'message/rfc822') ? '.eml' : '.pdf';

        $statement = 'userkey = ? AND checksum = ?';
        $options = [$userkey, $checksum];
        if ($this->db->exists('accepted_document', $statement, $options)) {
            if ($ifexists === 'id') {
                return $this->db->get($ifexists, 'accepted_document', $statement, $options);
            }
            $this->app->err['vl_file'] = 128;
        }

        $today = new DateTime();
        $receipt_date = new DateTime($this->request->param('receipt_date'));
        if ($today < $receipt_date) {
            $this->app->err['vl_receipt_date'] = 2;
        }

        if (array_sum($this->app->err) > 0) {
            return false;
        }

        $sequence = (int)$this->db->max(
            'sequence',
            'accepted_document',
            'userkey = ? AND year = ?',
            [$userkey, $receipt_date->format('Y')]
        ) + 1;

        $data = [
            'sequence' => $sequence,
            'userkey' => $userkey,
            'checksum' => $checksum,
            'mimetype' => $mimetype,
            'sender' => $this->request->param('sender'),
            'category' => $this->request->param('category'),
            'source' => $this->request->param('source'),
            'receipt_date' => $receipt_date->format('Y-m-d'),
            'year' => $receipt_date->format('Y'),
            'price' => $this->request->param('price'),
        ];
        if (!empty($this->request->param('tax_a'))) {
            $data['tax_a'] = $this->request->param('tax_a');
        }
        if (!empty($this->request->param('tax_b'))) {
            $data['tax_b'] = $this->request->param('tax_b');
        }

        if ($this->db->getTransaction()) {
            $savepoint = 'accepted_document';
            if (false === $this->db->query("SAVEPOINT {$savepoint}")) {
                return false;
            }
        } else {
            $this->db->begin();
        }

        // Save the meta data
        if (false === $this->db->insert('accepted_document', $data)) {
            trigger_error($this->db->error());
            if (isset($savepoint)) {
                $this->db->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
            }

            return false;
        }

        $document_id = $this->db->lastInsertId();

        // Save the history
        $data = [
            'document_id' => $document_id,
            'type' => 'CREATE',
            'reason' => Lang::translate('CREATE_DOCUMENT'),
        ];
        if (false === $this->db->insert('accepted_history', $data)) {
            trigger_error($this->db->error());
            if (isset($savepoint)) {
                $this->db->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
            }

            return false;
        }

        // Update relation
        $rel = $this->request->param('rel');
        if (!empty($rel)) {
            $documents = [];
            $statement = "userkey = ? AND CONCAT(category, DATE_FORMAT(issue_date, '%Y%m%d'), '.', page_number) = ? AND line_number = ?";
            $options = [$userkey, $rel, 1];

            $note = $this->db->get('note', 'transfer', $statement, $options);
            if (empty($note)) {
                $note = '';
            } elseif (preg_match('/a(\{.+?\})/', $note, $matchs)) {
                $tmp = json_decode($matchs[1], true);
                $documents = $tmp['docid'];
                $note = preg_replace('/a(\{.+?\})/', '', $note);
            }

            $documents[] = $document_id;

            $json = 'a' . json_encode(['docid' => $documents]);
            if (false === $this->db->update('transfer', ['note' => $json.$note], $statement, $options)) {
                trigger_error($this->db->error());
                if (isset($savepoint)) {
                    $this->db->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
                }

                return false;
            }
        }

        // Save the uploaded file
        $filepath = $this->getPdfPath(
            $receipt_date->format('Y'),
            'accepted_documents',
            "{$sequence}{$extension}"
        );

        $upload_dir = dirname($filepath);
        if (!file_exists($upload_dir) && false === @mkdir($upload_dir, 0700, true)) {
            trigger_error('Failed make a directory');

            return false;
        }

        if (false === move_uploaded_file($file['tmp_name'], $filepath)) {
            trigger_error('Does not save upload file');

            return false;
        }
        @chmod($filepath, 0444);

        if (isset($savepoint)) {
            if (false === $this->db->query("RELEASE SAVEPOINT {$savepoint}")) {
                return false;
            }
        } else {
            if (false === $this->db->commit()) {
                return false;
            }
        }

        return $document_id;
    }
}
