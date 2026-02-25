//-- public/js/plans.js --//

$(document).ready(function() {
  // データがない場合はDataTablesを初期化しない
  const hasData = $('#planInfoTable tbody tr').length > 0 &&
                  !$('#planInfoTable tbody tr:first td[colspan]').length;

  if (hasData) {
    $('#planInfoTable').DataTable({
      language: {
        url: '/js/dataTables-ja.json',
        paginate: {
          previous: '◂ 前へ',
          next: '次へ ▸'
        }
      },
      order: [[3, 'desc']], // データ登録日の降順
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      columnDefs: [
        { orderable: false, targets: [4, 5] } // 複製・削除列はソート無効
      ]
    });
  }

  // 削除確認
  $(document).on('submit', '.delete-form', function(e) {
    e.preventDefault();
    if (confirm('一度削除したデータは元に戻せません。\n削除してもよろしいですか？')) {
      this.submit();
    }
  });

});
