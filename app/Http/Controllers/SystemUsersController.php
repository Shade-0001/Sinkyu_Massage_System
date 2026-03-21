<?php
//-- app/Http/Controllers/SystemUsersController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemUser;
use Illuminate\Support\Facades\Hash;

class SystemUsersController extends Controller
{
  /**
   * システムユーザー一覧を表示
   */
  public function index()
  {
    $systemUsers = SystemUser::orderBy('id', 'asc')->get();

    return view('admin-panel.system-users.system-users_index', [
      'systemUsers' => $systemUsers,
      'page_header_title' => 'システムユーザー',
    ]);
  }

  /**
   * 新規作成フォームを表示
   */
  public function create()
  {
    return view('admin-panel.system-users.system-users_form', [
      'mode' => 'create',
      'page_header_title' => 'システムユーザー‐登録 (新規)',
      'systemUser' => null,
    ]);
  }

  /**
   * 新規作成：データ保存
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'           => 'required|string|max:255',
      'login_id'       => 'required|string|max:255|unique:system_users,login_id',
      'plain_password' => 'required|string|min:4|max:255',
      'is_admin'       => 'required|in:0,1',
    ]);

    SystemUser::create([
      'name'           => $validated['name'],
      'login_id'       => $validated['login_id'],
      'password'       => Hash::make($validated['plain_password']),
      'plain_password' => $validated['plain_password'],
      'is_admin'       => $validated['is_admin'],
    ]);

    return redirect()->route('system-users.index')
      ->with('success', 'システムユーザーを登録しました。');
  }

  /**
   * 編集前パスワード検証（Ajax）
   */
  public function verifyPassword(Request $request)
  {
    $editUrl  = $request->input('edit_url', '');
    $password = $request->input('password', '');

    // edit_url から id を抽出
    if (!preg_match('#/admin-panel/system-users/(\d+)/edit$#', $editUrl, $m)) {
      return response()->json(['success' => false]);
    }

    $systemUser = SystemUser::find((int) $m[1]);
    if (!$systemUser) {
      return response()->json(['success' => false]);
    }

    if ($password !== $systemUser->plain_password) {
      return response()->json(['success' => false]);
    }

    return response()->json(['success' => true, 'redirect' => $editUrl]);
  }

  /**
   * 編集フォームを表示
   */
  public function edit($id)
  {
    $systemUser = SystemUser::findOrFail($id);

    return view('admin-panel.system-users.system-users_form', [
      'mode' => 'edit',
      'page_header_title' => 'システムユーザー‐登録 (編集)',
      'systemUser' => $systemUser,
    ]);
  }

  /**
   * 編集：データ更新
   */
  public function update(Request $request, $id)
  {
    $systemUser = SystemUser::findOrFail($id);

    $validated = $request->validate([
      'name'           => 'required|string|max:255',
      'login_id'       => 'required|string|max:255|unique:system_users,login_id,' . $id,
      'plain_password' => 'required|string|min:4|max:255',
      'is_admin'       => 'required|in:0,1',
    ]);

    $systemUser->update([
      'name'           => $validated['name'],
      'login_id'       => $validated['login_id'],
      'password'       => Hash::make($validated['plain_password']),
      'plain_password' => $validated['plain_password'],
      'is_admin'       => $validated['is_admin'],
    ]);

    return redirect()->route('system-users.index')
      ->with('success', 'システムユーザーを更新しました。');
  }
}
