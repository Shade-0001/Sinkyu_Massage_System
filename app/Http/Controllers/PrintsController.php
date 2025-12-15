<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Print\AcupunctureBenefitPdfService;
use Illuminate\Support\Facades\DB;

/**
 * 印刷メニューコントローラー
 */
class PrintsController extends Controller
{
  /**
   * 印刷メニューを表示
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    // 利用者一覧を取得してモーダル用に渡す
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('last_kana')
      ->get();

    return view('prints.prints_index', compact('clinicUsers'));
  }

  /**
   * はり・きゅう療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param AcupunctureBenefitPdfService $service
   * @return \Illuminate\Http\Response
   */
  public function acupunctureBenefit(Request $request, AcupunctureBenefitPdfService $service)
  {
    $validated = $request->validate([
      'clinic_user_ids' => 'required|array',
      'clinic_user_ids.*' => 'exists:clinic_users,id',
      'service_year_month' => 'required|date_format:Y-m',
      'submission_date' => 'required|date',
    ]);

    $pdfBinary = $service->generate(
      $validated['clinic_user_ids'],
      $validated['service_year_month'],
      $validated['submission_date']
    );

    $filename = 'acupuncture_benefit_' . $validated['service_year_month'] . '.pdf';

    return response($pdfBinary, 200, [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }
}
