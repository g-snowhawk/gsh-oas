<?php

/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Gsnowhawk\Oas\Lang;

/**
 * Japanese Languages for Gsnowhawk.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Ja extends \Gsnowhawk\Common\Lang
{
    public const APP_NAME = 'OAS';
    public const ALT_NAME = '会計管理';

    public const APPLICATION_NAME = self::APP_NAME;
    public const APPLICATION_LABEL = self::ALT_NAME;
    public const APP_DETAIL = self::ALT_NAME.'機能を提供します。';
    public const SUCCESS_SETUP = self::ALT_NAME.'機能の追加に成功しました。';
    public const FAILED_SETUP = self::ALT_NAME.'機能の追加に失敗しました。';

    public const LABEL_CASH = '現金';
    public const YEN = '円';

    public const LIST_HEADER_1 = 'No';
    public const LIST_HEADER_2 = '勘定科目';
    public const LIST_HEADER_3 = '摘要';
    public const LIST_HEADER_4 = '支払期限';
    public const LIST_HEADER_5 = '編<br />集<br />';

    public const EDIT = '編集';

    public const DATE_FORMAT = 'Y年m月d日';
    public const DATE_FORMAT_N = '%4d年%2d月%2d日';
    public const DATE_FORMAT_S = '昭和%3d年%3d月%3d日';
    public const DATE_FORMAT_H = '平成%3d年%3d月%3d日';
    public const DATE_FORMAT_R = '令和%3d年%3d月%3d日';

    public const BRACKETS_LEFT = '（';
    public const BRACKETS_RIGHT = '）';
    public const IDENTICAL = '〃';
    public const DEBT = '借';
    public const LOAN = '貸';
    public const COMMA = '、';

    public const TO_NEXT_PAGE = '次頁へ繰越';
    public const FROM_PRIV_PAGE = '前頁より繰越';

    public const SHOKUCHI = '諸口';
    public const TB_TOTAL = '合計';

    public const LG_MONTH = '月計';
    public const LG_TOTAL = '累計';
    public const LG_NEXT = '次月繰越';
    public const LG_PREV = '前月繰越';
    public const LG_NEXT_STAGE = '次期繰越';
    public const LG_THIS_STAGE = '当期残高';

    public const FROM_PRIV_YEAR = '前年より繰越';
    public const DEPRECIATION = '本年分減価償却費';
    public const ACQUIRE = '新規購入';

    public const KOKUMINNENKIN = '国民年金';
    public const SHOKIBOKYOSAI = '小規模企業共済';

    public const SOLD = '売却';
    public const SALE_PRICE = '売却額';
    public const LOSS_ON_SALE = '売却損';
    public const GAIN_ON_SALE = '売却益';
    public const RETIREMENT = '除却';

    public const CREATE_DOCUMENT = '新規作成';
}
