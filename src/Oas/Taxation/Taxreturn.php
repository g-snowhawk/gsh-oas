<?php
/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Taxation;

use Gsnowhawk\Common\Lang;
use Gsnowhawk\Common\Text;
use Gsnowhawk\Pdf;

/**
 * Category management response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Taxreturn extends \Gsnowhawk\Oas\Taxation
{
    public const LINE_STYLE = [
        'width' => 0.3,
        'cap' => 'round',
        'join' => 'round',
        'dash' => 0,
        'color' => [0, 66, 99]
    ];

    public const PDF_ELLIPSE_MAP = [
        'gender' => [
            1 => 103.1,
            2 => 108.0,
        ],
        'formtype' => [
            1 => 93.0,
        ],
        'phoneplace' => [
            1 => 161.5,
            2 => 170.0,
            3 => 179.0,
        ],
        'bank_type' => [
            1 => ['x' => 147.8, 'y' => 241.0],
            2 => ['x' => 147.8, 'y' => 243.4],
            3 => ['x' => 153.2, 'y' => 243.4],
            4 => ['x' => 147.8, 'y' => 245.6],
            5 => ['x' => 153.2, 'y' => 245.6],
        ],
        'branch_type' => [
            1 => ['x' => 186.8, 'y' => 241.0],
            2 => ['x' => 192.3, 'y' => 241.0],
            3 => ['x' => 187.8, 'y' => 243.4],
            4 => ['x' => 186.8, 'y' => 245.6],
            5 => ['x' => 192.3, 'y' => 245.6],
        ],
        'account_type' => [
            1 => 161.2,
            2 => 168.8,
            3 => 176.3,
            4 => 183.8,
            5 => 191.4,
        ],
    ];

    private $pension = 0;
    private $mutualaid = 0;
    private $lifeinsurance = 0;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        $this->view->bind(
            'header',
            [
                'title' => Lang::translate('HEADER_TITLE'),
                'id' => 'osa-taxation-taxreturn',
                'class' => 'taxation'
            ]
        );

        $paths = $this->view->getPaths();
        $private_templates = $this->privateSavePath() . '/templates';
        if (is_dir($private_templates)) {
            array_unshift($paths, $private_templates);
        }
        $this->pdf = new Pdf($paths);
    }

    /**
     * Default view.
     */
    public function defaultView(): void
    {
        $this->checkPermission('oas.taxation.read');

        $template_path = 'oas/taxation/tax_return.tpl';
        $html_id = 'oas-taxation-taxreturn';

        $form_params = $this->view->param('form');
        if (is_null($form_params)) {
            $form_params = [];
        }
        $form_params['target'] = 'GsnowhawkPDFWindow';
        $this->view->bind('form', $form_params);

        $this->setHtmlId($html_id);
        $this->view->render($template_path);
    }

    public function pdf(): void
    {
        $target_year = $this->request->POST('nendo') . '-01-01';
        $year = date('Y', strtotime($target_year));

        if ($year === date('Y')) {
            trigger_error('Today is still in the period.', E_USER_ERROR);
        }

        $this->pdf->loadTemplate('oas/taxation/tax_return_B.pdf');

        $mapfile = $this->privateSavePath() . '/templates/oas/taxation/tax_return_B.json';
        if (file_exists($mapfile)) {
            $this->pdfmap = json_decode(file_get_contents($mapfile), true);
            if (is_null($this->pdfmap)) {
                echo json_last_error_msg();
                exit;
            }
        }

        $this->page1($target_year);
        $this->page2($target_year);

        $year = date('Y', strtotime($target_year));
        $file = $this->getPdfPath($year, 'taxation', 'bluepaper.pdf');
        $locked = ($this->request->POST('locked') === '1') ? true : false;
        $this->outputPdf(basename($file), dirname($file), true, $locked);
    }

    private function page1($target_year)
    {
        $orientation = $this->pdfmap['orientation'] ?? 'P';
        $format = $this->pdfmap['format'] ?? '';
        $tplIdx = $this->pdf->addPageFromTemplate(1, $orientation, $format);

        $this->drawHeader($target_year);
        $this->drawBank();
        $this->drawDetail($target_year);
    }

    private function page2($target_year)
    {
        $orientation = $this->pdfmap['orientation'] ?? 'P';
        $format = $this->pdfmap['format'] ?? '';
        $tplIdx = $this->pdf->addPageFromTemplate(2, $orientation, $format);
        //
        $data = [];
        $data['address1'] = $this->userinfo['city'] . $this->userinfo['town'] . $this->userinfo['address1'];
        $data['company'] = $this->userinfo['company'];
        $data['name'] = $this->userinfo['fullname'];
        $data['rubi'] = $this->userinfo['fullname_rubi'];
        $data['nengo'] = $this->toWareki($target_year);

        $sql = 'SELECT MIN(colnumber) AS colnumber,
                       MIN(title) AS title,
                       SUM(amount) AS amount
                  FROM table::social_insurance
                 WHERE year = ? AND userkey = ?
                 GROUP BY colnumber';
        $this->db->query($sql, [$target_year, $this->uid]);
        $data = [];
        while ($unit = $this->db->fetch()) {
            $data[$unit['colnumber']] = number_format($unit['amount']);
            $data["{$unit['colnumber']}-label"] = $unit['title'];
        }

        $data['kokumin'] = Lang::translate('KOKUMINNENKIN');
        $data['shokibo'] = Lang::translate('SHOKIBOKYOSAI');
        $data['nenkin'] = number_format($this->pension);
        $data['syaho'] = number_format($this->pension);
        $data['kyosai'] = number_format($this->mutualaid);
        $data['kakekin'] = number_format($this->mutualaid);
        $data['seimei'] = number_format($this->lifeinsurance);

        $ary = $this->pdfmap['page2']['items'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'address1', 'suffix' => '', 'x' => 34, 'y' => 44, 'type' => 'Cell', 'width' => 68,   'height' => 8, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'company',  'suffix' => '', 'x' => 34, 'y' => 48, 'type' => 'Cell', 'width' => 68,   'height' => 8, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 7, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'rubi',     'suffix' => '', 'x' => 34, 'y' => 54, 'type' => 'Cell', 'width' => 66,   'height' => 6, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 11, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'name',     'suffix' => '', 'x' => 34, 'y' => 55.5, 'type' => 'Cell', 'width' => 66,   'height' => 10, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 11, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nengo',    'suffix' => '', 'x' => 28.0, 'y' => 13, 'type' => 'Cell', 'width' => 10,   'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 2.9],
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'kokumin',  'suffix' => '', 'x' => 117, 'y' => 26, 'type' => 'Cell', 'width' => 17.2, 'height' => 6.9, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nenkin',   'suffix' => '', 'x' => 134, 'y' => 26, 'type' => 'Cell', 'width' => 19.6, 'height' => 6.9, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'syaho',    'suffix' => '', 'x' => 134, 'y' => 47, 'type' => 'Cell', 'width' => 19.6, 'height' => 6.9, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'seimei',   'suffix' => '', 'x' => 134, 'y' => 54, 'type' => 'Cell', 'width' => 19.6, 'height' => 6.1, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'shokibo',  'suffix' => '', 'x' => 162.5, 'y' => 26, 'type' => 'Cell', 'width' => 17.2, 'height' => 6.9, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'kyosai',   'suffix' => '', 'x' => 180, 'y' => 26, 'type' => 'Cell', 'width' => 19.6, 'height' => 6.9, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'kakekin',  'suffix' => '', 'x' => 180, 'y' => 47, 'type' => 'Cell', 'width' => 19.6, 'height' => 6.9, 'align' => 'R', 'flg' => true],
        ];
        $this->pdf->draw($ary, $data);
    }

    public function drawHeader($target_year)
    {
        $data = [];
        $data['address1'] = $this->userinfo['city'] . $this->userinfo['town'] . $this->userinfo['address1'];
        $data['address2'] = $this->userinfo['address2'];
        $data['company'] = $this->userinfo['company'];
        $data['name'] = $this->userinfo['fullname'];
        $data['rubi'] = mb_convert_kana($this->userinfo['fullname_rubi'], 'k', 'utf-8');
        $tel = explode('-', Text::formatPhonenumber($this->userinfo['tel']));
        $data['tel1'] = $tel[0];
        $data['tel2'] = $tel[1];
        $data['tel3'] = $tel[2];
        if (preg_match('/^([0-9]{3})[ \-]?([0-9]{4})$/', $this->userinfo['zip'], $zip)) {
            $data['zip1'] = $zip[1];
            $data['zip2'] = $zip[2];
        }

        // fixed properties
        if (!is_null($this->oas_config)) {
            $data['caddress'] = $this->oas_config->caddress;
            $data['works'] = $this->oas_config->works;
            $data['nushi'] = $this->oas_config->head_of_household;
            $data['gara'] = $this->oas_config->relationship;
            $data['gengo'] = $this->oas_config->gengo;
            $data['bYear'] = $this->oas_config->birth_year;
            $data['bMonth'] = $this->oas_config->birth_month;
            $data['bDay'] = $this->oas_config->birth_day;
            $data['kankatu'] = $this->oas_config->jurisdiction;
            $data['kubun'] = $this->oas_config->declaration_type;
        }

        // today
        $data['year'] = $this->toWareki(date('Y-m-d'));
        $data['month'] = date('n');
        $data['day'] = date('j');
        $data['nengo'] = $this->toWareki($target_year);

        $lh = (empty($data['address2'])) ? 9.2 : 4.5;
        $ary = $this->pdfmap['page1']['header'] ?? [
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'zip1',     'suffix' => '', 'x' => 33.3, 'y' => 21, 'type' => 'Cell', 'width' => 14.3, 'height' => 5.5, 'align' => 'C', 'flg' => true, 'pitch' => 3.3],
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'zip2',     'suffix' => '', 'x' => 51.0, 'y' => 21, 'type' => 'Cell', 'width' => 19.2, 'height' => 5.5, 'align' => 'C', 'flg' => true, 'pitch' => 3.3],
            ['font' => $this->mincho, 'style' => '',  'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'address1', 'suffix' => '', 'x' => 30.5, 'y' => 29.0, 'type' => 'Cell', 'width' => 68.5, 'height' => 8.8, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'caddress', 'suffix' => '', 'x' => 30.5, 'y' => 49.5, 'type' => 'Cell', 'width' => 56, 'height' => 8, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'rubi',     'suffix' => '', 'x' => 111.5, 'y' => 26.5, 'type' => 'Cell', 'width' => 68, 'height' => 7, 'align' => 'L', 'flg' => true, 'pitch' => 3.42],
            ['font' => $this->mincho, 'style' => '',  'size' => 11, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'name',     'suffix' => '', 'x' => 113, 'y' => 34.5, 'type' => 'Cell', 'width' => 69, 'height' => 8.3, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'works',    'suffix' => '', 'x' => 111, 'y' => 45.0, 'type' => 'Cell', 'width' => 23, 'height' => 6.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'company',  'suffix' => '', 'x' => 135, 'y' => 45.0, 'type' => 'Cell', 'width' => 23, 'height' => 6.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nushi',    'suffix' => '', 'x' => 159, 'y' => 45.0, 'type' => 'Cell', 'width' => 21, 'height' => 6.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'gara',     'suffix' => '', 'x' => 180, 'y' => 45.0, 'type' => 'Cell', 'width' => 14.6, 'height' => 6.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'gengo',    'suffix' => '', 'x' => 108.5, 'y' => 50.2, 'type' => 'Cell', 'width' => 4.5, 'height' => 6.7, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'bYear',    'suffix' => '', 'x' => 116.0, 'y' => 50.2, 'type' => 'Cell', 'width' => 9.4, 'height' => 6.7, 'align' => 'R', 'flg' => true, 'pitch' => 3.3],
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'bMonth',   'suffix' => '', 'x' => 128.5, 'y' => 50.2, 'type' => 'Cell', 'width' => 9.4, 'height' => 6.7, 'align' => 'R', 'flg' => true, 'pitch' => 3.3],
            ['font' => $this->mono,   'style' => '',  'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'bDay',     'suffix' => '', 'x' => 141.0, 'y' => 50.2, 'type' => 'Cell', 'width' => 9.4, 'height' => 6.7, 'align' => 'R', 'flg' => true, 'pitch' => 3.3],
            ['font' => $this->gothic, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'tel1',     'suffix' => '', 'x' => 158.5, 'y' => 52, 'type' => 'Cell', 'width' => 10, 'height' => 5.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->gothic, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'tel2',     'suffix' => '', 'x' => 170.7, 'y' => 52, 'type' => 'Cell', 'width' => 9.8, 'height' => 5.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->gothic, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'tel3',     'suffix' => '', 'x' => 182.0, 'y' => 52, 'type' => 'Cell', 'width' => 11.6, 'height' => 5.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->gothic, 'style' => '',  'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nengo',    'suffix' => '', 'x' => 63.0, 'y' => 13.5, 'type' => 'Cell', 'width' => 10, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mincho, 'style' => '',  'size' => 6, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'year',     'suffix' => '', 'x' => 22.2, 'y' => 50.3, 'type' => 'Cell', 'width' => 5, 'height' => 2.8, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 7, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'year',     'suffix' => '', 'x' => 20.0, 'y' => 15.0, 'type' => 'Cell', 'width' => 7.7, 'height' => 3.8, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 7, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'month',    'suffix' => '', 'x' => 29.0, 'y' => 15.0, 'type' => 'Cell', 'width' => 7.7, 'height' => 3.8, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 7, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'day',      'suffix' => '', 'x' => 37.5, 'y' => 15.0, 'type' => 'Cell', 'width' => 7.7, 'height' => 3.8, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '',  'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'kankatu',  'suffix' => '', 'x' => 17.0, 'y' => 11, 'type' => 'Cell', 'width' => 19.4, 'height' => 3.8, 'align' => 'C', 'flg' => true],
            ['font' => $this->gothic, 'style' => 'B', 'size' => 17, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'kubun',    'suffix' => '', 'x' => 122.0, 'y' => 11.7, 'type' => 'Cell', 'width' => 20, 'height' => 9.0, 'align' => 'L', 'flg' => true],
        ];
        $this->pdf->draw($ary, $data);

        $x1 = self::PDF_ELLIPSE_MAP['gender'][$this->oas_config->gender];
        $x2 = self::PDF_ELLIPSE_MAP['formtype'][1];
        $x3 = self::PDF_ELLIPSE_MAP['phoneplace'][$this->oas_config->phoneplace];
        $line_map = $this->pdfmap['page1']['header_choices'] ?? [
            ['name' => 'circle',  'x' => $x1, 'y' => 49, 'r' => 1.6, 'astart' => 0, 'angend' => 360, 'type' => 'Circle', 'style' => 'D', 'line_style' => self::LINE_STYLE],
            ['name' => 'circle',  'x' => $x2, 'y' => 60, 'r' => 2,   'astart' => 0, 'angend' => 360, 'type' => 'Circle', 'style' => 'D', 'line_style' => self::LINE_STYLE],
            ['name' => 'ellipse', 'x' => $x3, 'y' => 51.8, 'rx' => 4, 'ry' => 1.6, 'angle' => 0, 'astart' => 0, 'afinish' => 360, 'type' => 'Ellipse', 'style' => 'D', 'line_style' => self::LINE_STYLE],
        ];
        $this->pdf->draw($line_map, ['circle' => 1, 'ellipse' => 1]);
    }

    private function drawBank()
    {
        if (is_null($this->oas_config)) {
            return;
        }
        $data = [
            'bank' => $this->oas_config->bank,
            'shiten' => $this->oas_config->branch,
            'koza' => $this->oas_config->account_number
        ];
        $ary = $this->pdfmap['page1']['bank'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'bank',   'suffix' => '', 'x' => 114.5, 'y' => 240, 'type' => 'Cell', 'width' => 28.8, 'height' => 7.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'shiten', 'suffix' => '', 'x' => 157.5, 'y' => 240, 'type' => 'Cell', 'width' => 25, 'height' => 7.3, 'align' => 'C', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'koza',   'suffix' => '', 'x' => 124.0, 'y' => 256.5, 'type' => 'Cell', 'width' => 68.5, 'height' => 4.9, 'align' => 'L', 'flg' => true, 'pitch' => 3.0],
        ];
        $this->pdf->draw($ary, $data);

        $x1 = self::PDF_ELLIPSE_MAP['bank_type'][$this->oas_config->bank_type]['x'];
        $x2 = self::PDF_ELLIPSE_MAP['branch_type'][$this->oas_config->branch_type]['x'];
        $x3 = self::PDF_ELLIPSE_MAP['account_type'][$this->oas_config->account_type];
        $y1 = self::PDF_ELLIPSE_MAP['bank_type'][$this->oas_config->bank_type]['y'];
        $y2 = self::PDF_ELLIPSE_MAP['branch_type'][$this->oas_config->branch_type]['y'];
        $y3 = 253.3;
        $line_map = $this->pdfmap['page1']['bank_choices'] ?? [
            ['name' => 'ellipse', 'x' => $x1, 'y' => $y1, 'rx' => 2.6, 'ry' => 1.6, 'angle' => 0, 'astart' => 0, 'afinish' => 360, 'type' => 'Ellipse', 'style' => 'D', 'line_style' => self::LINE_STYLE],
            ['name' => 'ellipse', 'x' => $x2, 'y' => $y2, 'rx' => 2.5, 'ry' => 1.6, 'angle' => 0, 'astart' => 0, 'afinish' => 360, 'type' => 'Ellipse', 'style' => 'D', 'line_style' => self::LINE_STYLE],
            ['name' => 'circle',  'x' => $x3, 'y' => $y3, 'r' => 1.5, 'astart' => 0, 'angend' => 360, 'type' => 'Circle', 'style' => 'D', 'line_style' => self::LINE_STYLE],
        ];
        $this->pdf->draw($line_map, ['circle' => 1, 'ellipse' => 1]);
    }

    public function drawDetail($target_year)
    {
        $start = date('Y', strtotime($target_year));
        $sql = 'SELECT * FROM `table::account_book`
                 WHERE userkey = ? AND year = ?';
        if (!$this->db->query($sql, [$this->uid, $start])) {
            return false;
        }
        $result = $this->db->fetch();

        $basic_deduction = $this->oas_config->basic_deduction_column ?? 'col_24';

        $step = 0;
        $total = 0;
        $data = [];

        ksort($data);

        $origin = $this->pdfmap['page1']['item'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 11,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 58.5,
            'y' => 62.8,
            'type' => 'Cell',
            'width' => 44.7,
            'height' => 6.26,
            'align' => 'R',
            'flg' => true,
            'pitch' => 2.8
        ];

        $y = $origin['y'];
        $h = $origin['height'];
        $lineheight = $this->pdfmap['page1']['lineheight'] ?? $h;
        $columnright = $this->pdfmap['page1']['columnright'] ?? 149;

        $w = $origin['width'];
        $x = $origin['x'];
        $t = $origin['pitch'];

        $ary = [];
        $s = $this->pdfmap['page1']['income1']['start'] ?? 1;
        $e = $this->pdfmap['page1']['income1']['end'] ?? 11;
        for ($i = $s; $i <= $e; $i++) {
            $key = sprintf('bol_%02d', $i);
            if (!empty($result[$key] ?? null)) {
                $data[$key] = $result[$key];
                $cell = $origin;
                $cell['y'] = $y;
                $cell['name'] = $key;
                $ary[] = $cell;
            }
            $y += $lineheight;
        }

        $skip = $this->pdfmap['page1']['column']['skip'] ?? [];
        $s = $this->pdfmap['page1']['income2']['start'] ?? 1;
        $e = $this->pdfmap['page1']['income2']['end'] ?? 29;
        $calc = $this->pdfmap['page1']['calc'] ?? [];
        $override = [];
        for ($i = $s; $i <= $e; $i++) {
            $key = sprintf('col_%02d', $i);
            if (in_array($key, $skip)) {
                continue;
            }
            if (!empty($result[$key] ?? null)) {
                $data[$key] = $result[$key];
            }

            $width = null;
            if (isset($calc[$key])) {
                $unit = $calc[$key];
                switch ($unit['type']) {
                    case 'floor':
                        if (!empty($data[$key])) {
                            $override[$key] = floor($data[$key] / $unit['figure']);
                            $width = $unit['width'] ?? null;
                        }
                        break;
                    case 'life insurance':
                        $sum = 0;
                        if (preg_match('/^([0-9]+)-([0-9]+)$/', $unit['data'], $match)) {
                            $this->db->query(
                                'SELECT SUM(`amount`) as `amount` FROM `table::social_insurance` WHERE `year` = ? AND `colnumber` = ? GROUP BY `colnumber`',
                                [$start, $unit['data']]
                            );
                            $sum = $this->db->fetchColumn();
                            switch ($match[2]) {
                                case '1':
                                    if ($sum > 80000) {
                                        $sum = 40000;
                                    } elseif ($sum <= 80000 and $sum > 40000) {
                                        $sum = $sum * 0.25 + 20000;
                                    } elseif ($sum <= 40000 and $sum > 20000) {
                                        $sum = $sum * 0.5 + 10000;
                                    }
                                    break;
                            }
                            $data[$key] = ceil($sum);
                        }
                        break;
                    case 'sum':
                        $sum = 0;
                        foreach ($unit['data'] as $k) {
                            $sum += $data[$k] ?? 0;
                        }
                        $data[$key] = $sum;
                        break;
                }
            }

            if (!empty($data[$key])) {
                $cell = $origin;
                $cell['y'] = $y;
                $cell['name'] = $key;
                if (!is_null($width)) {
                    $cell['width'] = $width;
                }
                $ary[] = $cell;
            }
            $y += $lineheight;
        }

        $data = array_merge($data, $override);

        $y = $origin['y'];
        $origin['x'] = $columnright;

        $s = $this->pdfmap['page1']['tax']['start'] ?? 30;
        $e = $this->pdfmap['page1']['tax']['end'] ?? 63;
        $override = [];
        for ($i = $s; $i <= $e; $i++) {
            $key = sprintf('col_%02d', $i);
            if (in_array($key, $skip)) {
                continue;
            }
            if (!empty($result[$key] ?? null)) {
                $data[$key] = $result[$key];
            }

            $width = null;
            if (isset($calc[$key])) {
                $unit = $calc[$key];
                switch ($unit['type']) {
                    case 'config':
                        $name = $unit['name'];
                        $data[$key] = $this->oas_config->$name ?? null;
                        break;
                    case 'diff':
                        $base = array_shift($unit['data']);
                        $diff = $data[$base];
                        foreach ($unit['data'] as $k) {
                            $diff -= $data[$k] ?? 0;
                        }
                        if (isset($unit['floor'])) {
                            $diff = floor($diff / $unit['floor']);
                            if (($unit['notoverride'] ?? null) !== 1) {
                                $override[$key] = $diff;
                            }
                            $diff *= $unit['floor'];
                            $width = $unit['width'] ?? null;
                        }

                        if ($diff < 0 && isset($unit['negative'])) {
                            $diff = $unit['negative'];
                        }

                        $data[$key] = $diff;
                        break;
                    case 'floor':
                        if (!empty($data[$key])) {
                            $override[$key] = floor($data[$key] / $unit['figure']);
                            $width = $unit['width'] ?? null;
                        }
                        break;
                    case 'multiplication':
                        $base = $data[$unit['data']];
                        $data[$key] = floor($base * $unit['value']);
                        break;
                    case 'sum':
                        $sum = 0;
                        foreach ($unit['data'] as $k) {
                            $sum += $data[$k] ?? 0;
                        }
                        $data[$key] = $sum;
                        break;
                    case 'tax':
                        $total = $data[$unit['amount']];
                        if ($total < 1000) {
                            $tax = 0;
                        } elseif ($total < 1950000) {
                            $tax = $total * 0.05;
                        } elseif ($total < 3300000) {
                            $tax = $total * 0.1 - 97500;
                        } elseif ($total < 6950000) {
                            $tax = $total * 0.2 - 427500;
                        } elseif ($total < 9000000) {
                            $tax = $total * 0.23 - 636000;
                        } elseif ($total < 18000000) {
                            $tax = $total * 0.33 - 1536000;
                        } else {
                            $tax = $total * 0.4 - 2796000;
                        }

                        $data[$key] = floor($tax / 10) * 10;
                        break;
                }
            }

            if (!empty($data[$key])) {
                $cell = $origin;
                $cell['y'] = $y;
                $cell['name'] = $key;
                if (!is_null($width)) {
                    $cell['width'] = $width;
                }
                $ary[] = $cell;
            }
            $y += $lineheight;
        }

        $data = array_merge($data, $override);

        $this->pdf->draw($ary, $data);
    }

    private function medicalCostDeduction($income, $medicalcost, $insurance = 0)
    {
        $c = $medicalcost - $insurance;
        $e = floor($income * 0.05);
        $f = min($e, 1000000);
        $g = $c - $f;
        if ($g < 0) {
            $g = 0;
        }

        return min(2000000, $g);
    }
}
