<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>@yield('title', 'ホーム')｜鍼灸マッサージ管理システム v1.0.0</title>

		@php
			// URLパターンからfaviconアイコン（SVGパス）を決定
			$faviconPath = match(true) {
				request()->is('records', 'records/*')                 => 'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75z',                       // 鉛筆（実績）
				request()->is('reports', 'reports/*')                 => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm-1 1v5h5zM8 13h8v1.5H8zm0 3h8v1.5H8zm0-6h5v1.5H8z',                                  // ファイル（報告書）
				request()->is('schedules', 'schedules/*')             => 'M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V9h14zm0-12H5V5h14zM7 11h5v5H7z',             // カレンダー（スケジュール）
				request()->is('master', 'master/*')                   => 'M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm1 15h-2v-4H9l3-7 3 7h-2z M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.1-1.64a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.4 7.4 0 0 0-1.68-.98l-.38-2.65A.49.49 0 0 0 14 2h-4a.49.49 0 0 0-.49.42l-.38 2.65c-.61.25-1.17.59-1.68.98l-2.49-1a.49.49 0 0 0-.61.22l-2 3.46a.48.48 0 0 0 .12.64l2.1 1.64c-.04.32-.07.65-.07.99s.03.66.07.98l-2.1 1.64a.49.49 0 0 0-.12.64l2 3.46c.12.22.37.3.61.22l2.49-1c.51.39 1.07.73 1.68.98l.38 2.65c.07.38.39.65.49.65h4c.27 0 .49-.2.49-.42l.38-2.65c.61-.25 1.17-.59 1.68-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46a.49.49 0 0 0-.12-.64zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z', // 歯車（マスター）
				request()->is('submaster', 'submaster/*')             => 'M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.1-1.64a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.4 7.4 0 0 0-1.68-.98l-.38-2.65A.49.49 0 0 0 14 2h-4a.49.49 0 0 0-.49.42l-.38 2.65c-.61.25-1.17.59-1.68.98l-2.49-1a.49.49 0 0 0-.61.22l-2 3.46a.48.48 0 0 0 .12.64l2.1 1.64c-.04.32-.07.65-.07.99s.03.66.07.98l-2.1 1.64a.49.49 0 0 0-.12.64l2 3.46c.12.22.37.3.61.22l2.49-1c.51.39 1.07.73 1.68.98l.38 2.65c.07.38.39.65.49.65h4c.27 0 .49-.2.49-.42l.38-2.65c.61-.25 1.17-.59 1.68-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46a.49.49 0 0 0-.12-.64zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z', // 歯車（サブマスター）
				request()->is('prints', 'prints/*')                   => 'M19 8H5a3 3 0 0 0-3 3v6h4v4h12v-4h4v-6a3 3 0 0 0-3-3zm-3 11H8v-5h8zm3-7a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm-1-9H6v4h12z',                             // プリンター（印刷）
				request()->is('therapy-periods', 'therapy-periods/*') => 'M3 13h2v-2H3zm0 4h2v-2H3zm0-8h2V7H3zm4 4h14v-2H7zm0 4h14v-2H7zM7 7v2h14V7z',                                                                        // リスト（要加療期間）
				request()->is('deposits', 'deposits/*')               => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2zm0-4h-2V7h2z M11.5 5.5c.28 0 .5.22.5.5v4l2.5 1.5-.75 1.3-3.25-2V6c0-.28.22-.5.5-.5z M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm.5 15h-1v-5l-3-1.75.5-.87 3.5 2.12z', // 円（入金）
				request()->is('admin-panel', 'admin-panel/*')         => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5zm0 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 14c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08s5.97 1.09 6 3.08A7.24 7.24 0 0 1 12 19z', // シールド（管理）
				default                                               => 'M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z',                                                                                                                  // ホーム（デフォルト）
			};
			$faviconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="' . $faviconPath . '"/></svg>';
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
