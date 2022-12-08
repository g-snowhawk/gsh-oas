<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas;

use DateTime;

/**
 * Category management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class AcceptedDocs extends \Gsnowhawk\Oas
{
    /**
     * Using common accessor methods
     */
    use \Gsnowhawk\Accessor;

    public const DEFAULT_MODE = 'oas.accepted-docs.response';
    public const QUERY_STRING_KEY = 'accepteddocs_search_condition';
    public const SEARCH_OPTIONS_KEY = 'accepteddocs_search_options';
    public const PAGE_KEY = 'accepteddocs_page';

    protected $user_root;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        call_user_func_array('parent::__construct', func_get_args());

        $this->user_root = $this->privateSavePath();
        $conf_file = $this->user_root . '/oas_config.json';
        if (file_exists($conf_file)) {
            $config = json_decode(file_get_contents($conf_file));
            if ($config->private_save_path) {
                $this->user_root = $this->privateSavePath($config->private_save_path);
            }
        }
    }

    protected function save(): bool
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

        if ($this->db->exists('accepted_document', 'userkey = ? AND checksum = ?', [$userkey, $checksum])) {
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

        $sequence = (int)$this->db->max('sequence', 'accepted_document', 'userkey = ?', [$userkey]) + 1;

        $data = [
            'sequence' => $sequence,
            'userkey' => $userkey,
            'checksum' => $checksum,
            'sender' => $this->request->param('sender'),
            'price' => $this->request->param('price'),
            'receipt_date' => $receipt_date->format('Y-m-d'),
            'category' => $this->request->param('category'),
        ];
        if (!empty($this->request->param('tax_a'))) {
            $data['tax_a'] = $this->request->param('tax_a');
        }
        if (!empty($this->request->param('tax_b'))) {
            $data['tax_b'] = $this->request->param('tax_b');
        }

        $this->db->begin();

        // Save the meta data
        if (false === $this->db->insert('accepted_document', $data)) {
            trigger_error($this->db->error());

            return false;
        }

        $id = $this->db->lastInsertId();

        // Save the history
        $data = [
            'document_id' => $id,
            'type' => 'CREATE',
        ];
        if (false === $this->db->insert('accepted_history', $data)) {
            trigger_error($this->db->error());

            return false;
        }

        // Save the uploaded file
        $filepath = $this->getPdfPath(
            $receipt_date->format('Y'),
            'accepted_documents',
            "{$sequence}.pdf"
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

        return $this->db->commit();
    }

    protected function getSearchCondition($key = 'q'): ?string
    {
        $query_string = (!$this->request->isset($key))
            ? $this->session->param(self::QUERY_STRING_KEY)
            : $this->request->param($key);

        if (empty($query_string)) {
            $this->session->clear(self::QUERY_STRING_KEY);
            if (is_null($query_string)) {
                return null;
            }
        }

        return mb_convert_kana($query_string, 's');
    }
}
