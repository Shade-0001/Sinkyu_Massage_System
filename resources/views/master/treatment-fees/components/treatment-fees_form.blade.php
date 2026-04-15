{{-- resources/views/master/treatment-fees/components/treatment-fees_form.blade.php --}}

@csrf
@if(isset($item) && isset($item->id))
  @method('PUT')
@endif

<div class="d-flex flex-column gap-4 container ms-0">

  {{-- はり・きゅう --}}
  <div>
    <div class="form-label-tab">はり・きゅう</div>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th style="width: 40%;">施術項目</th>
            <th style="width: 30%;">初回料金</th>
            <th style="width: 30%;">通常料金</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>はり</td>
            <td><input type="number" name="hari_first" value="{{ old('hari_first', $item->hari_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="hari_normal" value="{{ old('hari_normal', $item->hari_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>きゅう</td>
            <td><input type="number" name="kyu_first" value="{{ old('kyu_first', $item->kyu_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="kyu_normal" value="{{ old('kyu_normal', $item->kyu_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>はりきゅう併用</td>
            <td><input type="number" name="hari_and_kyu_first" value="{{ old('hari_and_kyu_first', $item->hari_and_kyu_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="hari_and_kyu_normal" value="{{ old('hari_and_kyu_normal', $item->hari_and_kyu_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>電療料（電気針）</td>
            <td><input type="number" name="hari_and_elec_needle_first" value="{{ old('hari_and_elec_needle_first', $item->hari_and_elec_needle_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="hari_and_elec_needle_normal" value="{{ old('hari_and_elec_needle_normal', $item->hari_and_elec_needle_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>電療料（電気温灸器）</td>
            <td><input type="number" name="kyu_and_elec_moxa_heater_first" value="{{ old('kyu_and_elec_moxa_heater_first', $item->kyu_and_elec_moxa_heater_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="kyu_and_elec_moxa_heater_normal" value="{{ old('kyu_and_elec_moxa_heater_normal', $item->kyu_and_elec_moxa_heater_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>電療料（電気光線器具）</td>
            <td><input type="number" name="hari_and_kyu_elec_ray_first" value="{{ old('hari_and_kyu_elec_ray_first', $item->hari_and_kyu_elec_ray_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="hari_and_kyu_elec_ray_normal" value="{{ old('hari_and_kyu_elec_ray_normal', $item->hari_and_kyu_elec_ray_normal ?? '') }}" min="0" required></td>
          </tr>
        </tbody>
      </table>
      @error('kyu_normal')<div class="text-danger mt-1">{{ $message }}</div>@enderror
    </div>
  </div>

  {{-- あんま・マッサージ --}}
  <div>
    <div class="form-label-tab">あんま・マッサージ</div>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th style="width: 40%;">施術項目</th>
            <th style="width: 30%;">初回料金</th>
            <th style="width: 30%;">通常料金</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>マッサージ躯幹</td>
            <td><input type="number" name="massage_trunk_first" value="{{ old('massage_trunk_first', $item->massage_trunk_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="massage_trunk_normal" value="{{ old('massage_trunk_normal', $item->massage_trunk_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>マッサージ右上肢</td>
            <td><input type="number" name="massage_upper_limb_r_first" value="{{ old('massage_upper_limb_r_first', $item->massage_upper_limb_r_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="massage_upper_limb_r_normal" value="{{ old('massage_upper_limb_r_normal', $item->massage_upper_limb_r_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>マッサージ左上肢</td>
            <td><input type="number" name="massage_upper_limb_l_first" value="{{ old('massage_upper_limb_l_first', $item->massage_upper_limb_l_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="massage_upper_limb_l_normal" value="{{ old('massage_upper_limb_l_normal', $item->massage_upper_limb_l_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>マッサージ右下肢</td>
            <td><input type="number" name="massage_lower_limb_r_first" value="{{ old('massage_lower_limb_r_first', $item->massage_lower_limb_r_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="massage_lower_limb_r_normal" value="{{ old('massage_lower_limb_r_normal', $item->massage_lower_limb_r_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>マッサージ左下肢</td>
            <td><input type="number" name="massage_lower_limb_l_first" value="{{ old('massage_lower_limb_l_first', $item->massage_lower_limb_l_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="massage_lower_limb_l_normal" value="{{ old('massage_lower_limb_l_normal', $item->massage_lower_limb_l_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>変形徒手矯正術</td>
            <td><input type="number" name="manual_correction_first" value="{{ old('manual_correction_first', $item->manual_correction_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="manual_correction_normal" value="{{ old('manual_correction_normal', $item->manual_correction_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>温罨法</td>
            <td><input type="number" name="fomentation_first" value="{{ old('fomentation_first', $item->fomentation_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="fomentation_normal" value="{{ old('fomentation_normal', $item->fomentation_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>温罨法・電気光線器具</td>
            <td><input type="number" name="fomentation_and_elec_ray_first" value="{{ old('fomentation_and_elec_ray_first', $item->fomentation_and_elec_ray_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="fomentation_and_elec_ray_normal" value="{{ old('fomentation_and_elec_ray_normal', $item->fomentation_and_elec_ray_normal ?? '') }}" min="0" required></td>
          </tr>
        </tbody>
      </table>
      @error('massage_trunk_first')<div class="text-danger mt-1">{{ $message }}</div>@enderror
      @error('massage_trunk_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_upper_limb_r_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_upper_limb_r_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_upper_limb_l_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_upper_limb_l_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_lower_limb_r_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_lower_limb_r_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_lower_limb_l_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('massage_lower_limb_l_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('manual_correction_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('manual_correction_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('fomentation_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('fomentation_normal')<div class="text-danger">{{ $message }}</div>@enderror
      @error('fomentation_and_elec_ray_first')<div class="text-danger">{{ $message }}</div>@enderror
      @error('fomentation_and_elec_ray_normal')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
  </div>

  {{-- 往診料 --}}
  <div>
    <div class="form-label-tab">往診料</div>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th style="width: 40%;"></th>
            <th style="width: 30%;">初回料金</th>
            <th style="width: 30%;">通常料金</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>往診料（4km以内）</td>
            <td><input type="number" name="housecall_max_2km_first" value="{{ old('housecall_max_2km_first', $item->housecall_max_2km_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="housecall_max_2km_normal" value="{{ old('housecall_max_2km_normal', $item->housecall_max_2km_normal ?? '') }}" min="0" required></td>
          </tr>
          <tr>
            <td>往診料（4km超過）</td>
            <td><input type="number" name="housecall_additional_max_4km_first" value="{{ old('housecall_additional_max_4km_first', $item->housecall_additional_max_4km_first ?? '') }}" min="0" required></td>
            <td><input type="number" name="housecall_additional_max_4km_normal" value="{{ old('housecall_additional_max_4km_normal', $item->housecall_additional_max_4km_normal ?? '') }}" min="0" required></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- 対象期間 --}}
  <div>
    <div class="form-label-tab">対象期間
      @error('period_start')<span class="text-danger ms-2">{{ $message }}</span>@enderror
      @error('period_end')<span class="text-danger ms-2">{{ $message }}</span>@enderror
    </div>
    <div class="form-field px-3 py-2">
      <div class="form-field-top"></div>
      <table class="table table-bordered mb-0">
        <tr>
          <th style="width: 30%;">開始日<span class="text-danger">*</span></th>
          <td><input type="date" name="period_start" value="{{ old('period_start', $item->period_start ?? '') }}" required></td>
        </tr>
        <tr>
          <th>終了日<span class="text-danger">*</span></th>
          <td><input type="date" name="period_end" value="{{ old('period_end', $item->period_end ?? '') }}" required></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="mt-4 d-flex gap-2 justify-content-end">
    <a href="{{ $cancelRoute ?? route('master.treatment-fees.index') }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
    <button type="submit" class="btn-ex-main btn-ex-green">{{ $submitLabel ?? '登録' }}</button>
  </div>

</div>
