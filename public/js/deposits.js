// 展開中の月を追跡
let currentExpandedMonth = null;
let isLoadingData = false;
let isScrolling = false; // スクロール中フラグ
let initialExpansionDone = false; // 初期展開完了フラグ

// 年ヘッダーのアイコン切り替え
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.collapse').forEach(function(collapseElement) {
    collapseElement.addEventListener('show.bs.collapse', function(event) {
      const button = document.querySelector(`[data-bs-target="#${this.id}"]`);
      if (button) {
        const icon = button.querySelector('.toggle-icon');
        if (icon) icon.textContent = '▼';
      }
    });

    collapseElement.addEventListener('hide.bs.collapse', function() {
      const button = document.querySelector(`[data-bs-target="#${this.id}"]`);
      if (button) {
        const icon = button.querySelector('.toggle-icon');
        if (icon) icon.textContent = '▶';
      }
    });

    // 年が展開されたら、その年の最新月（最初の月セクション）を自動展開
    collapseElement.addEventListener('shown.bs.collapse', function() {
      // 初期展開が既に完了している場合は、自動展開をスキップ（ユーザーの手動操作のみ許可）
      if (initialExpansionDone) {
        return;
      }

      // 既にスクロール処理中の場合はスキップ
      if (isScrolling) {
        return;
      }

      // データがある最初の月セクションを取得
      const firstMonthSection = this.querySelector('.deposit-month-section[data-has-data="true"]');
      if (firstMonthSection) {
        const yearMonth = firstMonthSection.getAttribute('data-year-month');
        if (yearMonth && yearMonth !== currentExpandedMonth) {
          isScrolling = true;
          const collapseEl = this;
          expandMonth(yearMonth, () => {
            scrollToYear(collapseEl);
            // スクロール完了後、フラグをリセット
            setTimeout(() => {
              isScrolling = false;
              initialExpansionDone = true;
            }, 1000);
          });
        }
      }
    });
  });

  // 現在年月を自動展開（1回のみ）
  if (window.depositsConfig.scrollToYearMonth && !initialExpansionDone) {
    const targetSection = document.querySelector(`[data-year-month="${window.depositsConfig.scrollToYearMonth}"]`);
    if (targetSection) {
      const collapseParent = targetSection.closest('.collapse');
      if (collapseParent) {
        const collapseInstance = new bootstrap.Collapse(collapseParent, { toggle: true });
      }
    }
  }
});

// 月データを展開
function expandMonth(yearMonth, callback) {
  // 既に同じ月を展開中、またはデータ読み込み中の場合はスキップ
  if (yearMonth === currentExpandedMonth || isLoadingData) {
    return;
  }

  // 他の展開中の月を格納
  if (currentExpandedMonth && currentExpandedMonth !== yearMonth) {
    const prevContainer = document.querySelector(`[data-year-month="${currentExpandedMonth}"] .deposit-data-container`);
    if (prevContainer) {
      prevContainer.innerHTML = '';
    }

    const prevMonthSection = document.querySelector(`[data-year-month="${currentExpandedMonth}"]`);
    if (prevMonthSection) {
      const prevIcon = prevMonthSection.querySelector('.month-toggle-icon');
      if (prevIcon) prevIcon.textContent = '▸';
    }

    const prevSection = document.querySelector(`[data-year-month="${currentExpandedMonth}"]`);
    if (prevSection) {
      const prevCollapseParent = prevSection.closest('.collapse');
      if (prevCollapseParent && prevCollapseParent.classList.contains('show')) {
        const prevYear = prevCollapseParent.getAttribute('data-year');
        const currentYear = yearMonth.split('-')[0];
        if (prevYear !== currentYear) {
          prevCollapseParent.classList.remove('show');
        }
      }
    }
  }

  currentExpandedMonth = yearMonth;

  // データを取得して表示
  const container = document.querySelector(`[data-year-month="${yearMonth}"] .deposit-data-container`);
  if (!container) {
    return;
  }

  container.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 読み込み中...</div>';

  const monthSection = document.querySelector(`[data-year-month="${yearMonth}"]`);
  if (monthSection) {
    const monthIcon = monthSection.querySelector('.month-toggle-icon');
    if (monthIcon) monthIcon.textContent = '▾';
  }

  const url = window.depositsConfig.getMonthDataUrl.replace(':yearMonth', yearMonth);

  isLoadingData = true;

  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (data.deposits.length === 0) {
          container.innerHTML = '<span class="text-secondary">該当データなし</span>';
        } else {
          container.innerHTML = renderDepositsTable(data.deposits);
        }
      } else {
        container.innerHTML = '<span class="text-danger">データの取得に失敗しました</span>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      container.innerHTML = '<span class="text-danger">データの取得に失敗しました</span>';
    })
    .finally(() => {
      isLoadingData = false;
      if (callback && typeof callback === 'function') {
        callback();
      }
    });
}

