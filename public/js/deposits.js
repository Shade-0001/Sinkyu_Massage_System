//-- public/js/deposits.js --//

// 状態管理
let currentExpandedMonth = null;
let isLoadingData = false;
let initialExpansionDone = false;

// ── hr2 ヘルパー ──────────────────────────────
function getHr1Metrics(block) {
  const topHr = block.querySelector('.year-top-hr');
  return { top: topHr.offsetTop, left: topHr.offsetLeft, width: topHr.offsetWidth };
}

function blockExpandedTop(block) {
  const pb = parseFloat(getComputedStyle(block).paddingBottom) || 0;
  const bottomHr = block.querySelector('.year-bottom-hr');
  const hrH = bottomHr ? bottomHr.offsetHeight : 0;
  return block.clientHeight - pb - hrH;
}

function setHr2ToHr1(block) {
  const bottomHr = block.querySelector('.year-bottom-hr');
  const m = getHr1Metrics(block);
  bottomHr.style.transition = 'none';
  bottomHr.style.top = m.top + 'px';
  bottomHr.style.left = m.left + 'px';
  bottomHr.style.width = m.width + 'px';
}

function setHr2Expanded(block) {
  const bottomHr = block.querySelector('.year-bottom-hr');
  const cs = getComputedStyle(block);
  const pl = parseFloat(cs.paddingLeft) || 0;
  const pr = parseFloat(cs.paddingRight) || 0;
  const hrWidth = block.clientWidth - pl - pr;
  const hrLeft = (block.clientWidth - hrWidth) / 2;
  bottomHr.style.transition = 'none';
  bottomHr.style.top = blockExpandedTop(block) + 'px';
  bottomHr.style.left = hrLeft + 'px';
  bottomHr.style.width = hrWidth + 'px';
}

// rAFループでblock.clientHeightの変化に追従してhr2のtopを更新
const hr2Loops = new Map();
function startHr2Tracking(block) {
  const id = block.id || (block._hr2id = block._hr2id || Math.random());
  if (hr2Loops.has(id)) cancelAnimationFrame(hr2Loops.get(id));
  const bottomHr = block.querySelector('.year-bottom-hr');
  let last = -1;
  let stable = 0;
  function loop() {
    const h = block.clientHeight;
    bottomHr.style.top = blockExpandedTop(block) + 'px';
    if (h === last) { stable++; if (stable >= 3) { hr2Loops.delete(id); return; } }
    else stable = 0;
    last = h;
    hr2Loops.set(id, requestAnimationFrame(loop));
  }
  hr2Loops.set(id, requestAnimationFrame(loop));
}

// ── アニメーション ────────────────────────────
function expandContent(content) {
  content.style.height = content.scrollHeight + 'px';
  content.style.transform = 'scaleY(1)';
  content.style.opacity = '1';
  content.addEventListener('transitionend', function onEnd(e) {
    if (e.target !== content || e.propertyName !== 'height') return;
    content.removeEventListener('transitionend', onEnd);
    content.style.height = 'auto';
  });
}

function collapseContent(content) {
  content.style.height = content.scrollHeight + 'px';
  requestAnimationFrame(() => {
    content.style.height = '0';
    content.style.transform = 'scaleY(0)';
    content.style.opacity = '0';
  });
}

