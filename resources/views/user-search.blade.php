<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>利用者検索｜鍼灸マッサージ管理システム v1.0.0</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-90">
  <div class="d-inline-flex gap-3 align-items-start m-4 pt-2" style="align-items: stretch !important;">
    <!-- 左パネル：検索ツール -->
    <div class="flex-shrink-0 d-flex flex-column">
      <!-- 氏名/TEL検索 -->
      <div class="mb-5">
        <div class="form-label-tab">氏名/TEL検索</div>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <!-- 検索対象指定ラジオボタン -->
          <div class="mb-1">
            <label><input type="radio" name="search-target" value="name" checked> 氏名</label>
            <label><input type="radio" name="search-target" value="kana"> カナ</label>
            <label><input type="radio" name="search-target" value="tel"> TEL</label>
          </div>
          <!-- 検索ワード入力フィールドと検索ボタン -->
          <input type="text" id="search-keyword" placeholder="検索ワード入力" class="align-middle">
          <button type="button" id="search-btn" class="btn-ex-main btn-ex-blue btn-ex-sm align-middle">検索</button>
        </div>
      </div>


      <!-- 50音検索 -->
      <div class="mb-5">
        <div class="form-label-tab">50音検索</div>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <table id="katakana-table">
          <thead>
            <tr>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="wa" data-chars="ワ,ヲ,ン"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="ra" data-chars="ラ,リ,ル,レ,ロ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="ya" data-chars="ヤ,ユ,ヨ"></th>
              <th><input type="checkbox" class="katakana-column-checkbox" value="ma" data-chars="マ,ミ,ム,メ,モ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="ha" data-chars="ハ,ヒ,フ,ヘ,ホ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="na" data-chars="ナ,ニ,ヌ,ネ,ノ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="ta" data-chars="タ,チ,ツ,テ,ト"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="sa" data-chars="サ,シ,ス,セ,ソ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="ka" data-chars="カ,キ,ク,ケ,コ"></th>
              <th class="text-center"><input type="checkbox" class="katakana-column-checkbox" value="a" data-chars="ア,イ,ウ,エ,オ"></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75);" data-char="ワ">ワ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ラ">ラ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヤ">ヤ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="マ">マ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ハ">ハ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ナ">ナ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="タ">タ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="サ">サ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="カ">カ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ア">ア</button></td>
            </tr>
            <tr>
              <td></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="リ">リ</button></td>
              <td></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ミ">ミ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヒ">ヒ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ニ">ニ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="チ">チ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="シ">シ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="キ">キ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="イ">イ</button></td>
            </tr>
            <tr>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヲ">ヲ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ル">ル</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ユ">ユ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ム">ム</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="フ">フ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヌ">ヌ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ツ">ツ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ス">ス</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ク">ク</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ウ">ウ</button></td>
            </tr>
            <tr>
              <td></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="レ">レ</button></td>
              <td></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="メ">メ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヘ">ヘ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ネ">ネ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="テ">テ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="セ">セ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ケ">ケ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="エ">エ</button></td>
            </tr>
            <tr>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ン">ン</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ロ">ロ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ヨ">ヨ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="モ">モ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ホ">ホ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ノ">ノ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ト">ト</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="ソ">ソ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="コ">コ</button></td>
              <td><button type="button" class="katakana-btn btn-ex-main btn-ex-gray-light btn-ex-toggle-blue btn-ex-sm" style="--btn-padding-x: calc(var(--btn-padding-y-base) * 0.75)" data-char="オ">オ</button></td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>


      <!-- 該当利用者一覧ボックス -->
      <div>
        <div class="form-label-tab">該当者 (<span id="match-count">{{ count($users) }}</span>件)</div>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <select id="user-list" size="6" class="border border-0">
            @foreach ($users as $user)
              <option value="{{ $user->id }}" data-last-name="{{ $user->last_name }}" data-first-name="{{ $user->first_name }}" data-last-kana="{{ $user->last_kana }}" data-first-kana="{{ $user->first_kana }}" data-phone="{{ $user->phone }}" data-cell-phone="{{ $user->cell_phone }}" data-address="{{ trim(($user->address_1 ?? '') . ' ' . ($user->address_2 ?? '') . ' ' . ($user->address_3 ?? '')) }}" data-email="{{ $user->email }}" data-birthday="{{ $user->birthday ? $user->birthday->format('Y/n/j') : '' }}" data-age="{{ $user->age }}" data-note="{{ $user->note }}">ID-{{ str_pad($user->id, $clinicUserIdLength, '0', STR_PAD_LEFT) }}｜{{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}｜{{ $user->last_kana }}{{ "\u{2000}" }}{{ $user->first_kana }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <!-- 右パネル：利用者情報表示ボックス -->
    <div class="flex-shrink-0 d-flex flex-column" style="width: 400px; min-height: 0;">
      <div id="selected-user-box" class="d-flex flex-column flex-grow-1">
        <div class="form-label-tab">利用者情報</div>
        <div class="form-field px-3 py-2 d-flex flex-column flex-grow-1">
          <div class="form-field-top"></div>
          <div id="selected-user-info">
            <p>利用者を選択してください</p>
          </div>
          <button type="button" id="select-user-btn" class="btn-ex-main btn-ex-blue ms-auto" style="display: none;">この利用者を選択</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ウィンドウサイズをコンテンツに合わせて調整
      function resizeToContent() {
        const body = document.body;
        const html = document.documentElement;

        // コンテンツサイズを取得
        const contentWidth = Math.max(body.scrollWidth, body.offsetWidth, html.scrollWidth);
        const contentHeight = Math.max(body.scrollHeight, body.offsetHeight, html.scrollHeight);

        // ウィンドウの装飾部分（タイトルバー、境界線など）のサイズを計算
        const chromeWidth = window.outerWidth - window.innerWidth;
        const chromeHeight = window.outerHeight - window.innerHeight;

        const targetWidth = contentWidth + chromeWidth;
        const targetHeight = contentHeight + chromeHeight;

        // 画面サイズを超えないように制限
        const maxWidth = screen.availWidth * 0.9;
        const maxHeight = screen.availHeight * 0.9;
        const finalWidth = Math.min(targetWidth, maxWidth);
        const finalHeight = Math.min(targetHeight, maxHeight);

        // ウィンドウをリサイズして中央に配置
        const left = (screen.availWidth - finalWidth) / 2;
        const top = (screen.availHeight - finalHeight) / 2;

        window.resizeTo(finalWidth, finalHeight);
        window.moveTo(left, top);
      }

      // ページ読み込み完了後にリサイズ（少し遅延させてレンダリング完了を待つ）
      setTimeout(resizeToContent, 100);

      const allUsers = @json($users);
      const clinicUserIdLength = {{ $clinicUserIdLength }};
      const userList = document.getElementById('user-list');
      const matchCount = document.getElementById('match-count');
      const searchKeyword = document.getElementById('search-keyword');
      const searchBtn = document.getElementById('search-btn');
      const selectUserBtn = document.getElementById('select-user-btn');
      const selectedUserInfo = document.getElementById('selected-user-info');
      const searchTargetRadios = document.querySelectorAll('input[name="search-target"]');
      const katakanaButtons = document.querySelectorAll('.katakana-btn');
      const katakanaColumnCheckboxes = document.querySelectorAll('.katakana-column-checkbox');

      let selectedKatakanaChars = [];
      let currentSelectedUserId = null;

      // 選択利用者を表示
      function displayUserInfo(option) {
        if (!option) {
          selectedUserInfo.innerHTML = '<p>利用者が選択されていません</p>';
          selectUserBtn.style.display = 'none';
          currentSelectedUserId = null;
          return;
        }
        selectUserBtn.style.display = 'block';

        currentSelectedUserId = option.value;
        const lastName = option.dataset.lastName || '';
        const firstName = option.dataset.firstName || '';
        const lastKana = option.dataset.lastKana || '';
        const firstKana = option.dataset.firstKana || '';
        const phone = option.dataset.phone || '';
        const cellPhone = option.dataset.cellPhone || '';
        const address = option.dataset.address || '';
        const email = option.dataset.email || '';
        const birthday = option.dataset.birthday || '';
        const age = option.dataset.age || '';
        const note = option.dataset.note || '';

        let birthdayDisplay = birthday;
        if (birthday && age) {
          birthdayDisplay = birthday + '（' + age + '歳）';
        }

        selectedUserInfo.innerHTML = `
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">氏名：</div>
            <div class="fw-medium ms-1">${lastName}\u2000${firstName}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">カナ：</div>
            <div class="fw-medium ms-1">${lastKana}\u2000${firstKana}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">固定電話番号：</div>
            <div class="fw-medium ms-1">${phone || '-'}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">携帯電話番号：</div>
            <div class="fw-medium ms-1">${cellPhone || '-'}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">住所：</div>
            <div class="fw-medium ms-1">${address || '-'}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">メールアドレス：</div>
            <div class="fw-medium ms-1">${email || '-'}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">生年月日：</div>
            <div class="fw-medium ms-1">${birthdayDisplay || '-'}</div>
          </div>
          <div class="mb-3">
            <div class="fw-medium opacity-75 small">メモ：</div>
            <div class="fw-medium ms-1">${note || '-'}</div>
          </div>
        `;

        // コンテンツサイズに応じてウィンドウサイズを調整
        resizeToContent();
      }

      // 利用者一覧クリックで選択利用者を表示
      userList.addEventListener('click', function() {
        const selected = userList.options[userList.selectedIndex];
        displayUserInfo(selected);
      });

      // 利用者リストを更新
      function updateUserList(users) {
        userList.innerHTML = '';
        users.forEach(user => {
          const option = document.createElement('option');
          option.value = user.id;
          option.dataset.lastName = user.last_name || '';
          option.dataset.firstName = user.first_name || '';
          option.dataset.lastKana = user.last_kana || '';
          option.dataset.firstKana = user.first_kana || '';
          option.dataset.phone = user.phone || '';
          option.dataset.cellPhone = user.cell_phone || '';
          const address = [user.address_1, user.address_2, user.address_3].filter(Boolean).join(' ');
          option.dataset.address = address;
          option.dataset.email = user.email || '';
          option.dataset.birthday = user.birthday ? user.birthday.split('T')[0].replace(/-/g, '/') : '';
          option.dataset.age = user.age || '';
          option.dataset.note = user.note || '';
          const paddedId = 'ID-' + String(user.id).padStart(clinicUserIdLength, '0');
          option.textContent = paddedId + '｜' + (user.last_name || '') + '\u2000' + (user.first_name || '') + '｜' + (user.last_kana || '') + '\u2000' + (user.first_kana || '');
          userList.appendChild(option);
        });
        matchCount.textContent = users.length;
      }

      // 検索実行
      function performSearch() {
        const keyword = searchKeyword.value.trim().toLowerCase();
        const target = document.querySelector('input[name="search-target"]:checked').value;

        if (!keyword) {
          updateUserList(allUsers);
          return;
        }

        const filtered = allUsers.filter(user => {
          if (target === 'name') {
            const fullName = ((user.last_name || '') + '\u2000' + (user.first_name || '')).toLowerCase();
            return fullName.includes(keyword);
          } else if (target === 'kana') {
            const fullKana = ((user.last_kana || '') + '\u2000' + (user.first_kana || '')).toLowerCase();
            return fullKana.includes(keyword);
          } else if (target === 'tel') {
            const phone = (user.phone || '').toLowerCase();
            const cellPhone = (user.cell_phone || '').toLowerCase();
            return phone.includes(keyword) || cellPhone.includes(keyword);
          }
          return false;
        });

        updateUserList(filtered);
      }

      // カタカナフィルタリング
      function performKatakanaFilter() {
        if (selectedKatakanaChars.length === 0) {
          updateUserList(allUsers);
          return;
        }

        const filtered = allUsers.filter(user => {
          const lastKana = user.last_kana || '';
          if (!lastKana) return false;
          const firstChar = lastKana.charAt(0);
          return selectedKatakanaChars.includes(firstChar);
        });

        updateUserList(filtered);
      }

      // カタカナ選択状態をリセット
      function clearKatakanaSelection() {
        selectedKatakanaChars = [];
        katakanaButtons.forEach(btn => deactivateBtnCustom(btn));
        katakanaColumnCheckboxes.forEach(cb => cb.checked = false);
        updateUserList(allUsers);
      }

      // 検索ボタン
      searchBtn.addEventListener('click', function() {
        clearKatakanaSelection();
        performSearch();
      });

      // Enterキーで検索
      searchKeyword.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          clearKatakanaSelection();
          performSearch();
        }
      });

      // カタカナボタンクリック
      katakanaButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          // 検索対象を氏名に変更
          document.querySelector('input[name="search-target"][value="name"]').checked = true;
          // 検索ワードをクリア
          searchKeyword.value = '';
          // 列チェックボックスの選択を解除
          katakanaColumnCheckboxes.forEach(cb => cb.checked = false);

          const char = this.dataset.char;

          // 単一文字選択モード
          katakanaButtons.forEach(b => deactivateBtnCustom(b));
          activateBtnCustom(this);
          selectedKatakanaChars = [char];

          performKatakanaFilter();
        });
      });

      // カタカナ列チェックボックスクリック
      katakanaColumnCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          // 検索対象を氏名に変更
          document.querySelector('input[name="search-target"][value="name"]').checked = true;
          // 検索ワードをクリア
          searchKeyword.value = '';

          if (this.checked) {
            // 他のチェックボックスを外す
            katakanaColumnCheckboxes.forEach(cb => {
              if (cb !== this) {
                cb.checked = false;
              }
            });

            const chars = this.dataset.chars.split(',');
            selectedKatakanaChars = chars;

            // ボタンのハイライト更新
            katakanaButtons.forEach(btn => {
              if (chars.includes(btn.dataset.char)) {
                activateBtnCustom(btn);
              } else {
                deactivateBtnCustom(btn);
              }
            });
          } else {
            // チェックを外した場合はリセット
            selectedKatakanaChars = [];
            katakanaButtons.forEach(btn => deactivateBtnCustom(btn));
          }

          performKatakanaFilter();
        });
      });

      // この利用者を選択ボタン
      selectUserBtn.addEventListener('click', function() {
        if (!currentSelectedUserId) {
          alert('利用者を選択してください');
          return;
        }

        // 親ウィンドウのセレクトボックスに値を設定
        if (window.opener && !window.opener.closed) {
          const parentSelect = window.opener.document.getElementById('clinic_user_id');
          if (parentSelect) {
            parentSelect.value = currentSelectedUserId;
            // フォームを自動送信
            const filterForm = window.opener.document.getElementById('filterForm');
            if (filterForm) {
              filterForm.submit();
            }
          }
        }

        // ポップアップウィンドウを閉じる
        window.close();
      });
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
