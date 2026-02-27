<?php

namespace App\Services\Print;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;

/**
 * 利用者情報一覧（基本情報）PDF生成サービス
 *
 * レイアウト概要：
 * - A4縦 (210mm × 297mm)、左右マージン 8mm → 利用可能幅 194mm
 * - 第1カラム（縦書きラベル）：8mm
 * - 第2カラム（行ラベル）：22mm
 * - ヘッダー合計：30mm
 * - データカラム幅：22mm（最大11文字）
 * - 1ページあたりのデータカラム数：floor((194 - 30) / 22) = 7
 * - 行構成：34行（1〜16：基本情報、17〜25：はり・きゅう、26〜34：あんま・マッサージ）
 * - 各行高は最大文字数11を超えた場合に行が増加する（可変高）
 */
class UserInfoBasicListPdfService extends BasePdfService
{
  // レイアウト定数
  const MARGIN_X       = 8;    // 左右マージン mm
  const AVAILABLE_W    = 194;  // 利用可能幅 mm
  const COL1_W         = 8;    // 第1カラム（縦書きラベル）幅
  const COL2_W         = 22;   // 第2カラム（行ラベル）幅
  const HEADER_W       = 30;   // COL1 + COL2
  const DATA_COL_W     = 22;   // データカラム幅
  const MAX_COLS_PER_PAGE = 7; // 1ページのデータカラム数 floor((194-30)/22)
  const MAX_CHARS      = 11;   // 1行あたりの最大文字数
  const BASE_ROW_H     = 6;    // 行の基本高さ mm（1行分）
  const FONT_SIZE      = 7;    // データフォント
  const HEADER_FONT    = 7;    // ヘッダーフォント