// ── 年展開格納 ────────────────────────────────
function toggleYear(btn) {
  const targetId = btn.dataset.toggleYear;
  const content = document.getElementById(targetId);
  const block = btn.closest('.year-block');
  const arrow = btn.querySelector('.year-toggle-arrow');
  const bottomHr = block.querySelector('.year-bottom-hr');
  const isExpanded = content.classList.contains('expanded');

  // 他の展開中の年を格納
  document.querySelectorAll('.year-content.expanded').forEach(openContent => {
    if (openContent.id === targetId) return;
    const openBlock = openContent.closest('.year-block');
    const openBtn = document.querySelector(`[data-toggle-year="${openContent.id}"]`);
    const openArrow = openBtn ? openBtn.querySelector('.year-toggle-arrow') : null;
    const openBottomHr = openBlock ? openBlock.querySelector('.year-bottom-hr') : null;
    openContent.classList.remove('expanded');
    if (openBtn) {
      openBtn.setAttribute('aria-expanded', 'false');
      openBtn.classList.remove('btn-ex-active');
    }
    if (openArrow) openArrow.classList.remove('rotated');
    collapseContent(openContent);
    if (openBottomHr) {
      const m = getHr1Metrics(openBlock);
      openBottomHr.style.transition = 'top 0.3s ease, left 0.3s ease, width 0.3s ease';
      openBottomHr.style.top = m.top + 'px';
      openBottomHr.style.left = m.left + 'px';
      openBottomHr.style.width = m.width + 'px';
      openContent.addEventListener('transitionend', function onEnd(e) {
        if (e.target !== openContent || e.propertyName !== 'height') return;
        openContent.removeEventListener('transitionend', onEnd);
        openBottomHr.style.transition = 'none';
        openBottomHr.style.display = 'none';
      });
    }
  });

  if (isExpanded) {
    // 格納
    content.classList.remove('expanded');
    btn.setAttribute('aria-expanded', 'false');
    btn.classList.remove('btn-ex-active');
    if (arrow) arrow.classList.remove('rotated');
    collapseContent(content);
    const m = getHr1Metrics(block);
    bottomHr.style.transition = 'top 0.3s ease, left 0.3s ease, width 0.3s ease';
    bottomHr.style.top = m.top + 'px';
    bottomHr.style.left = m.left + 'px';
    bottomHr.style.width = m.width + 'px';
    content.addEventListener('transitionend', function onEnd(e) {
      if (e.target !== content || e.propertyName !== 'height') return;
      content.removeEventListener('transitionend', onEnd);
      bottomHr.style.transition = 'none';
      bottomHr.style.display = 'none';
    });
  } else {
    // 展開
    content.classList.add('expanded');
    btn.setAttribute('aria-expanded', 'true');
    btn.classList.add('btn-ex-active');
    if (arrow) arrow.classList.add('rotated');
    setHr2ToHr1(block);
    bottomHr.style.display = 'block';
    expandContent(content);
    const cs = getComputedStyle(block);
    const pl = parseFloat(cs.paddingLeft) || 0;
    const pr = parseFloat(cs.paddingRight) || 0;
    const hrWidth = block.clientWidth - pl - pr;
    const hrLeft = (block.clientWidth - hrWidth) / 2;
    requestAnimationFrame(() => {
      bottomHr.style.transition = 'left 0.3s ease, width 0.3s ease';
      bottomHr.style.left = hrLeft + 'px';
      bottomHr.style.width = hrWidth + 'px';
      startHr2Tracking(block);
    });

    // 年展開時に最初のデータあり月を自動展開（初回のみ）
    if (!initialExpansionDone) {
      const firstMonthBtn = content.querySelector('[data-toggle-month]');
      if (firstMonthBtn) {
        setTimeout(() => toggleMonth(firstMonthBtn), 350);
      }
    }
  }
}

// ── 月展開格納 ────────────────────────────────
function toggleMonth(btn) {
  const targetId = btn.dataset.toggleMonth;
  const content = document.getElementById(targetId);
  const block = btn.closest('.year-block');
  const arrow = btn.querySelector('.year-toggle-arrow');
  const isExpanded = content.classList.contains('expanded');
  const monthSection = btn.closest('.deposit-month-section');
  const yearMonth = monthSection ? monthSection.dataset.yearMonth : null;

  // 他の展開中の月を格納
  document.querySelectorAll('.month-content.expanded').forEach(openContent => {
    if (openContent.id === targetId) return;
    const openBtn = document.querySelector(`[data-toggle-month="${openContent.id}"]`);
    if (openBtn) {
      openBtn.classList.remove('btn-ex-active');
      openBtn.setAttribute('aria-expanded', 'false');
      const openArrow = openBtn.querySelector('.year-toggle-arrow');
      if (openArrow) openArrow.classList.remove('rotated');
    }
    openContent.classList.remove('expanded');
    collapseContent(openContent);
  });
  currentExpandedMonth = null;

  if (isExpanded) {
    // 格納
    content.classList.remove('expanded');
    btn.setAttribute('aria-expanded', 'false');
    btn.classList.remove('btn-ex-active');
    if (arrow) arrow.classList.remove('rotated');
    collapseContent(content);
    startHr2Tracking(block);
  } else {
    // 展開 → アニメーション開始、その後AJAXロード
    content.classList.add('expanded');
    btn.setAttribute('aria-expanded', 'true');
    btn.classList.add('btn-ex-active');
    if (arrow) arrow.classList.add('rotated');
    expandContent(content);
    currentExpandedMonth = yearMonth;
    startHr2Tracking(block);

    if (yearMonth) {
      loadMonthData(yearMonth, content, block);
    }
  }
}

