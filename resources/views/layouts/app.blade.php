<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>@yield('title', 'ホーム')｜鍼灸マッサージ管理システム v1.0.0</title>

		@php
			// ファビコン - 実績データ（Edit Square）
			if (request()->is('records', 'records/*'))
				$faviconIcon = ['symbol_evenodd' => 'M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Z'];

			// ファビコン - 報告書データ（Description Fill）
			elseif (request()->is('reports', 'reports/*'))
				$faviconIcon = ['symbol' => 'M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520h200L520-800v200Z'];

			// ファビコン - スケジュール（Calendar Month）
			elseif (request()->is('schedules', 'schedules/*'))
				$faviconIcon = ['symbol' => 'M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-188.5-11.5Q280-423 280-440t11.5-28.5Q303-480 320-480t28.5 11.5Q360-457 360-440t-11.5 28.5Q337-400 320-400t-28.5-11.5ZM640-400q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-188.5-11.5Q280-263 280-280t11.5-28.5Q303-320 320-320t28.5 11.5Q360-297 360-280t-11.5 28.5Q337-240 320-240t-28.5-11.5ZM640-240q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z'];

			// ファビコン - マスター登録（Settings）
			elseif (request()->is('master', 'master/*'))
				$faviconIcon = ['d' => 'M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.1-1.64a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.4 7.4 0 0 0-1.68-.98l-.38-2.65A.49.49 0 0 0 14 2h-4a.49.49 0 0 0-.49.42l-.38 2.65c-.61.25-1.17.59-1.68.98l-2.49-1a.49.49 0 0 0-.61.22l-2 3.46a.48.48 0 0 0 .12.64l2.1 1.64c-.04.32-.07.65-.07.99s.03.66.07.98l-2.1 1.64a.49.49 0 0 0-.12.64l2 3.46c.12.22.37.3.61.22l2.49-1c.51.39 1.07.73 1.68.98l.38 2.65c.07.38.39.65.49.65h4c.27 0 .49-.2.49-.42l.38-2.65c.61-.25 1.17-.59 1.68-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46a.49.49 0 0 0-.12-.64zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z'];

			// ファビコン - サブマスター登録（Settings）
			elseif (request()->is('submaster', 'submaster/*'))
				$faviconIcon = ['d' => 'M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.1-1.64a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.4 7.4 0 0 0-1.68-.98l-.38-2.65A.49.49 0 0 0 14 2h-4a.49.49 0 0 0-.49.42l-.38 2.65c-.61.25-1.17.59-1.68.98l-2.49-1a.49.49 0 0 0-.61.22l-2 3.46a.48.48 0 0 0 .12.64l2.1 1.64c-.04.32-.07.65-.07.99s.03.66.07.98l-2.1 1.64a.49.49 0 0 0-.12.64l2 3.46c.12.22.37.3.61.22l2.49-1c.51.39 1.07.73 1.68.98l.38 2.65c.07.38.39.65.49.65h4c.27 0 .49-.2.49-.42l.38-2.65c.61-.25 1.17-.59 1.68-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46a.49.49 0 0 0-.12-.64zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z'];

			// ファビコン - 印刷メニュー（Print）
			elseif (request()->is('prints', 'prints/*'))
				$faviconIcon = ['d' => 'M19 8H5a3 3 0 0 0-3 3v6h4v4h12v-4h4v-6a3 3 0 0 0-3-3zm-3 11H8v-5h8zm3-7a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm-1-9H6v4h12z'];

			// ファビコン - 要加療期間リスト（List）
			elseif (request()->is('therapy-periods', 'therapy-periods/*'))
				$faviconIcon = ['d' => 'M3 13h2v-2H3zm0 4h2v-2H3zm0-8h2V7H3zm4 4h14v-2H7zm0 4h14v-2H7zM7 7v2h14V7z'];

			// ファビコン - 入金管理（Currency Yen）
			elseif (request()->is('deposits', 'deposits/*'))
				$faviconIcon = ['symbol' => 'M440-120v-160H240v-80h200v-80H240v-80h163L200-840h95l185 292 185-292h95L557-520h163v80H520v80h200v80H520v160h-80Z'];

			// ファビコン - 管理パネル（Build Fill）
			elseif (request()->is('admin-panel', 'admin-panel/*'))
				$faviconIcon = ['symbol' => 'M686-132 444-376q-20 8-40.5 12t-43.5 4q-100 0-170-70t-70-170q0-36 10-68.5t28-61.5l146 146 72-72-146-146q29-18 61.5-28t68.5-10q100 0 170 70t70 170q0 23-4 43.5T584-516l244 242q12 12 12 29t-12 29l-84 84q-12 12-29 12t-29-12Z'];

			// ファビコン - デフォルト（Home）
			else
				$faviconIcon = ['d' => 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z'];

			if (isset($faviconIcon['symbol_evenodd']))
				$faviconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path fill="white" fill-rule="evenodd" d="' . $faviconIcon['symbol_evenodd'] . '"/></svg>';
			elseif (isset($faviconIcon['symbol']))
				$faviconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960"><path fill="white" d="' . $faviconIcon['symbol'] . '"/></svg>';
			else
				$faviconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="' . $faviconIcon['d'] . '"/></svg>';

			$faviconDataUri = 'data:image/svg+xml,' . rawurlencode($faviconSvg);
		@endphp
		<link rel="icon" type="image/svg+xml" href="{{ $faviconDataUri }}">

		<!-- CSS -->
		<!-- Bootstrap CSS -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

		<!-- DataTables CSS -->
		<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

		<!-- フォント -->
		<!-- Google Fonts (Noto Sans JP + M PLUS Rounded 1c) -->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=M+PLUS+Rounded+1c:wght@100;300;400;500;700;800;900&display=swap" rel="stylesheet">		

		<!-- Nerd Fonts (Webfont) -->
		<link rel="stylesheet" href="https://www.nerdfonts.com/assets/css/webfont.css">

		<!-- スクリプト -->
		@vite(['resources/css/app.css', 'resources/js/app.js'])

		<!-- 追加CSS -->
		@stack('styles')

		<!-- サイドバー状態の事前読み込み（フリッカー防止） -->
		<script>
			document.documentElement.dataset.sidebar =
				localStorage.getItem('sidebarState') === 'open' ? 'open' : 'closed';
		</script>

		@auth
		@unless(session('remember_login'))
		<!-- タブ全閉じ検知用セッションCookie（タブ/ウィンドウを全て閉じると消える） -->
		<script>
			document.cookie = 'tab_alive=1; path=/; SameSite=Lax';
		</script>
		@endunless
		@endauth
	</head>

	<body class="d-flex flex-column vh-100">
		<!-- ヘッダー -->
		<header class="bg-gray-26 text-light sticky-top user-select-none border-bottom border-3 border-secondary">
			@include('layouts.header')
		</header>

		<!-- コンテンツラッパー（サイドバー ＋ メインコンテンツ ＋ フッター）-->
		<div class="content-wrapper d-flex flex-grow-1 overflow-hidden">
			<!-- サイドバー -->
			<aside id="sidebar" class="bg-gray-26 text-light border-end border-secondary border-3">
				<div id="sidebar-content">
					@include('layouts.sidebar')
				</div>
			</aside>

			<!-- メインコンテンツ -->
			<div class="main-content flex-grow-1 pt-3 px-3 pb-0 overflow-y-auto d-flex flex-column bg-gray-90">
				<main class="flex-fill">
					{{ $slot }}
				</main>
				@unless($hideFooter)
				<!-- フッター -->
				   <footer class="py-2 mt-5 text-muted border-top border-1 border-secondary border-opacity-50 bg-gray-80">
					<p class="mx-4 my-0">Copyright © All rights reserved.</p>
				</footer>
				@endunless
			</div>
		</div>

		<!-- jQuery (required for DataTables) -->
		<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

		<!-- Bootstrap JS -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

		<!-- DataTables JS -->
		<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

		<!-- サイドバー JS -->
		<script src="{{ asset('js/sidebar.js') }}"></script>

		@stack('scripts')
	</body>
</html>