// 入金データテーブルをレンダリング
function renderDepositsTable(deposits) {
  let html = '<div class="table-responsive"><table class="table table-bordered table-sm w-100" style="table-layout: auto;"><thead style=" font-size: 0.9rem" class="table-light"><tr>';
  html += '<th class="text-center align-middle" style="width: 3%;">ID</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 10%;">保険者</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 8%;">被保険者</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 8%;">受療者</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 8%;">治療日</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 6%;">施術種類</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 9%;">療養費合計</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 9%;">自己負担額</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 9%;">保険請求額</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 9%;">入金額</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 10%;">入金日</th>';
  html += '<th class="text-center align-middle text-nowrap" style="width: 5%;">登録</th>';
  html += '</tr></thead><tbody class="small">';

  deposits.forEach(deposit => {
    html += '<tr>';
    html += `<td class="text-center align-middle">${deposit.id}</td>`;
    html += `<td class="align-middle text-truncate" style="max-width: 150px;" title="${deposit.insurer_name}">${deposit.insurer_name}</td>`;
    html += `<td class="align-middle text-truncate" style="max-width: 120px;" title="${deposit.insured_name}">${deposit.insured_name}</td>`;
    html += `<td class="align-middle text-truncate" style="max-width: 120px;" title="${deposit.clinic_user_name}">${deposit.clinic_user_name}</td>`;
    html += `<td class="align-middle small" style="white-space: pre-line; word-break: break-word;">${deposit.treatment_dates}</td>`;
    html += `<td class="text-center align-middle">${deposit.treatment_type}</td>`;
    html += `<td class="text-end align-middle px-2">${deposit.total_amount.toLocaleString()}</td>`;
    html += `<td class="text-end align-middle px-2">${deposit.selfpay_amount.toLocaleString()}</td>`;
    html += `<td class="text-end align-middle px-2">${deposit.insurance_billing_amount.toLocaleString()}</td>`;
    html += `<td class="align-middle p-1"><input type="number" class="form-control form-control-sm w-100" data-id="${deposit.id}" data-field="deposit_amount" value="${deposit.deposit_amount}" min="0"></td>`;
    html += `<td class="align-middle p-1"><input type="date" class="form-control form-control-sm w-100" data-id="${deposit.id}" data-field="deposit_date" value="${deposit.deposit_date}"></td>`;
    html += `<td class="text-center align-middle p-1"><button type="button" class="btn btn-sm btn-primary" onclick="saveDeposit(${deposit.id})">登録</button></td>`;
    html += '</tr>';
  });

  html += '</tbody></table></div>';
  return html;
}

