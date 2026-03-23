<?php
//-- app/Http/Controllers/NoticesController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\NoticeRead;
use Illuminate\Support\Facades\Auth;

class NoticesController extends Controller
{
  /**
   * お知らせ一覧を表示
   */
  public function index()
  {
    $notices = Notice::orderBy('id', 'desc')->get();

    return view('admin-panel.notices.notices_index', [
      'notices' => $notices,
      'page_header_title' => 'お知らせ',
    ]);
  }

  /**
   * 新規作成フォームを表示
   */
  public function create()
  {
    return view('admin-panel.notices.notices_form', [
      'mode' => 'create',
      'page_header_title' => 'お知らせ‐登録 (新規)',
      'notice' => null,
    ]);
  }

  /**
   * 新規作成：データ保存
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'title'   => 'required|string|max:255',
      'content' => 'required|string',
    ]);

    Notice::create($validated);

    return redirect()->route('notices.index')
      ->with('success', 'お知らせを登録しました。');
  }

  /**
   * 編集フォームを表示
   */
  public function edit($id)
  {
    $notice = Notice::findOrFail($id);

    return view('admin-panel.notices.notices_form', [
      'mode' => 'edit',
      'page_header_title' => 'お知らせ‐登録 (編集)',
      'notice' => $notice,
    ]);
  }

  /**
   * 編集：データ更新
   */
  public function update(Request $request, $id)
  {
    $validated = $request->validate([
      'title'   => 'required|string|max:255',
      'content' => 'required|string',
    ]);

    $notice = Notice::findOrFail($id);
    $notice->fill($validated);
    $notice->save();

    return redirect()->route('notices.index')
      ->with('success', 'お知らせを更新しました。');
  }

  /**
   * 複製フォームを表示
   */
  public function duplicate($id)
  {
    $notice = Notice::findOrFail($id);

    return view('admin-panel.notices.notices_form', [
      'mode' => 'duplicate',
      'page_header_title' => 'お知らせ‐登録 (複製)',
      'notice' => $notice,
    ]);
  }

  /**
   * 複製：データ保存
   */
  public function duplicateStore(Request $request)
  {
    $validated = $request->validate([
      'title'   => 'required|string|max:255',
      'content' => 'required|string',
    ]);

    Notice::create($validated);

    return redirect()->route('notices.index')
      ->with('success', 'お知らせを複製しました。');
  }

  /**
   * 削除処理
   */
  public function destroy($id)
  {
    $notice = Notice::findOrFail($id);
    $notice->delete();

    return redirect()->route('notices.index')
      ->with('success', 'お知らせを削除しました。');
  }

  // ─── ヘッダー通知API ─────────────────────────────────

  /**
   * 通知一覧をJSON返却（ヘッダー用）
   */
  public function apiList()
  {
    $userId = Auth::id();
    $notices = Notice::orderBy('id', 'desc')->get();

    $data = $notices->map(function ($notice) use ($userId) {
      return [
        'id'         => $notice->id,
        'title'      => $notice->title,
        'content'    => $notice->content,
        'created_at' => $notice->created_at->format('Y/m/d'),
        'is_read'    => $notice->isReadBy($userId),
      ];
    });

    $unread_count = $data->filter(fn($n) => !$n['is_read'])->count();

    return response()->json([
      'notices'      => $data,
      'unread_count' => $unread_count,
    ]);
  }

  /**
   * 既読をトグル（既読→未読 / 未読→既読）
   */
  public function apiToggleRead(Request $request, $id)
  {
    $userId = Auth::id();
    $notice = Notice::findOrFail($id);

    $existing = NoticeRead::where('user_id', $userId)
      ->where('notice_id', $id)
      ->first();

    if ($existing) {
      $existing->delete();
      $is_read = false;
    } else {
      NoticeRead::create(['user_id' => $userId, 'notice_id' => $id]);
      $is_read = true;
    }

    $total        = Notice::orderBy('id', 'desc')->get()->count();
    $read_count   = NoticeRead::where('user_id', $userId)->count();
    $unread_count = max(0, $total - $read_count);

    return response()->json([
      'is_read'      => $is_read,
      'unread_count' => $unread_count,
    ]);
  }
}
