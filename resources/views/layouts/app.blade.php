<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>{{ config('app.name', 'Laravel') }}</title>

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
		<header class="bg-gray-26 text-light sticky-top user-select-none border-bottom border-secondary border-3">
			@include('layouts.header')
		</header>

		<!-- コンテンツラッパー（サイドバー ＋ メインコンテンツ ＋ フッター）-->
		<div class="content-wrapper">
			<!-- サイドバー -->
			<aside id="sidebar" class="bg-gray-26 text-light border-end border-secondary border-3">
				<div id="sidebar-content">
					@include('layouts.sidebar')
				</div>
			</aside>

			<!-- メインコンテンツ -->
			<div class="main-content bg-gray-92">
				<main class="flex-fill">
					{{ $slot }}
				</main>
			</div>
		</div>

		@unless($hideFooter)
		<!-- フッター -->
		<footer class="py-2 text-muted border-top border-dark-subtle bg-body-secondary">
			<p class="mx-3 my-0">Copyright © All rights reserved.</p>
		</footer>
		@endunless

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
