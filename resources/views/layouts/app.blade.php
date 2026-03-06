<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

		<title>{{ config('app.name', 'Laravel') }}</title>

		<!-- Google Fonts (Noto Sans JP + M PLUS Rounded 1c) -->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=M+PLUS+Rounded+1c:wght@100;300;400;500;700;800;900&display=swap" rel="stylesheet">		

		<!-- Bootstrap CSS -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

		<!-- Nerd Fonts (Webfont) -->
		<link rel="stylesheet" href="https://www.nerdfonts.com/assets/css/webfont.css">

		<!-- DataTables CSS -->
		<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

		<!-- スクリプト -->
		@vite(['resources/css/app.css', 'resources/js/app.js'])

		<!-- 追加CSS -->
		@stack('styles')
	</head>
	<body class="min-vh-100 overflow-hidden">

		<!-- ヘッダー -->
		<header class="position-fixed top-0 start-0 end-0 border-bottom border-secondary border-2 px-3 py-2 bg-dark text-light" style="z-index: 1000;">
			@include('layouts.header')
		</header>

		<!-- サイドバー状態の事前読み込み（ちらつき防止） -->
		<script>
			(function() {
				if (localStorage.getItem('sidebarState') === 'closed') {
					document.documentElement.classList.add('sidebar-preload-closed');
				}
			})();
		</script>

		<!-- Content Wrapper（サイドバー ＋ メインコンテンツ ＋ フッター）-->
		<div class="content-wrapper">
			<!-- サイドバー -->
			@include('layouts.sidebar')

			<!-- メインコンテンツ -->
			<div class="main-content">
				<main class="flex-fill">
					{{ $slot }}
				</main>
				@unless($hideFooter)
				<!-- フッター -->
				   <footer class="py-2 text-muted border-top border-dark-subtle bg-body-secondary" style="margin: 2rem -1rem 0 -1rem;">
					<p class="mx-3 my-0">Copyright © All rights reserved.</p>
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
