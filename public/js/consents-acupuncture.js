//-- public/js/consents-acupuncture.js --//

$(document).ready(function() {
  // データがない場合はDataTablesを初期化しない
  const hasData = $('#consentingTable tbody tr').length > 0 &&
                  !$('#consentingTable tbody tr:first td[colspan]').length;

  if (hasData) {
    $('#consentingTable').DataTable({
      language: {
        url: '/js/dataTables-ja.json',
        paginate: {
          previous: '<span class="nf nf-fa-caret_left fs-5"></span>',
          next: '<span class="nf nf-fa-caret_right fs-5"></span>'
        }
      },
      order: [[4, 'desc']], // データ登録日の降順
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      autoWidth: false,
      columnDefs: [
        { orderable: false, targets: [5] } // 操作列はソート無効
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

  // 同意医師履歴印刷（動的にURLを取得）
  $('#printConsentingHistory').on('click', function() {
    const url = $(this).data('print-url');
    if (!url) {
      return;
    }
    const windowName = 'ConsentingHistoryPDF_' + new Date().getTime();
    const windowFeatures = 'popup=yes,width=1200,height=800,left=100,top=100,menubar=yes,toolbar=yes,location=yes,status=yes,scrollbars=yes,resizable=yes';
    window.open(url, windowName, windowFeatures);
  });
});
