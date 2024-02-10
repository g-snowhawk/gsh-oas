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
use Gsnowhawk\Oas\Fixedasset;
use Gsnowhawk\Pdf;

/**
 * Category management response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Financial extends \Gsnowhawk\Oas\Taxation
{
    private $sum_income = 0;
    private $sum_buying = 0;
    private $sum_fixedasset = 0;

    private $investments = 0;
    private $withdrawals = 0;
    private $deposit = 0;

    private $column33 = 0;
    private $column43 = 0;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array(parent::class.'::__construct', $params);

        $this->view->bind(
            'header',
            ['title' => Lang::translate('HEADER_TITLE'), 'id' => 'osa-taxation-financial', 'class' => 'taxation']
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

        $template_path = 'oas/taxation/financial.tpl';
        $html_id = 'oas-taxation-financial';

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

        $this->pdf->loadTemplate('oas/taxation/financial.pdf');

        $mapfile = $this->privateSavePath() . '/templates/oas/taxation/financial.json';
        if (file_exists($mapfile)) {
            $this->pdfmap = json_decode(file_get_contents($mapfile), true);
            if (is_null($this->pdfmap)) {
                echo json_last_error_msg();
                exit;
            }
        }

        $this->page2($target_year);
        $this->page3($target_year);
        $this->page1($target_year);
        $this->pdf->movePage(3, 1);
        $this->page4($target_year);
        $this->pdf->setPage(2, false);
        $this->drawDeduction();

        $amount = $this->withdrawals + $this->deposit + $this->column43 - $this->investments;
        $item_code = $this->filter_items['DEPOSIT'];
        $this->transferAmount($year, $item_code, $amount);

        $file = $this->getPdfPath($year, 'taxation', 'financialsheet.pdf');
        $locked = ($this->request->POST('locked') === '1') ? true : false;
        $this->outputPdf(basename($file), dirname($file), true, $locked);
    }

    private function page1($target_year)
    {
        $this->pdf->addPageFromTemplate(1, 'L');

        $this->drawHeader($target_year);
        $income = $this->drawIncome($target_year);
        $buying = $this->drawBuying($target_year);
        $cost = $this->drawCost($target_year);
        $data = [
            'no07' => $income - $buying,
            'no37' => 0,
            'no42' => 0,
        ];
        $data['no33'] = $data['no07'] - $cost;
        $data['no43'] = $data['no33'] + $data['no37'] - $data['no42'];
        $data['no44'] = min($data['no43'], $this->oas_config->blue_return_deduction);
        $data['no45'] = $data['no43'] - $data['no44'];
        $ary = $this->pdfmap['page1']['columns'] ?? [
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no07', 'suffix' => '', 'x' => 59.5, 'y' => 125.5, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no33', 'suffix' => '', 'x' => 145.8, 'y' => 182.7, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no43', 'suffix' => '', 'x' => 232.2, 'y' => 135.0, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no44', 'suffix' => '', 'x' => 232.2, 'y' => 141.5, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no45', 'suffix' => '', 'x' => 232.2, 'y' => 151.0, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
        ];
        $this->pdf->draw($ary, $data);

        $basic_deduction = $this->oas_config->basic_deduction_column ?? 'col_24';
        $amounts = [
            'bol_01' => $income,
            'col_01' => $data['no45'],
            $basic_deduction => $this->oas_config->basic_deduction,
        ];
        $year = date('Y', strtotime($target_year));
        if (false === $this->updateAccountBook($year, $amounts)) {
            trigger_error($this->db->error());
        }

        $this->column33 = $data['no33'];
        $this->column43 = $data['no43'];
    }

    private function page2($target_year)
    {
        $this->pdf->addPageFromTemplate(2, 'L');

        $data = [
            'nengo' => $this->toWareki($target_year),
            'name' => $this->userinfo['fullname'],
            'rubi' => $this->userinfo['fullname_rubi'],
        ];
        $ary = $this->pdfmap['page2']['header'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 6, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'rubi',  'suffix' => '', 'x' => 70, 'y' => 13.0, 'type' => 'Cell', 'width' => 35, 'height' => 4, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'name',  'suffix' => '', 'x' => 70, 'y' => 16.0, 'type' => 'Cell', 'width' => 35, 'height' => 5, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 12, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nengo', 'suffix' => '', 'x' => 32.0, 'y' => 7.8, 'type' => 'Cell', 'width' => 10, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.5],
        ];
        $this->pdf->draw($ary, $data);

        //
        $this->sum_income = $this->drawIncomeDetail($target_year);
        $this->sum_buying = $this->drawBuyingDetail($target_year);
    }

    private function page3($target_year)
    {
        $this->pdf->addPageFromTemplate(3, 'L');
        $this->sum_fixedasset = $this->drawFixedassetsDetail($target_year);
    }

    private function page4($target_year)
    {
        $this->pdf->addPageFromTemplate(4, 'L');

        $year = date('Y', strtotime($target_year));
        $data['year'] = $this->toWareki($target_year);
        $data['sMonth'] = '1';
        $data['sDay'] = '1';
        $data['eMonth'] = '12';
        $data['eDay'] = '31';

        $ary = $this->pdfmap['page4']['header'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'year',   'suffix' => '', 'x' => 170.2, 'y' => 19.3, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eMonth', 'suffix' => '', 'x' => 178.2, 'y' => 19.3, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eDay',   'suffix' => '', 'x' => 186.2, 'y' => 19.3, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sMonth', 'suffix' => '', 'x' => 49.5, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sDay',   'suffix' => '', 'x' => 58.0, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eMonth', 'suffix' => '', 'x' => 79.5, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eDay',   'suffix' => '', 'x' => 88, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sMonth', 'suffix' => '', 'x' => 140.5, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sDay',   'suffix' => '', 'x' => 148.5, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eMonth', 'suffix' => '', 'x' => 170.5, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eDay',   'suffix' => '', 'x' => 179.0, 'y' => 30.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true],
        ];
        $this->pdf->draw($ary, $data);

        $this->drawCreditor($year);
        $this->drawCreditor($year + 1, $year);

        $this->drawDebit($year);
        $this->drawDebit($year + 1, $year);
    }

    private function drawIncomeDetail($target_year)
    {
        $start = date('Y-01-01 00:00:00', strtotime($target_year));
        $end = date('Y-12-31 23:59:59', strtotime($target_year));
        $item_code = $this->filter_items['SALES'];

        $sql = function ($col) {
            return "SELECT DATE_FORMAT(issue_date, '%c') AS month,
                           SUM(amount_{$col}) AS amount
                      FROM `table::transfer`
                     WHERE item_code_{$col} = ?
                       AND (issue_date >= ? AND issue_date <= ?)
                  GROUP BY month
                  ORDER BY month";
        };

        if (!$this->db->query($sql('left'), [$item_code, $start, $end])) {
            return false;
        }
        $result2 = $this->db->fetchAll();
        $umount = [];
        foreach ($result2 as $unit) {
            $umount[$unit['month']] = $unit['amount'];
        }

        if (!$this->db->query($sql('right'), [$item_code, $start, $end])) {
            return false;
        }
        $result = $this->db->fetchAll();

        $data = [];
        $ary = [];
        $total = 0;
        foreach ($result as $unit) {
            $amount = $unit['amount'];
            if (isset($umount[$unit['month']])) {
                $amount -= $umount[$unit['month']];
            }
            $data[$unit['month']] = number_format($amount);
            $total += $amount;
        }
        ksort($data);
        $y = $this->pdfmap['page2']['income']['month']['y'] ?? 37;
        $x = $this->pdfmap['page2']['income']['month']['x'] ?? 77.3;
        $w = $this->pdfmap['page2']['income']['month']['width'] ?? 44.7;
        $h = $this->pdfmap['page2']['income']['month']['height'] ?? 6.3;
        $t = $this->pdfmap['page2']['income']['total']['pitch'] ?? 3.0;
        $origin = $this->pdfmap['page2']['income']['month'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'suffix' => '',
            'type' => 'Cell',
            'align' => 'R',
            'flg' => true
        ];
        for ($cnt = 1; $cnt <= 12; $cnt++) {
            $month = $origin;
            $month['name'] = $cnt;
            $month['x'] = $x;
            $month['y'] = $y;
            $month['width'] = $w - 2;
            $month['height'] = $h;
            $ary[] = $month;
            $y += $h;
        }

        $sql = function ($col) {
            return "SELECT item_code_{$col},
                           SUM(amount_{$col}) AS amount
                      FROM `table::transfer`
                     WHERE item_code_{$col} = ?
                       AND (issue_date >= ? AND issue_date <= ?)
                  GROUP BY item_code_{$col}";
        };

        // home use
        $item_code = $this->filter_items['HOUSEWORK'];
        if (!$this->db->query($sql('right'), [$item_code, $start, $end])) {
            return false;
        }
        $result = $this->db->fetch();
        if (is_array($result)) {
            $data[$item_code] = $result['amount'];
            $total += $result['amount'];
        }
        $ary[] = $this->pdfmap['page2']['income']['home'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => $item_code,
            'suffix' => '',
            'x' => $x - 1.8,
            'y' => $y,
            'type' => 'Cell',
            'width' => $w,
            'height' => $h,
            'align' => 'R',
            'flg' => true,
            'pitch' => $t
        ];
        $y += $h;

        // other use
        $item_code = $this->filter_items['MISCELLANOUS_INCOME'];
        if (!$this->db->query($sql('right'), [$item_code, $start, $end])) {
            return false;
        }
        $result = $this->db->fetch();
        if (is_array($result)) {
            $data[$item_code] = $result['amount'];
            $total += $result['amount'];
        }
        $ary[] = $this->pdfmap['page2']['income']['other'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => $item_code,
            'suffix' => '',
            'x' => $x - 1.8,
            'y' => $y,
            'type' => 'Cell',
            'width' => $w,
            'height' => $h,
            'align' => 'R',
            'flg' => true,
            'pitch' => $t
        ];
        $y += $h;

        // Total
        $data['total'] = $total;
        $ary[] = $this->pdfmap['page2']['income']['total'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => 'total',
            'suffix' => '',
            'x' => $x - 1.8,
            'y' => $y + 3.5,
            'type' => 'Cell',
            'width' => $w,
            'height' => $h,
            'align' => 'R',
            'flg' => true,
            'pitch' => $t
        ];

        $this->pdf->draw($ary, $data);

        return $total;
    }

    private function drawBuyingDetail($target_year)
    {
        $start = date('Y-01-01 00:00:00', strtotime($target_year));
        $end = date('Y-12-31 23:59:59', strtotime($target_year));
        $item_code = $this->filter_items['SALES'];

        $sql = function ($col) {
            return "SELECT DATE_FORMAT(issue_date, '%c') AS month,
                           SUM(amount_{$col}) AS amount
                      FROM `table::transfer`
                     WHERE item_code_{$col} = ?
                       AND category NOT IN ('A','Z')
                       AND (issue_date >= ? AND issue_date <= ?)
                  GROUP BY month
                  ORDER BY month";
        };

        if (!$this->db->query($sql('left'), [$item_code, $start, $end])) {
            return false;
        }
        $result = $this->db->fetchAll();
        $data = [];
        $ary = [];
        $total = 0;
        foreach ($result as $unit) {
            $data[$unit['month']] = number_format($unit['amount']);
            $total += $unit['amount'];
        }
        ksort($data);
        $y = $this->pdfmap['page2']['buying']['month']['y'] ?? 37;
        $x = $this->pdfmap['page2']['buying']['month']['x'] ?? 77.3;
        $w = $this->pdfmap['page2']['buying']['month']['width'] ?? 44.7;
        $h = $this->pdfmap['page2']['buying']['month']['height'] ?? 6.3;
        $t = $this->pdfmap['page2']['buying']['total']['pitch'] ?? 3.0;
        $origin = $this->pdfmap['page2']['buying']['month'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'suffix' => '',
            'type' => 'Cell',
            'align' => 'R',
            'flg' => true
        ];
        for ($cnt = 1; $cnt <= 12; $cnt++) {
            $month = $origin;
            $month['name'] = $cnt;
            $month['x'] = $x;
            $month['y'] = $y;
            $month['width'] = $w - 2;
            $month['height'] = $h;
            $ary[] = $month;
            $y += $h;
        }
        $y += $h * 2;

        // Total
        $data['total'] = $total;
        $ary[] = $this->pdfmap['page2']['buying']['total'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => 'total',
            'suffix' => '',
            'x' => $x - 1.8,
            'y' => $y + 3.5,
            'type' => 'Cell',
            'width' => $w,
            'height' => $h,
            'align' => 'R',
            'flg' => true,
            'pitch' => $t
        ];

        $this->pdf->draw($ary, $data);

        return $total;
    }

    public function drawFixedassetsDetail($target_year)
    {
        $sql = "SELECT *
                  FROM `table::fixed_assets`
                 WHERE quantity > 0 AND DATE_FORMAT(acquire,'%Y') <= ?";
        if (!$this->db->query($sql, [$target_year])) {
            return 0;
        }

        $total = 0;
        $dpsum = 0;
        $spsum = 0;
        $ohsum = 0;
        $tysum = 0;
        $line = 0;

        $origin = $this->pdfmap['page3']['item'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'name',            'suffix' => '', 'x' => 19.5, 'y' => 35.4, 'type' => 'Cell', 'width' => 20.5, 'height' => 7.15, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'quantity',        'suffix' => '', 'x' => 36.0, 'y' => 35.4, 'type' => 'Cell', 'width' => 9.8, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'acyear',          'suffix' => '', 'x' => 49.7, 'y' => 36.2, 'type' => 'Cell', 'width' => 5, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'acmon',           'suffix' => '', 'x' => 55.5, 'y' => 36.2, 'type' => 'Cell', 'width' => 4.6, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'price1',          'suffix' => '', 'x' => 61.0, 'y' => 35.4, 'type' => 'Cell', 'width' => 19, 'height' => 4.00, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'price2',          'suffix' => '', 'x' => 84.2, 'y' => 35.4, 'type' => 'Cell', 'width' => 19, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'type',            'suffix' => '', 'x' => 105.3, 'y' => 35.4, 'type' => 'Cell', 'width' => 9.9, 'height' => 7.15, 'align' => 'C', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'durability',      'suffix' => '', 'x' => 115, 'y' => 35.4, 'type' => 'Cell', 'width' => 7, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'rate',            'suffix' => '', 'x' => 124.2, 'y' => 35.4, 'type' => 'Cell', 'width' => 9.85, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 7, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'months',          'suffix' => '', 'x' => 139.0, 'y' => 35.4, 'type' => 'Cell', 'width' => 4.5, 'height' => 4.00, 'align' => 'C', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'depreciate',      'suffix' => '', 'x' => 146, 'y' => 35.4, 'type' => 'Cell', 'width' => 18, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'special',         'suffix' => '', 'x' => 166, 'y' => 35.4, 'type' => 'Cell', 'width' => 18, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'depreciatetotal', 'suffix' => '', 'x' => 186, 'y' => 35.4, 'type' => 'Cell', 'width' => 18.5, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'official_ratio',  'suffix' => '', 'x' => 207, 'y' => 35.4, 'type' => 'Cell', 'width' => 8, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'thisyear',        'suffix' => '', 'x' => 216.5, 'y' => 35.4, 'type' => 'Cell', 'width' => 18.5, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'onhand',          'suffix' => '', 'x' => 236.6, 'y' => 35.4, 'type' => 'Cell', 'width' => 19, 'height' => 7.15, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'note',            'suffix' => '', 'x' => 257.5, 'y' => 35.4, 'type' => 'Cell', 'width' => 20, 'height' => 7.15, 'align' => 'L', 'flg' => true],
        ];
        $y = $origin[0]['y'];
        $lh = $origin[0]['height'];

        while ($result = $this->db->fetch()) {
            $durability = intval($result['durability']);
            $t = intval(date('Y', strtotime($target_year)));
            $s = intval(date('Y', strtotime($result['acquire'])));
            $limit = $s + $durability;

            $cln = clone $this->db;
            $sql = 'SELECT *
                      FROM `' . $cln->TABLE('fixed_assets_detail') . '`
                     WHERE id = ' . $cln->quote($result['id']) . '
                       AND year = ' . $cln->quote($t);
            if (false !== $cln->query($sql)) {
                while ($ido = $cln->fetch()) {
                    $result['ido'] = $ido;
                    $result['note'] = $ido['note'];
                }
            }

            //
            $lda = ($result['item'] === $this->filter_items['LUMPSUM_DEPRECIABLE_ASSETS']);
            $depreciate = Fixedasset::depreciate($result, $lda, $t, false);
            $depreciable = Fixedasset::depreciate($result, $lda, $t);
            $price_onhand = ($result['quantity'] * $result['price']) - $depreciable;
            if (isset($result['ido']['month'])) {
                $price_onhand = 0;
            }
            $dpsum += $depreciate;
            $ohsum += $price_onhand;

            //
            $special = 0;
            $spsum += $special;

            //
            $total += $depreciate - $special;
            $tysum += ($depreciate - $special) * ($result['official_ratio'] / 100);

            if (isset($result['ido']['month'])) {
                $months = $result['ido']['month'];
            } elseif ($t == $s) {
                $months = 13 - date('n', strtotime($result['acquire']));
            } elseif ($t == $limit) {
                $months = 12 - (13 - date('n', strtotime($result['acquire'])));
            } else {
                $months = 12;
            }

            $acyear = $this->toWareki($result['acquire']);

            $data = [
                'name' => $result['title'],
                'quantity' => $result['quantity'],
                'acyear' => $acyear,
                'acmon' => date('n', strtotime($result['acquire'])),
                'price1' => number_format($result['price']),
                'price2' => number_format($result['price']),
                'type' => $result['depreciate_type'],
                'durability' => $result['durability'],
                'rate' => sprintf('%01.3f', $result['depreciate_rate']),
                'months' => $months,
                'depreciate' => number_format($depreciate),
                'depreciatetotal' => number_format($depreciate - $special),
                'official_ratio' => $result['official_ratio'],
                'thisyear' => number_format(($depreciate - $special) * ($result['official_ratio'] / 100)),
                'onhand' => number_format($price_onhand),
            ];

            if ($result['item'] === $this->filter_items['LUMPSUM_DEPRECIABLE_ASSETS']) {
                //$data['quantity'] = '-';
                //$data['acmon'] = null;
                $data['type'] = '-';
                $data['durability'] = '-';
                $data['rate'] = '-';
                $data['months'] = '-';
            }

            // Special
            if ($special > 0) {
                $data['special'] = $special;
            }
            // Note
            $data['note'] = (isset($result['note'])) ? $result['note'] : '';

            $line++;
            //$ym = $y + 0.8;
            $ary = [];
            foreach ($origin as $i => $cell) {
                $cell['y'] = $y;
                if ($i === 2 || $i === 3) {
                    $cell['y'] += 0.8;
                } elseif ($i === 4 || $i === 9) {
                    $cell['y'] -= 0.5;
                }
                $ary[] = $cell;
            }
            $this->pdf->draw($ary, $data);
            $y += $lh;
        }
        for ($i = $line; $i < 11; $i++) {
            $y += $lh;
        }
        $y += 1.3;
        $lh = 6;
        $data = [
            'depreciate' => number_format($dpsum),
            'depreciatetotal' => number_format($total),
            'thisyear' => number_format($tysum),
            'onhand' => number_format($ohsum),
        ];
        if ($spsum > 0) {
            $data['special'] = $spsum;
        }
        $ary = $this->pdfmap['page3']['total'] ?? [
            ['font' => $this->mono, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'depreciate',      'suffix' => '', 'x' => 146, 'y' => $y, 'type' => 'Cell', 'width' => 18, 'height' => $lh, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'special',         'suffix' => '', 'x' => 166, 'y' => $y, 'type' => 'Cell', 'width' => 18, 'height' => $lh, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'depreciatetotal', 'suffix' => '', 'x' => 186, 'y' => $y, 'type' => 'Cell', 'width' => 18.5, 'height' => $lh, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'thisyear',        'suffix' => '', 'x' => 216.5, 'y' => $y, 'type' => 'Cell', 'width' => 18.5, 'height' => $lh, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'onhand',          'suffix' => '', 'x' => 236.6, 'y' => $y, 'type' => 'Cell', 'width' => 19, 'height' => $lh, 'align' => 'R', 'flg' => true],
        ];
        $this->pdf->draw($ary, $data);

        return $total;
    }

    private function drawHeader($target_year)
    {
        $data = [];
        $data['address1'] = $this->userinfo['city'] . $this->userinfo['town'] . $this->userinfo['address1'];
        $data['address2'] = $this->userinfo['address2'];
        $data['company'] = $this->userinfo['company'];
        $data['name'] = $this->userinfo['fullname'];
        $data['rubi'] = $this->userinfo['fullname_rubi'];
        $data['tel'] = $this->userinfo['tel'];

        // fixed properties
        $data['caddress'] = $this->oas_config->caddress;
        $data['works'] = $this->oas_config->works;
        $data['telhome'] = $this->oas_config->homephone;

        // today
        $data['year'] = $this->toWareki(date('Y-m-d'));
        $data['month'] = date('n');
        $data['day'] = date('j');
        $data['nengo'] = $this->toWareki($target_year);
        $data['sMonth'] = '1';
        $data['sDay'] = '1';
        $data['eMonth'] = '12';
        $data['eDay'] = '31';

        $lh = (empty($data['address2'])) ? 9.2 : 4.5;
        $ary = $this->pdfmap['page1']['header'] ?? [
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'address1', 'suffix' => '', 'x' => 114.8, 'y' => 24, 'type' => 'Cell', 'width' => 56, 'height' => $lh, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'caddress', 'suffix' => '', 'x' => 114.8, 'y' => 33.5, 'type' => 'Cell', 'width' => 56, 'height' => $lh, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'works',    'suffix' => '', 'x' => 114.8, 'y' => 43, 'type' => 'Cell', 'width' => 23, 'height' => $lh, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'company',  'suffix' => '', 'x' => 148.5, 'y' => 43, 'type' => 'Cell', 'width' => 23, 'height' => $lh, 'align' => 'C', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 6, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'rubi',     'suffix' => '', 'x' => 192, 'y' => 25, 'type' => 'Cell', 'width' => 35, 'height' => 4, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'name',     'suffix' => '', 'x' => 192, 'y' => 28, 'type' => 'Cell', 'width' => 35, 'height' => 5, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'telhome',  'suffix' => '', 'x' => 197, 'y' => 34, 'type' => 'Cell', 'width' => 28, 'height' => 4.5, 'align' => 'L', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'tel',      'suffix' => '', 'x' => 197, 'y' => 38, 'type' => 'Cell', 'width' => 28, 'height' => 4.5, 'align' => 'L', 'flg' => true],
            ['font' => $this->mono,   'style' => '', 'size' => 12, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'nengo',    'suffix' => '', 'x' => 113.5, 'y' => 14.0, 'type' => 'Cell', 'width' => 12, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.5],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sMonth',   'suffix' => '', 'x' => 155.3, 'y' => 63.6, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'sDay',     'suffix' => '', 'x' => 170.5, 'y' => 63.6, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eMonth',   'suffix' => '', 'x' => 191.0, 'y' => 63.6, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mono,   'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'eDay',     'suffix' => '', 'x' => 206.4, 'y' => 63.6, 'type' => 'Cell', 'width' => 9.8, 'height' => 6.2, 'align' => 'R', 'flg' => true, 'pitch' => 3.0],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'year',     'suffix' => '', 'x' => 23, 'y' => 62.8, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'month',    'suffix' => '', 'x' => 32, 'y' => 62.8, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
            ['font' => $this->mincho, 'style' => '', 'size' => 8, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'day',      'suffix' => '', 'x' => 41, 'y' => 62.8, 'type' => 'Cell', 'width' => 6, 'height' => 4.5, 'align' => 'R', 'flg' => true],
        ];
        if (!empty($data['address2'])) {
            $ary[] = ['font' => $this->mincho, 'style' => '', 'size' => 9, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'address2', 'suffix' => '', 'x' => 123, 'y' => 30.5, 'type' => 'Cell', 'width' => 56, 'height' => $lh, 'align' => 'L', 'flg' => true];
        }
        $this->pdf->draw($ary, $data);
    }

    private function drawIncome($target_year)
    {
        $start = date('Y-01-01 00:00:00', strtotime($target_year));
        $end = date('Y-12-31 23:59:59', strtotime($target_year));
        $sql = 'SELECT ai.item_code AS code,
                       SUM(td.amount_right) AS amount,
                       MIN(ai.item_name) AS label
                FROM `table::account_items` ai
                LEFT JOIN (
                    SELECT *
                      FROM `table::transfer`
                     WHERE userkey = ?
                       AND (issue_date >= ? AND issue_date <= ?)
                ) td
                ON td.item_code_right = ai.item_code
                WHERE (ai.item_code = 8112 OR ai.item_code = 8791)
                GROUP BY code';
        if (!$this->db->query($sql, [$this->uid, $start, $end])) {
            return false;
        }
        $result = $this->db->fetchAll();

        $data = [];
        $total = $this->sum_income;
        foreach ($result as $val) {
            if (empty($val['amount'])) {
                continue;
            }
        }

        ksort($data);

        $origin = $this->pdfmap['page1']['income'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => 'total',
            'suffix' => '',
            'x' => 59.58,
            'y' => 81,
            'type' => 'Cell',
            'width' => 44.7,
            'height' => 6.16,
            'align' => 'R',
            'flg' => true,
            'pitch' => 3.0
        ];
        $data['total'] = $total;
        $ary = [$origin];

        $this->pdf->draw($ary, $data);

        return $total;
    }

    private function drawBuying($target_year)
    {
        $start = date('Y-01-01 00:00:00', strtotime($target_year));
        $end = date('Y-12-31 23:59:59', strtotime($target_year));

        $data = [];
        $total = $this->sum_buying;

        foreach ([8211,8221,8241] as $key) {
            $where = ($key === 8221) ? " AND category NOT IN ('A','Z')" : '';
            $lr = ($key !== 8241) ? 'right' : 'left';

            $sql = "SELECT ai.item_code AS code,
                           SUM(td.amount_{$lr}) AS amount,
                           MIN(ai.item_name) AS label
                      FROM `table::account_items` ai
                      LEFT JOIN (
                          SELECT *
                            FROM `table::transfer`
                           WHERE userkey = ?
                             AND (issue_date >= ? AND issue_date <= ?){$where}
                      ) td
                        ON td.item_code_{$lr} = ai.item_code
                     WHERE ai.item_code = ?
                     GROUP BY code";

            if (!$this->db->query($sql, [$this->uid, $start, $end, $key])) {
                return false;
            }

            while ($result = $this->db->fetch()) {
                $data[$result['code']] = $result['amount'];
                if (empty($result['amount'])) {
                    continue;
                }
            }
        }

        ksort($data);

        $origin = $this->pdfmap['page1']['buying'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 59.58,
            'y' => 90.5,
            'type' => 'Cell',
            'width' => 44.7,
            'height' => 6.35,
            'align' => 'R',
            'flg' => true,
            'pitch' => 3.0
        ];
        $y = $origin['y'];
        $h = $origin['height'];
        $ary = [];
        foreach ($data as $key => $val) {
            if ($key === 8241) {
                continue;
            }
            $cell = $origin;
            $cell['y'] = $y;
            $cell['name'] = $key;
            $ary[] = $cell;
            $y += $h;
        }

        $purchase = $this->filter_items['PURCHASE'];
        $beginning_inventory = $this->filter_items['BEGINNING_INVENTORY'];
        $periodend_inventory = $this->filter_items['PERIODEND_INVENTORY'];

        $data['total'] = $data[$beginning_inventory] + $data[$purchase];
        $cell = $origin;
        $cell['y'] = $y;
        $cell['name'] = 'total';
        $ary[] = $cell;
        $y += $h;

        $periodend_inventory = $this->filter_items['PERIODEND_INVENTORY'];
        $cell = $origin;
        $cell['y'] = $y;
        $cell['name'] = $periodend_inventory;
        $ary[] = $cell;
        $y += $h;

        $data['offset'] = $data['total'] - $data[$periodend_inventory];
        $cell = $origin;
        $cell['y'] = $y;
        $cell['name'] = 'offset';
        $ary[] = $cell;

        $this->pdf->draw($ary, $data);

        return $data['offset'];
    }

    private function drawCost($target_year)
    {
        $start = date('Y-01-01 00:00:00', strtotime($target_year));
        $end = date('Y-12-31 23:59:59', strtotime($target_year));

        $sql = function ($col) {
            return "SELECT ai.item_code AS code,
                           SUM(td.amount_{$col}) AS amount,
                           MIN(ai.item_name) AS label,
                           MIN(ai.financial_order) AS financial_order,
                           CASE WHEN MIN(financial_order) <> 25 THEN 0
                                WHEN SUM(td.amount_{$col}) > 0 THEN 0
                                ELSE 1
                            END AS sorter
                      FROM `table::account_items` ai
                      LEFT JOIN (
                          SELECT * FROM `table::transfer`
                           WHERE userkey = ?
                             AND (issue_date >= ? AND issue_date <= ?)
                      ) td
                        ON td.item_code_{$col} = ai.item_code
                     WHERE ai.financial_order IS NOT NULL
                     GROUP BY code ORDER BY financial_order, sorter, code";
        };

        if (!$this->db->query($sql('left'), [$this->uid, $start, $end])) {
            return false;
        }

        $data1 = [];
        $order = [];
        $label = [];
        $subtotal = 0;
        $total = 0;
        $skip = 0;
        while ($val = $this->db->fetch()) {
            $data1[$val['code']] = (int)$val['amount'];
            $order[$val['code']] = (int)$val['financial_order'];
            $total += (int)$val['amount'];
            if ($val['financial_order'] >= 8 && $val['financial_order'] <= 31) {
                $subtotal += (int)$val['amount'];
            }
            if ($val['financial_order'] >= 25 && $val['financial_order'] <= 30) {
                $label[$val['code']] = $val['label'];
            }
        }
        if (!$this->db->query($sql('right'), [$this->uid, $start, $end])) {
            return false;
        }
        while ($val = $this->db->fetch()) {
            if (isset($data1[$val['code']])) {
                $data1[$val['code']] -= $val['amount'];
            }
            if (empty($val['amount'])) {
                continue;
            }
            $total -= $val['amount'];
        }

        ksort($label);

        $origin = $this->pdfmap['page1']['cost']['item'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 59.58,
            'y' => 135.0,
            'type' => 'Cell',
            'width' => 44.7,
            'height' => 6.35,
            'align' => 'R',
            'flg' => true,
            'pitch' => 3.0
        ];
        $y = $origin['y'];
        $x = $origin['x'];
        $h = $origin['height'];

        $lab_origin = [
            'font' => $this->mincho,
            'style' => '',
            'size' => 8,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 111.5,
            'y' => 135.0,
            'type' => 'Cell',
            'width' => 18,
            'height' => 6.35,
            'align' => 'J',
            'flg' => true
        ];
        $return = $this->pdfmap['page1']['cost']['return'] ?? [
            16 => ['x' => 145.8, 'y' => 78.0],
        ];

        $ary = [];
        $lab = [];
        foreach ($data1 as $key => $val) {
            if (!empty($val)) {
                if (isset($label[$key])) {
                    $cell = $lab_origin;
                    $cell['y'] = $y;
                    $cell['name'] = $key;
                    $lab[] = $cell;
                }
                $cell = $origin;
                $cell['x'] = $x;
                $cell['y'] = $y;
                $cell['name'] = $key;
                $ary[] = $cell;
            }
            $y += $h;
            if (isset($return[$order[$key]])) {
                $x = $return[$order[$key]]['x'];
                $y = $return[$order[$key]]['y'];
            }
            $data[$key] = $val;
        }

        $data['total'] = $total;
        $cell = $origin;
        $cell['x'] = $x;
        $cell['y'] = $y;
        $cell['name'] = 'total';
        $ary[] = $cell;

        $this->pdf->draw($ary, $data);
        $this->pdf->draw($lab, $label);

        return $total;
    }

    private function drawCreditor($year, $last_year = null)
    {
        $data = ['dummy' => null];
        $ary = [];
        $total = 0;

        $start = "$year-01-01 00:00:00";
        $end = "$year-12-31 23:59:59";

        $sql = "SELECT item_code_left AS code,
                       SUM(amount_left) AS amount
                  FROM `table::transfer`
                 WHERE category = 'A'
                   AND (issue_date >= ? AND issue_date <= ?)
                 GROUP BY item_code_left";
        if (false === $this->db->query($sql, [$start, $end])) {
            return false;
        }

        $products = $this->filter_items['PRODUCTS'];
        $purchase = $this->filter_items['PURCHASE'];
        $beginning_inventory = $this->filter_items['BEGINNING_INVENTORY'];
        $periodend_inventory = $this->filter_items['PERIODEND_INVENTORY'];
        $investments = $this->filter_items['INVESTMENTS'];
        $lost_fixedasset = $this->filter_items['LOST_FIXEDASSET'];
        $lumpsum_depreciable_assets = $this->filter_items['LUMPSUM_DEPRECIABLE_ASSETS'];

        while ($unit = $this->db->fetch()) {
            if (isset($this->financial_converter[$unit['code']])) {
                $unit['code'] = $this->financial_converter[$unit['code']];
            }
            if ($unit['code'] == $products) {
                continue;
            }
            if ($unit['code'] == $purchase) {
                $unit['code'] = $periodend_inventory;
            }
            if (!isset($data[$unit['code']])) {
                $data[$unit['code']] = 0;
            }
            $data[$unit['code']] += $unit['amount'];
            $total += $unit['amount'];
        }
        if (!is_null($last_year)) {
            $start = "{$last_year}-01-01 00:00:00";
            $end = "{$last_year}-12-31 23:59:59";
            $sql = 'SELECT item_code_left AS code,
                           SUM(amount_left) AS amount
                      FROM `table::transfer`
                     WHERE item_code_left IN (?, ?, ?)
                       AND (issue_date >= ? AND issue_date <= ?)
                     GROUP BY item_code_left';
            if (false === $this->db->query($sql, [$investments, $periodend_inventory, $lost_fixedasset, $start, $end])) {
                return false;
            }
            while ($unit = $this->db->fetch()) {
                if (!isset($data[$unit['code']])) {
                    $data[$unit['code']] = 0;
                }
                $data[$unit['code']] += $unit['amount'];
                $total += $unit['amount'];
            }

            $lost = (isset($data[$lost_fixedasset])) ? (int)$data[$lost_fixedasset] : 0;
            $data[$investments] = (int)$data[$investments] + $lost;
            $this->investments = $data[$investments];
        }

        $origin = $this->pdfmap['page4']['creditor']['item'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 52.4,
            'y' => 38.6,
            'type' => 'Cell',
            'width' => 30,
            'height' => 6.34,
            'align' => 'R',
            'flg' => true
        ];
        $h = $origin['height'];
        $y = $origin['y'];
        if (!is_null($last_year)) {
            $origin['x'] += $origin['width'];
        }

        $keys = $this->oas_config->creditor_keys;

        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = number_format($data[$key]);
            }
            $cell = $origin;
            $cell['y'] = $y;
            $cell['name'] = $key;
            $ary[] = $cell;

            $label_key = "{$key}_label";
            if (isset($this->pdfmap['page4']['creditor']['others'][$key])) {
                $cell = $this->pdfmap['page4']['creditor']['others'][$key];
                $cell['y'] = $y;
                $cell['name'] = $label_key;
                $ary[] = $cell;
                $data[$label_key] = $this->db->get(
                    'item_name',
                    'account_items',
                    'item_code = ?',
                    [$key]
                );
            }

            $y += $h;
        }
        $data['total'] = number_format($total);
        $cell = $origin;
        $cell['y'] = $y;
        $cell['name'] = 'total';
        $ary[] = $cell;

        $this->pdf->draw($ary, $data);

        return true;
    }

    private function drawDebit($year, $last_year = null)
    {
        $total = 0;
        $ary = [];
        $data = ['dummy' => null];

        $start = "{$year}-01-01 00:00:00";
        $end = "{$year}-12-31 23:59:59";
        $sql = "SELECT item_code_right AS code,
                       SUM(amount_right) AS amount
                  FROM `table::transfer`
                 WHERE category = 'A' AND item_code_right <> ?
                   AND (issue_date >= ? AND issue_date <= ?)
                 GROUP BY item_code_right";

        $deposit = $this->filter_items['DEPOSIT'];
        $withdrawals = $this->filter_items['WITHDRAWALS'];
        $gain_on_sale_of_fixedassets = $this->filter_items['GAIN_ON_SALE_OF_FIXEDASSETS'];

        if (false === $this->db->query($sql, [$this->filter_items['BEGINNING_INVENTORY'], $start, $end])) {
            return false;
        }
        while ($unit = $this->db->fetch()) {
            if (!isset($data[$unit['code']])) {
                $data[$unit['code']] = 0;
            }

            if (!is_null($last_year) && $unit['code'] === $deposit) {
                $unit['amount'] = $this->deposit;
            }

            $data[$unit['code']] += $unit['amount'];

            $total += $unit['amount'];
        }
        if (!is_null($last_year)) {
            $start = "{$last_year}-01-01 00:00:00";
            $end = "{$last_year}-12-31 23:59:59";
            $sql = 'SELECT item_code_right AS code,
                           SUM(amount_right) AS amount
                      FROM `table::transfer`
                     WHERE item_code_right IN (?, ?)
                       AND (issue_date >= ? AND issue_date <= ?)
                     GROUP BY item_code_right';
            if (false === $this->db->query($sql, [$withdrawals, $gain_on_sale_of_fixedassets, $start, $end])) {
                return false;
            }
            while ($unit = $this->db->fetch()) {
                if ($unit['code'] === $gain_on_sale_of_fixedassets) {
                    $unit['code'] = $withdrawals;
                }
                if (!isset($data[$unit['code']])) {
                    $data[$unit['code']] = 0;
                }

                if ($unit['code'] === $deposit) {
                    $data[$unit['code']] += $this->deposit;
                    continue;
                }

                $data[$unit['code']] += $unit['amount'];
                $total += $unit['amount'];
            }
            $data['no33'] = $this->column33;
            $data['no43'] = $this->column43;
            $total += $data['no43'];
            //$data[$deposit] = $this->deposit;
            //$total += $data[$deposit];
            //
            $this->withdrawals = (isset($data[$withdrawals])) ? (int)$data[$withdrawals] : 0;
        //$this->deposit = $kari + (int)$data[$deposit] + (int)$data['no33'] - $this->investments;
        } else {
            $data['no43'] = null;
            $this->deposit = $data[$deposit];
        }

        $origin = $this->pdfmap['page4']['debit']['item'] ?? [
            'font' => $this->mono,
            'style' => '',
            'size' => 10,
            'color' => self::TEXT_COLOR,
            'prefix' => '',
            'name' => null,
            'suffix' => '',
            'x' => 52.4,
            'y' => 38.6,
            'type' => 'Cell',
            'width' => 30,
            'height' => 6.34,
            'align' => 'R',
            'flg' => true
        ];
        $h = $origin['height'];
        $y = $origin['y'];
        if (!is_null($last_year)) {
            $origin['x'] += $origin['width'];
        }

        $keys = $this->oas_config->debit_keys;

        foreach ($keys as $key) {
            // Add 2024.02.10
            if ($key === 'consumption_tax' && !is_null($last_year)) {
                $sql = 'SELECT item_code_right AS code,
                               SUM(amount_right) AS amount
                          FROM `table::transfer`
                         WHERE item_code_right = ?
                           AND (issue_date >= ? AND issue_date <= ?)
                         GROUP BY item_code_right';
                if (false === $this->db->query($sql, [$this->filter_items['TAX_RECEIPT'], $start, $end])) {
                    return false;
                }
                $unit = $this->db->fetch();
                $receipt = $unit['amount'];
                $sql = 'SELECT item_code_left AS code,
                               SUM(amount_left) AS amount
                          FROM `table::transfer`
                         WHERE item_code_left = ?
                           AND (issue_date >= ? AND issue_date <= ?)
                         GROUP BY item_code_left';
                if (false === $this->db->query($sql, [$this->filter_items['TAX_PAYMENT'], $start, $end])) {
                    return false;
                }
                $unit = $this->db->fetch();
                $payment = $unit['amount'];
                if ($receipt > $payment) {
                    $data[$key] = $receipt - $payment;
                    $total += $data[$key];
                }
            }

            if (isset($data[$key])) {
                $data[$key] = number_format($data[$key]);
            }
            $cell = $origin;
            $cell['y'] = $y;
            $cell['name'] = $key;
            $ary[] = $cell;

            $label_key = "{$key}_label";
            if (isset($this->pdfmap['page4']['debit']['others'][$key])) {
                $cell = $this->pdfmap['page4']['debit']['others'][$key];
                $cell['y'] = $y;
                $cell['name'] = $label_key;
                $ary[] = $cell;
                $data[$label_key] = $this->db->get(
                    'item_name',
                    'account_items',
                    'item_code = ?',
                    [$key]
                );
            } elseif ($key === 'consumption_tax' && !empty($data[$key])) {
                $cell = $origin;
                $cell['font'] = $this->mincho;
                $cell['x'] = $cell['x'] - $origin['width'] * 2;
                $cell['y'] = $y;
                $cell['name'] = $label_key;
                $cell['align'] = 'C';
                $cell['size'] = 9;
                $ary[] = $cell;
                $data[$label_key] = $this->oas_config->debit_consumption_tax_label;
            }

            $y += $h;
        }
        $data['total'] = number_format($total);
        $cell = $origin;
        $cell['y'] = $y;
        $cell['name'] = 'total';
        $ary[] = $cell;

        $this->pdf->draw($ary, $data);

        return true;
    }

    private function drawDeduction(): void
    {
        $data = [
            'no06' => 0,
            'no07' => 0,
            'no08' => 0,
            'no09' => 0,
            'no08a' => 0,
            'no09a' => 0
        ];

        $data['no09'] = number_format(min($this->oas_config->blue_return_deduction - (int)$data['no08'], $this->column43));
        $data['no07'] = number_format($this->column43);

        $ary = $this->pdfmap['page2']['deduction'] ?? [
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no06', 'suffix' => '', 'x' => 230, 'y' => 158.0, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no07', 'suffix' => '', 'x' => 230, 'y' => 164.0, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no08', 'suffix' => '', 'x' => 230, 'y' => 170.3, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no09', 'suffix' => '', 'x' => 230, 'y' => 176.6, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no08a', 'suffix' => '', 'x' => 230, 'y' => 183.0, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
            ['font' => $this->mono, 'style' => '', 'size' => 10, 'color' => self::TEXT_COLOR, 'prefix' => '', 'name' => 'no09a', 'suffix' => '', 'x' => 230, 'y' => 189.3, 'type' => 'Cell', 'width' => 44.7, 'height' => 6.16, 'align' => 'R', 'flg' => true],
        ];

        $this->pdf->draw($ary, $data);
    }
}