// 入金データを保存
function saveDeposit(depositId) {
  const inputs = document.querySelectorAll(`input[data-id="${depositId}"]`);
  const data = { _token: window.depositsConfig.csrfToken };

  inputs.forEach(input => {
    const field = input.getAttribute('data-field');
    data[field] = input.value || (input.type === 'date' ? null : 0);
  });

  const url = window.depositsConfig.updateUrl.replace(':id', depositId);

  fetch(url, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': window.depositsConfig.csrfToken
    },
    body: JSON.stringify(data)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
    } else {
      alert('エラー: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('データの保存に失敗しました');
  });
}

// 指定年月へスクロール
function scrollToMonth(yearMonth) {
  const targetSection = document.querySelector(`[data-year-month="${yearMonth}"]`);
  if (targetSection) {
    const container = document.getElementById('deposits-list-area');
    if (container) {
      const containerRect = container.getBoundingClientRect();
      const targetRect = targetSection.getBoundingClientRect();
      const scrollOffset = targetRect.top - containerRect.top + container.scrollTop;

      container.scrollTo({
        top: scrollOffset,
        behavior: 'smooth'
      });
    }
  }
}

// 指定年要素へスクロール
function scrollToYear(collapseElement) {
  const yearHeader = collapseElement.previousElementSibling;
  if (yearHeader && yearHeader.classList.contains('year-header')) {
    const container = document.getElementById('deposits-list-area');
    if (container) {
      const containerRect = container.getBoundingClientRect();
      const yearHeaderRect = yearHeader.getBoundingClientRect();
      const scrollOffset = yearHeaderRect.top - containerRect.top + container.scrollTop;

      container.scrollTo({
        top: scrollOffset,
        behavior: 'smooth'
      });
    }
  }
}

// 年ボタンのクリックイベントを監視
document.addEventListener('click', function(e) {
  const yearButton = e.target.closest('[data-bs-toggle="collapse"]');
  if (yearButton) {
    const targetId = yearButton.getAttribute('data-bs-target');
    if (targetId && targetId.startsWith('#year-')) {
      const collapseElement = document.querySelector(targetId);
      const isCurrentlyClosed = !collapseElement.classList.contains('show');

      // 他の展開中の年を全て格納
      document.querySelectorAll('.collapse.show').forEach(function(openCollapse) {
        if (openCollapse.id !== collapseElement.id && openCollapse.id.startsWith('year-')) {
          const collapseInstance = bootstrap.Collapse.getInstance(openCollapse);
          if (collapseInstance) {
            collapseInstance.hide();
          } else {
            openCollapse.classList.remove('show');
          }
        }
      });

      // 年を展開する場合のみ、月データ展開とスクロールを実行
      if (isCurrentlyClosed) {
        setTimeout(() => {
          // データがある最初の月セクションを取得
          const firstMonthSection = collapseElement.querySelector('.deposit-month-section[data-has-data="true"]');
          if (firstMonthSection) {
            const yearMonth = firstMonthSection.getAttribute('data-year-month');
            // currentExpandedMonthをリセットして必ず実行されるようにする
            const prevExpandedMonth = currentExpandedMonth;
            currentExpandedMonth = null;
            expandMonth(yearMonth, () => {
              scrollToYear(collapseElement);
            });
            // expandMonthが実行されなかった場合に備えて復元
            if (currentExpandedMonth === null) {
              currentExpandedMonth = prevExpandedMonth;
            }
          } else {
            // データがない年の場合でもスクロールを実行
            scrollToYear(collapseElement);
          }
        }, 400);
      }
    }
  }
});

// 月セクションのクリックイベントを監視
document.addEventListener('click', function(e) {
  const monthSection = e.target.closest('.deposit-month-section');
  if (monthSection && !e.target.closest('button') && !e.target.closest('input')) {
    // データがある月のみ処理
    const hasData = monthSection.getAttribute('data-has-data') === 'true';
    if (!hasData) return;

    const yearMonth = monthSection.getAttribute('data-year-month');
    if (yearMonth !== currentExpandedMonth) {
      expandMonth(yearMonth, () => {
        scrollToMonth(yearMonth);
      });
    }
  }
});
