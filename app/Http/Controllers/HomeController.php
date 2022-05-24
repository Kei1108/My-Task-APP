<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Memo;
use App\Tag;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // ログインしているユーザー情報を取得
        $user = \Auth::user();

        // DBからログインユーザーと同じidが振られているメモのみを取得
        // ASC = 昇順（古い順） / DESC = 降順（新しい順）
        $memos = Memo::where('user_id', $user['id'])->where('status', 1)->orderBy('updated_at', 'DESC')->get();
        // dd($memos);
        return view('create', compact('user', 'memos'));
    }

    public function create()
    {
        // ログインしているユーザー情報を取得しViewに渡す
        $user = \Auth::user();
        $memos = Memo::where('user_id', $user['id'])->where('status', 1)->orderBy('updated_at', 'DESC')->get();
        return view('create', compact('user', 'memos'));
    }

    public function store(Request $request)
    {
        // Requestを使用することで、ブラウザを通してユーザーから送られる情報オブジェクトを取得できる
        $data = $request->all();
        // dd($data);

        // 同ユーザーで同タグが記録されていないかの確認
        $exist_tag = Tag::where('name', $data['tag'])->where('user_id', $data['user_id'])->first();
        // dd($exist_tag);
        if(empty($exist_tag)) {
            // タグを先にインサート
            $tag_id = Tag::insertGetId( ['name' => $data['tag'], 'user_id' => $data['user_id']] );
            // dd($tag_id);
        } else {
            $tag_id = $exist_tag['id'];
        }

        // POSTされたデータをDB（memos table）に挿入する
        // テーブルが自動増分IDを持っている場合、insertGetIdメソッドを使うことでレコードを挿入し、そのレコードのIDを返してくれる
        $memo_id = Memo::insertGetId
        ([
            'content' => $data['content'], 
            'user_id' => $data['user_id'], 
            'tag_id' => $tag_id,
            'status' => 1 
        ]);
        return redirect()->route('home');
    }

    public function edit($id)
    {
        // 選択したメモのidをDBから取得
        // 条件、status->1 / id->$id（URLパラメーター） / user_id->$user['id']に一致するもの
        $user = \Auth::user();
        $memo = Memo::where('status', 1)->where('id', $id)->where('user_id', $user['id'])->first();
        
        // メモ一覧に出力するために$memosの取得が必要
        $memos = Memo::where('user_id', $user['id'])->where('status', 1)->orderBy('updated_at', 'DESC')->get();
        // dd($memo);

        // タグテーブルから、ユーザーidが一致するものを全て取得
        $tags = Tag::where('user_id', $user['id'])->get();

        return view('edit', compact('user', 'memo', 'memos', 'tags'));
    }

    public function update(Request $request, $id)
    {
        // フォームの内容を取得
        $inputs = $request->all();
        // dd($inputs);

        Memo::where('id', $id)->update( ['content' => $inputs['content'], 'tag_id' => $inputs['tag_id']] );

        return redirect()->route('home');
    }

    public function delete(Request $request, $id)
    {
        // フォームの内容を取得
        $inputs = $request->all();
        // dd($inputs);

        // ステータスを切り替え、論理削除を行う
        Memo::where('id', $id)->update( ['status'=> 2] );

        return redirect()->route('home')->with('success', 'メモの削除が完了しました。');
    }
}