// ── AJAXデータロード ──────────────────────────
function loadMonthData(yearMonth, monthContent, block) {
  if (isLoadingData) return;
  isLoadingData = true;

  const container = monthContent.querySelector('.deposit-data-container');
  if (!container) {
    isLoadingData = false;
    return;
  }

  container.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 読み込み中...</div>';

  // spinner表示後にheightを再計算
  requestAnimationFrame(() => {
    monthContent.style.height = monthContent.scrollHeight + 'px';
    startHr2Tracking(block);
  });

  const url = window.depositsConfig.getMonthDataUrl.replace(':yearMonth', yearMonth);

  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        container.innerHTML = data.deposits.length === 0
          ? '<span class="text-secondary ms-2 d-inline-block py-2">該当データなし</span>'
          : renderDepositsTable(data.deposits);
      } else {
        container.innerHTML = '<span class="text-danger ms-2">データの取得に失敗しました</span>';
      }
    })
    .catch(() => {
      container.innerHTML = '<span class="text-danger ms-2">データの取得に失敗しました</span>';
    })
    .finally(() => {
      isLoadingData = false;
      // テーブル注入後にheightを再計算
      monthContent.style.height = monthContent.scrollHeight + 'px';
      monthContent.addEventListener('transitionend', function onEnd(e) {
        if (e.propertyName !== 'height') return;
        monthContent.removeEventListener('transitionend', onEnd);
        monthContent.style.height = 'auto';
        startHr2Tracking(block);
      });
      startHr2Tracking(block);
    });
}

// ── 入金データテーブルをレンダリング ────────────
function renderDepositsTable(deposits) {
  let html = '<div class="table-responsive"><table class="table table-bordered table-sm w-100" style="table-layout: auto;"><thead style="font-size: 0.9rem;" class="table-light"><tr>';
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

// ── 入金データを保存 ──────────────────────────
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
  .catch(() => {
    alert('データの保存に失敗しました');
  });
}

// ── スクロールヘルパー ────────────────────────
function scrollToSection(targetSection) {
  if (!targetSection) return;
  const container = document.getElementById('deposits-list-area');
  if (!container) return;
  const containerRect = container.getBoundingClientRect();
  const targetRect = targetSection.getBoundingClientRect();
  const scrollOffset = targetRect.top - containerRect.top + container.scrollTop;
  container.scrollTo({ top: scrollOffset, behavior: 'smooth' });
}

// ── 初期化 ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  // 初期状態のheight・hr2セット（expanded クラス付きブロックの初期化）
  document.querySelectorAll('.year-block').forEach(block => {
    const yearContent = block.querySelector('.year-content');
    const bottomHr = block.querySelector('.year-bottom-hr');
    if (!bottomHr) return;
    if (yearContent && yearContent.classList.contains('expanded')) {
      yearContent.style.height = 'auto';
      yearContent.style.transform = 'scaleY(1)';
      yearContent.style.opacity = '1';
      setHr2ToHr1(block);
      bottomHr.style.display = 'block';
      requestAnimationFrame(() => {
        const cs = getComputedStyle(block);
        const pl = parseFloat(cs.paddingLeft) || 0;
        const pr = parseFloat(cs.paddingRight) || 0;
        const hrWidth = block.clientWidth - pl - pr;
        const hrLeft = (block.clientWidth - hrWidth) / 2;
        bottomHr.style.transition = 'none';
        bottomHr.style.top = blockExpandedTop(block) + 'px';
        bottomHr.style.left = hrLeft + 'px';
        bottomHr.style.width = hrWidth + 'px';
      });
    } else {
      setHr2ToHr1(block);
    }
  });

  // 年ボタンのクリックイベント
  document.querySelectorAll('[data-toggle-year]').forEach(btn => {
    btn.addEventListener('click', () => toggleYear(btn));
  });

  // 月ボタンのクリックイベント
  document.querySelectorAll('[data-toggle-month]').forEach(btn => {
    btn.addEventListener('click', () => toggleMonth(btn));
  });

  // scrollToYearMonth への自動展開・スクロール
  if (window.depositsConfig.scrollToYearMonth) {
    const targetYearMonth = window.depositsConfig.scrollToYearMonth;
    const targetYear = targetYearMonth.split('-')[0];
    const yearBtn = document.querySelector(`[data-toggle-year="year-${targetYear}"]`);

    if (yearBtn) {
      toggleYear(yearBtn);

      // 年展開(350ms) + 月自動展開(350ms) 完了後にスクロール
      setTimeout(() => {
        const targetSection = document.querySelector(`[data-year-month="${targetYearMonth}"]`);
        scrollToSection(targetSection);
        initialExpansionDone = true;
      }, 750);
    } else {
      initialExpansionDone = true;
    }
  } else {
    initialExpansionDone = true;
  }
});
