<!-- resources/views/prints/prints_index.blade.php -->

<x-app-layout>
  @php
    $page_header_title = '印刷メニュー';
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('prints.index')"
  />

  <br>

  <h3>はり・きゅう関連</h3>
  <button type="button" class="btn btn-primary" onclick="openAcupunctureBenefitModal()">療養費支給申請書</button>
  <button>施術料金領収書</button>
  <button>医療助成費支給申請書</button>
  <button>後期高齢者医療療養費支給申請書</button>
  <button>同意書依頼状 (サンプル版)</button>
  <button>同意書依頼状 (医師指定)</button>
  <button>同意書</button>
  <button>施術料金一覧表(保険)</button>
  <button>施術料金一覧表(自費)</button>
  <button>施術録</button>
  <button>総括表</button>
  <br><br>

  <h3>あんま・マッサージ関連</h3>
  <button>療養費支給申請書</button>
  <button>施術料金領収書</button>
  <button>医療助成費支給申請書</button>
  <button>後期高齢者医療療養費支給申請書</button>
  <button>同意書依頼状 (サンプル版)</button>
  <button>同意書依頼状 (医師指定)</button>
  <button>同意書</button>
  <button>施術料金一覧表(保険)</button>
  <button>施術料金一覧表(自費)</button>
  <button>施術録</button>
  <button>総括表</button>
  <br><br>

  <h3>その他１</h3>
  <button>初回体験用資料</button>
  <button>委任状（申請・受領）</button>
  <button>委任状（同意書取得）</button>
  <button>入金管理票（保険）</button>
  <button>医師への御礼状</button>
  <button>紹介者への御礼状</button>
  <button>利用者数集計表</button>
  <button>実施計画書</button>
  <button>報告書挨拶文</button>
  <button>報告書</button>
  <button>翌月予定表</button>
  <button>要加療期限切れリスト</button>
  <br><br>

  <h3>その他２</h3>
  <button>利用者情報一覧（基本情報）</button>
  <button>利用者情報一覧（医療保険情報）</button>
  <button>利用者情報一覧（同意医師情報）</button>
  <button>医師情報一覧</button>
  <button>ケアマネ情報一覧</button>
  <button>施術者情報一覧</button>
  <button>宛名シール・住所データCSV出力</button>
  <button>FAX送信表表示</button>

  <!-- はり・きゅう療養費支給申請書モーダル -->
  <div class="modal fade" id="acupunctureBenefitModal" tabindex="-1" aria-labelledby="acupunctureBenefitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="acupunctureBenefitModalLabel">はり・きゅう療養費支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="acupunctureBenefitForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $display = $date->format('Y年m月');
                    echo "<option value=\"{$value}\">{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <label for="clinic_user_ids" class="form-label">利用者 <span class="text-danger">*</span></label>
              <select class="form-select" id="clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">
                    {{ $user->last_name }} {{ $user->first_name }} ({{ $user->last_kana }} {{ $user->first_kana }})
                  </option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（Ctrlキー + クリック）</div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitAcupunctureBenefit()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    @push('styles')
      <style>
        /* ensure modals sit above page content */
        .modal { z-index: 2000; }
        .modal-backdrop { z-index: 1990; }
        .modal .modal-content { background-color: #fff; }
      </style>
    @endpush
    <script src="{{ asset('js/prints.js') }}"></script>
  @endpush
</x-app-layout>