  // ページ座標
  const START_Y_PAGE1  = 30;   // 1ページ目の開始Y（タイトル分）
  const START_Y_OTHER  = 12;   // 2ページ目以降の開始Y
  const BOTTOM_MARGIN  = 285;  // 下マージン

  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/user_info_basic_list_coordinates.json');
  }

  protected function getDefaultCoordinates(): array
  {
    return [];
  }

  /**
   * PDF生成
   *
   * @param array  $clinicUserIds 未使用（BasePdfServiceシグネチャ互換）
   * @param string $serviceYearMonth 未使用
   * @param string $submissionDate  PDF出力日（Y-m-d形式）
   * @return string PDFバイナリ
   */
  public function generate(array $clinicUserIds, string $serviceYearMonth, string $submissionDate = '', string $remarks = ''): string
  {
    $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTextColor(0, 0, 0);

    $outputDate = $submissionDate ?: date('Y-m-d');
    $users      = $this->fetchUsers();
    $rowDefs    = $this->getRowDefinitions();

    // 各行の最大行数を事前計算（全利用者のデータを一括処理）
    // rowHeights[rowIndex] = その行の基本行高 mm
    // （データカラムは全利用者で同じ行高なので、各rowDef単位でmax行数を算出）
    $rowHeights = $this->calcRowHeights($rowDefs, $users);

    // ページ分割：1ページあたり MAX_COLS_PER_PAGE 人ずつ
    $chunks     = array_chunk($users, self::MAX_COLS_PER_PAGE);

    $isFirstPage = true;
    foreach ($chunks as $chunk) {
      $pdf->AddPage();
      $startY = $isFirstPage ? self::START_Y_PAGE1 : self::START_Y_OTHER;

      if ($isFirstPage) {
        $this->drawTitleAndDate($pdf, $outputDate);
      }

      $this->drawTable($pdf, $rowDefs, $rowHeights, $chunk, $startY);
      $isFirstPage = false;
    }

    return $pdf->Output('', 'S');
  }

  /**
   * 全利用者データを取得
   */
  protected function fetchUsers(): array
  {
    // 基本情報
    $users = DB::table('clinic_users')
      ->leftJoin('gender', 'clinic_users.gender_id', '=', 'gender.id')
      ->select('clinic_users.*', 'gender.gender as gender_label')
      ->orderBy('clinic_users.id')
      ->get();

    // はり・きゅう同意（最新）
    $acu = DB::table('consents_acupuncture as ca')
      ->leftJoin('doctors as d', 'd.id', '=', 'ca.consenting_doctor_id')
      ->leftJoin('bill_categories as bc', 'bc.id', '=', 'ca.bill_category_id')
      ->leftJoin('outcomes as oc', 'oc.id', '=', 'ca.outcome_id')
      ->leftJoin('work_scope_types as ws', 'ws.id', '=', 'ca.work_scope_type_id')
      ->leftJoin('therapy_contents as tc', 'tc.id', '=', 'ca.first_therapy_content_id')
      ->whereRaw('ca.id = (SELECT MAX(id) FROM consents_acupuncture WHERE clinic_user_id = ca.clinic_user_id)')
      ->select(
        'ca.clinic_user_id',
        'ca.first_care_date',
        'ca.onset_and_injury_date',
        'ws.work_scope_type',
        'ca.reconsenting_expiry',
        'tc.therapy_content as first_therapy_content',
        'd.last_name as doc_last',
        'd.first_name as doc_first',
        'ca.consenting_date',
        'bc.bill_category',
        'oc.outcome'
      )
      ->get()
      ->keyBy('clinic_user_id');

    // はり・きゅう同意（最古 = 初回）
    $acuFirst = DB::table('consents_acupuncture as ca')
      ->leftJoin('doctors as d', 'd.id', '=', 'ca.consenting_doctor_id')
      ->whereRaw('ca.id = (SELECT MIN(id) FROM consents_acupuncture WHERE clinic_user_id = ca.clinic_user_id)')
      ->select(
        'ca.clinic_user_id',
        'd.last_name as doc_last',
        'd.first_name as doc_first',
        'ca.consenting_date as first_consenting_date'
      )
      ->get()
      ->keyBy('clinic_user_id');

    // あんま・マッサージ同意（最新）
    $mas = DB::table('consents_massage as cm')
      ->leftJoin('doctors as d', 'd.id', '=', 'cm.consenting_doctor_id')
      ->leftJoin('bill_categories as bc', 'bc.id', '=', 'cm.bill_category_id')
      ->leftJoin('outcomes as oc', 'oc.id', '=', 'cm.outcome_id')
      ->leftJoin('work_scope_types as ws', 'ws.id', '=', 'cm.work_scope_type_id')
      ->leftJoin('therapy_contents as tc', 'tc.id', '=', 'cm.first_therapy_content_id')
      ->whereRaw('cm.id = (SELECT MAX(id) FROM consents_massage WHERE clinic_user_id = cm.clinic_user_id)')
      ->select(
        'cm.clinic_user_id',
        'cm.first_care_date',
        'cm.onset_and_injury_date',
        'ws.work_scope_type',
        'cm.reconsenting_expiry',
        'tc.therapy_content as first_therapy_content',
        'd.last_name as doc_last',
        'd.first_name as doc_first',
        'cm.consenting_date',
        'bc.bill_category',
        'oc.outcome'
      )
      ->get()
      ->keyBy('clinic_user_id');

    // あんま・マッサージ同意（最古 = 初回）
    $masFirst = DB::table('consents_massage as cm')
      ->leftJoin('doctors as d', 'd.id', '=', 'cm.consenting_doctor_id')
      ->whereRaw('cm.id = (SELECT MIN(id) FROM consents_massage WHERE clinic_user_id = cm.clinic_user_id)')
      ->select(
        'cm.clinic_user_id',
        'd.last_name as doc_last',
        'd.first_name as doc_first',
        'cm.consenting_date as first_consenting_date'
      )
      ->get()
      ->keyBy('clinic_user_id');

    $result = [];
    foreach ($users as $u) {
      $uid = $u->id;
      $a   = $acu[$uid]   ?? null;
      $af  = $acuFirst[$uid] ?? null;
      $m   = $mas[$uid]   ?? null;
      $mf  = $masFirst[$uid] ?? null;

      // 請求区分・転帰：はり or マッサージどちらか（優先：はり）
      $billCategory = $a->bill_category ?? $m->bill_category ?? '';
      $outcome      = $a->outcome       ?? $m->outcome       ?? '';

      // 住所結合
      $address = trim(($u->address_1 ?? '') . ($u->address_2 ?? '') . ($u->address_3 ?? ''));

      // 往療加算距離
      $hd      = (int)($u->housecall_distance ?? 0);
      $addDist = max(0, $hd - 4);

      $result[] = [
        'id'           => $uid,
        'name'         => $u->last_name . '  ' . $u->first_name,
        'kana'         => $u->last_kana . '  ' . $u->first_kana,
        'birthday'     => $this->formatJapaneseDate($u->birthday),
        'age'          => ($u->age !== null ? $u->age . '歳' : ''),
        'gender'       => $u->gender_label ?? '',
        'postal_code'  => $this->formatPostalCode($u->postal_code ?? ''),
        'address'      => $address,
        'phone'        => $this->formatPhoneNumber($u->phone ?? ''),
        'cell_phone'   => $this->formatPhoneNumber($u->cell_phone ?? ''),
        'fax'          => $this->formatPhoneNumber($u->fax ?? ''),
        'email'        => $u->email ?? '',
        'housecall_distance' => ($hd > 0 ? $hd . 'km' : ''),
        'housecall_add_dist' => $addDist . 'km',
        'bill_category'      => $billCategory,
        'outcome'            => $outcome,
        // はり・きゅう
        'acu_first_care_date'     => $this->formatJapaneseDate($a->first_care_date ?? null),
        'acu_onset_date'          => $this->formatJapaneseDate($a->onset_and_injury_date ?? null),
        'acu_work_scope_type'     => $a->work_scope_type ?? '',
        'acu_reconsenting_expiry' => $this->formatJapaneseDate($a->reconsenting_expiry ?? null),
        'acu_first_therapy'       => $a->first_therapy_content ?? '',
        'acu_first_doc'           => $af ? ($af->doc_last . '  ' . $af->doc_first) : '',
        'acu_first_consent_date'  => $this->formatJapaneseDate($af->first_consenting_date ?? null),
        'acu_doc'                 => $a ? ($a->doc_last . '  ' . $a->doc_first) : '',
        'acu_consent_date'        => $this->formatJapaneseDate($a->consenting_date ?? null),
        // あんま・マッサージ
        'mas_first_care_date'     => $this->formatJapaneseDate($m->first_care_date ?? null),
        'mas_onset_date'          => $this->formatJapaneseDate($m->onset_and_injury_date ?? null),
        'mas_work_scope_type'     => $m->work_scope_type ?? '',
        'mas_reconsenting_expiry' => $this->formatJapaneseDate($m->reconsenting_expiry ?? null),
        'mas_first_therapy'       => $m->first_therapy_content ?? '',
        'mas_first_doc'           => $mf ? ($mf->doc_last . '  ' . $mf->doc_first) : '',
        'mas_first_consent_date'  => $this->formatJapaneseDate($mf->first_consenting_date ?? null),
        'mas_doc'                 => $m ? ($m->doc_last . '  ' . $m->doc_first) : '',
        'mas_consent_date'        => $this->formatJapaneseDate($m->consenting_date ?? null),
      ];
    }

    return $result;
  }

  /**
   * 行定義を返す
   * [col1Label, col2Label, dataKey, section]
   * section: 'basic'|'acu'|'mas'
   */
  protected function getRowDefinitions(): array
  {
    return [
      // 行1〜16：基本情報（col1は空、col1とcol2を結合）
      ['', '利用者ID',       'id',                 'basic'],
      ['', '利用者氏名',     'name',               'basic'],
      ['', 'フリガナ',       'kana',               'basic'],
      ['', '生年月日',       'birthday',           'basic'],
      ['', '年齢',           'age',                'basic'],
      ['', '性別',           'gender',             'basic'],
      ['', '郵便番号',       'postal_code',        'basic'],
      ['', '住所',           'address',            'basic'],
      ['', '電話番号',       'phone',              'basic'],
      ['', '携帯番号',       'cell_phone',         'basic'],
      ['', 'FAX番号',        'fax',                'basic'],
      ['', 'メール',         'email',              'basic'],
      ['', '往療距離',       'housecall_distance', 'basic'],
      ['', '往療加算距離',   'housecall_add_dist', 'basic'],
      ['', '請求区分',       'bill_category',      'basic'],
      ['', '転帰',           'outcome',            'basic'],
      // 行17〜25：はり・きゅう（col1='はり・きゅう'、縦書き）
      ['はり・きゅう', '初療年月日',       'acu_first_care_date',     'acu'],
      ['はり・きゅう', '発病負傷年月日',   'acu_onset_date',          'acu'],
      ['はり・きゅう', '業務上外等区分',   'acu_work_scope_type',     'acu'],
      ['はり・きゅう', '再同意有効期限',   'acu_reconsenting_expiry', 'acu'],
      ['はり・きゅう', '初回施術内容',     'acu_first_therapy',       'acu'],
      ['はり・きゅう', '初回同意医師名',   'acu_first_doc',           'acu'],
      ['はり・きゅう', '初回同意年月日',   'acu_first_consent_date',  'acu'],
      ['はり・きゅう', '同意医師名',       'acu_doc',                 'acu'],
      ['はり・きゅう', '同意年月日',       'acu_consent_date',        'acu'],
      // 行26〜34：あんま・マッサージ（col1='あんま・マッサージ'、縦書き）
      ['あんま・マッサージ', '初療年月日',       'mas_first_care_date',     'mas'],
      ['あんま・マッサージ', '発病負傷年月日',   'mas_onset_date',          'mas'],
      ['あんま・マッサージ', '業務上外等区分',   'mas_work_scope_type',     'mas'],
      ['あんま・マッサージ', '再同意有効期限',   'mas_reconsenting_expiry', 'mas'],
      ['あんま・マッサージ', '初回施術内容',     'mas_first_therapy',       'mas'],
      ['あんま・マッサージ', '初回同意医師名',   'mas_first_doc',           'mas'],
      ['あんま・マッサージ', '初回同意年月日',   'mas_first_consent_date',  'mas'],
      ['あんま・マッサージ', '同意医師名',       'mas_doc',                 'mas'],
      ['あんま・マッサージ', '同意年月日',       'mas_consent_date',        'mas'],
    ];
  }

  /**
   * 各行の描画高さを計算（全利用者で共通の高さを使用）
   * 各行について全データカラムの最大行数を求め、それに BASE_ROW_H を掛けた値を返す
   *
   * @return float[] 行高の配列（rowDefs のインデックスに対応）
   */
  protected function calcRowHeights(array $rowDefs, array $users): array
  {
    $heights = [];
    foreach ($rowDefs as $i => $row) {
      $dataKey = $row[2];
      $maxLines = 1;
      foreach ($users as $u) {
        $text  = (string)($u[$dataKey] ?? '');
        $lines = $this->countLines($text);
        if ($lines > $maxLines) {
          $maxLines = $lines;
        }
      }
      $heights[$i] = $maxLines * self::BASE_ROW_H;
    }
    return $heights;
  }

  /**
   * テキストが何行になるかを計算
   */
  protected function countLines(string $text): int
  {
    if ($text === '') {
      return 1;
    }
    $len = mb_strlen($text);
    return (int)ceil($len / self::MAX_CHARS);
  }

  /**
   * テキストを MAX_CHARS で折り返した行配列を返す
   */
  protected function wrapText(string $text): array
  {
    if ($text === '') {
      return [''];
    }
    $lines  = [];
    $chars  = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $line   = '';
    foreach ($chars as $ch) {
      if (mb_strlen($line) >= self::MAX_CHARS) {
        $lines[] = $line;
        $line    = $ch;
      } else {
        $line .= $ch;
      }
    }
    if ($line !== '') {
      $lines[] = $line;
    }
    return $lines;
  }

  /**
   * タイトルと出力日を描画（1ページ目のみ）
   */
  protected function drawTitleAndDate(Fpdi $pdf, string $outputDate): void
  {
    $x = self::MARGIN_X;

    // タイトル（左上）
    $pdf->SetFont('kozgopromedium', '', 15);
    $pdf->Text($x, 13, '利用者情報一覧（基本情報）');

    // PDF出力日（右上）
    $dateStr  = 'PDF出力日：' . $this->formatJapaneseDate($outputDate);
    $pdf->SetFont('kozgopromedium', '', 10);
    $dateW    = $pdf->GetStringWidth($dateStr);
    $pdf->Text($x + self::AVAILABLE_W - $dateW, 13, $dateStr);
  }

  /**
   * テーブルを描画（1チャンク分）
   *
   * @param Fpdi    $pdf
   * @param array   $rowDefs    行定義
   * @param float[] $rowHeights 各行の高さ
   * @param array   $users      このページに表示する利用者データ（最大 MAX_COLS_PER_PAGE 件）
   * @param float   $startY     描画開始Y座標
   */
  protected function drawTable(Fpdi $pdf, array $rowDefs, array $rowHeights, array $users, float $startY): void
  {
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $startX    = self::MARGIN_X;
    $totalRows = count($rowDefs);

    // セクション区間を把握（縦結合用）
    // section_ranges: ['basic' => [0,15], 'acu' => [16,24], 'mas' => [25,33]]
    $sectionRanges = $this->getSectionRanges($rowDefs);

    // 各行のY座標を事前計算
    $rowYs = [];
    $y     = $startY;
    foreach ($rowDefs as $i => $row) {
      $rowYs[$i] = $y;
      $y        += $rowHeights[$i];
    }
    $tableBottom = $y;
    $tableHeight = $tableBottom - $startY;

    // セクション別の縦結合高さを計算
    $sectionHeights = [];
    foreach ($sectionRanges as $sec => [$secStart, $secEnd]) {
      $h = 0;
      for ($i = $secStart; $i <= $secEnd; $i++) {
        $h += $rowHeights[$i];
      }
      $sectionHeights[$sec] = $h;
    }

    // ---- 行を描画 ----
    $col1X = $startX;
    $col2X = $startX + self::COL1_W;
    $dataStartX = $startX + self::HEADER_W;

    // 各行のセル（第2カラム + データカラム）
    foreach ($rowDefs as $i => $rowDef) {
      [, $col2Label, $dataKey, $section] = $rowDef;
      $rowY = $rowYs[$i];
      $rowH = $rowHeights[$i];

      // basicセクション：第1カラムと第2カラムを横結合（幅 HEADER_W = 30mm）
      // acu/masセクション：第2カラムのみ（幅 COL2_W = 22mm）、第1カラムは後でセクション縦結合
      if ($section === 'basic') {
        $this->drawCell($pdf, $col1X, $rowY, self::HEADER_W, $rowH, $col2Label, true, 'C');
      } else {
        $this->drawCell($pdf, $col2X, $rowY, self::COL2_W, $rowH, $col2Label, true, 'C');
      }

      // データカラム
      foreach ($users as $j => $user) {
        $cellX  = $dataStartX + $j * self::DATA_COL_W;
        $text   = (string)($user[$dataKey] ?? '');
        $this->drawCell($pdf, $cellX, $rowY, self::DATA_COL_W, $rowH, $text, false, 'L');
      }
    }

    // ---- 第1カラムを描画（acu/masセクションのみ縦結合） ----
    foreach ($sectionRanges as $sec => [$secStart, $secEnd]) {
      if ($sec === 'basic') {
        continue; // basicは横結合済みのためスキップ
      }
      $secY = $rowYs[$secStart];
      $secH = $sectionHeights[$sec];
      $label = ($sec === 'acu') ? 'はり・きゅう' : 'あんま・マッサージ';

      // セル背景
      $pdf->SetFillColor(230, 230, 230);
      $pdf->Rect($col1X, $secY, self::COL1_W, $secH, 'FD');

      // 縦書きテキスト
      $this->drawVerticalText($pdf, $col1X, $secY, self::COL1_W, $secH, $label);
    }

    // ---- 右端の縦線（データカラムの最後） ----
    $rightX = $dataStartX + count($users) * self::DATA_COL_W;
    $pdf->Line($rightX, $startY, $rightX, $tableBottom);

    // ---- テーブル全体の左端縦線 ----
    $pdf->Line($col1X, $startY, $col1X, $tableBottom);

    // ---- テーブル下端横線 ----
    $pdf->Line($col1X, $tableBottom, $rightX, $tableBottom);
  }

  /**
   * セルを描画（枠線＋テキスト）
   * テキストは MAX_CHARS で折り返し
   *
   * @param bool   $isHeader ヘッダー行（グレー背景）
   * @param string $align    'C' or 'L'
   */
  protected function drawCell(Fpdi $pdf, float $x, float $y, float $w, float $h, string $text, bool $isHeader, string $align): void
  {
    // 背景
    if ($isHeader) {
      $pdf->SetFillColor(230, 230, 230);
      $pdf->Rect($x, $y, $w, $h, 'F');
    }

    // 枠線
    $pdf->Line($x, $y, $x + $w, $y);          // 上
    $pdf->Line($x, $y, $x, $y + $h);          // 左
    $pdf->Line($x + $w, $y, $x + $w, $y + $h); // 右

    // テキスト描画
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);
    $lines      = $this->wrapText($text);
    $lineCount  = count($lines);
    $fontMm     = self::FONT_SIZE * 0.352; // pt → mm 近似換算

    if ($isHeader) {
      // ヘッダー：セル全体に対して水平・垂直ともに中央揃え
      $totalTextH = $lineCount * self::BASE_ROW_H;
      $offsetY    = ($h - $totalTextH) / 2;
      foreach ($lines as $li => $line) {
        $lineY = $y + $offsetY + $li * self::BASE_ROW_H + (self::BASE_ROW_H - $fontMm) / 2;
        $textW = $pdf->GetStringWidth($line);
        $lineX = $x + ($w - $textW) / 2;
        $pdf->Text($lineX, $lineY, $line);
      }
    } else {
      // データ：垂直は先頭から、水平は左揃え
      foreach ($lines as $li => $line) {
        $lineY = $y + $li * self::BASE_ROW_H + (self::BASE_ROW_H - $fontMm) / 2;
        $lineX = $x + 0.8;
        $pdf->Text($lineX, $lineY, $line);
      }
    }
  }

  /**
   * 縦書きテキストを描画（第1カラム用）
   */
  protected function drawVerticalText(Fpdi $pdf, float $x, float $y, float $w, float $h, string $text): void
  {
    $pdf->SetFont('kozgopromedium', '', self::FONT_SIZE);

    $chars    = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $count    = count($chars);
    // 各文字の高さ（フォントサイズ mm換算 + 間隔）
    $charH    = self::FONT_SIZE * 0.4;
    $gap      = 0.8;
    $totalH   = $count * ($charH + $gap) - $gap;

    // 垂直中央揃え
    $startCharY = $y + ($h - $totalH) / 2;

    foreach ($chars as $ci => $ch) {
      $charY  = $startCharY + $ci * ($charH + $gap);
      // 各文字の実幅でセル内水平中央を計算
      $charW  = $pdf->GetStringWidth($ch);
      $charX  = $x + ($w - $charW) / 2;
      $pdf->SetXY($charX, $charY);
      $pdf->Cell($charW, 0, $ch, 0, 0, 'L', false);
    }
  }

  /**
   * セクション区間を取得
   * ['basic' => [startIdx, endIdx], 'acu' => [...], 'mas' => [...]]
   */
  protected function getSectionRanges(array $rowDefs): array
  {
    $ranges = [];
    foreach ($rowDefs as $i => $row) {
      $sec = $row[3];
      if (!isset($ranges[$sec])) {
        $ranges[$sec] = [$i, $i];
      } else {
        $ranges[$sec][1] = $i;
      }
    }
    return $ranges;
  }

  /**
   * 郵便番号フォーマット（〒XXX-XXXX）
   */
  protected function formatPostalCode(string $postalCode): string
  {
    $nums = preg_replace('/[^0-9]/', '', $postalCode);
    if (strlen($nums) === 7) {
      return '〒' . substr($nums, 0, 3) . '-' . substr($nums, 3, 4);
    } elseif ($postalCode !== '') {
      return '〒' . $postalCode;
    }
    return '';
  }

  /**
   * 電話番号フォーマット
   */
  protected function formatPhoneNumber(string $phone): string
  {
    $nums = preg_replace('/[^0-9]/', '', $phone);
    if (empty($nums)) {
      return '';
    }
    if (strlen($nums) === 10) {
      if (substr($nums, 0, 2) === '03') {
        return substr($nums, 0, 2) . '-' . substr($nums, 2, 4) . '-' . substr($nums, 6);
      }
      return substr($nums, 0, 3) . '-' . substr($nums, 3, 3) . '-' . substr($nums, 6);
    }
    if (strlen($nums) === 11) {
      return substr($nums, 0, 3) . '-' . substr($nums, 3, 4) . '-' . substr($nums, 7);
    }
    return $phone;
  }

  /**
   * 日付を和暦フォーマットに変換
   */
  protected function formatJapaneseDate(?string $date): string
  {
    if (!$date) {
      return '';
    }
    $ts    = strtotime($date);
    $year  = (int)date('Y', $ts);
    $month = (int)date('n', $ts);
    $day   = (int)date('j', $ts);
    $era   = $this->getJapaneseEra($year, $month, $day);
    return $era['era'] . $era['year'] . '年 ' . $month . '月 ' . $day . '日';
  }

  /**
   * 和暦情報を取得
   */
  protected function getJapaneseEra(int $year, int $month, int $day): array
  {
    $date = sprintf('%04d%02d%02d', $year, $month, $day);
    if ($date >= '20190501') {
      return ['era' => '令和', 'year' => $year - 2018];
    } elseif ($date >= '19890108') {
      return ['era' => '平成', 'year' => $year - 1988];
    } elseif ($date >= '19261225') {
      return ['era' => '昭和', 'year' => $year - 1925];
    } elseif ($date >= '19120730') {
      return ['era' => '大正', 'year' => $year - 1911];
    }
    return ['era' => '明治', 'year' => $year - 1867];
  }
}
