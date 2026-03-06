# Chapter 12: 応用機能 - タグ管理機能の実装

## 🎯 このセクションで学ぶこと

データベースとモデルの準備が整ったので、いよいよタグを実際に操作するための管理機能を実装します。このチャプターでは、タグの**C**reate（作成）、**U**pdate（更新）、**D**elete（削除）という、データ操作の基本となる3つの機能を、Laravelの伝統的なフォーム送信とリダイレクトのパターンで実装します。具体的には、`TagController`を作成し、`FormRequest`によるバリデーションを組み合わせることで、堅牢なタグ管理機能を構築する手法を学びます。タグの一覧表示（Read）は管理画面の`AdminController@index`で既に処理されているため、このチャプターではデータの変更操作に集中します。

## 1. はじめに 📖

### 伝統的なフォーム送信パターンとは？

管理画面のタグ管理機能は、Laravelの伝統的なフォーム送信パターンで実装します。これは、HTMLの`<form>`タグを使ってデータを送信し、サーバー側で処理した後にリダイレクトで画面を更新するという、Webアプリケーションの基本的な流れです。

この方式では、ユーザーがフォームを送信すると、ブラウザがサーバーにリクエストを送り、サーバーは処理完了後に`redirect()`でブラウザを管理画面に戻します。ページがリロードされることで、最新のデータが反映された画面が表示されます。LaravelのBladeテンプレートと組み合わせることで、シンプルかつ堅牢な管理機能を構築できます。

### CRUDとは？データ操作の基本原則

CRUDは、ほとんどのアプリケーションに共通する、永続的なデータを扱うための4つの基本的な操作を指す頭字語です。

-   **Create**: 新しいデータを作成する（例: 新しいタグを登録する）。
-   **Read**: 既存のデータを読み取る（例: タグの一覧を取得する）。
-   **Update**: 既存のデータを更新する（例: タグの名前を変更する）。
-   **Delete**: 既存のデータを削除する（例: タグを削除する）。

このCRUDという考え方は、コントローラー設計における基本となります。今回のタグ管理では、一覧表示（Read）は`AdminController`が担当し、`TagController`は残りの`store`（Create）、`update`（Update）、`destroy`（Delete）を担当するという役割分担になっています。

## 2. 要件の確認 📋

このチャプターで実装するタグ管理機能の具体的な仕様を整理します。

| 機能 | HTTPメソッド | URL | 処理内容 | バリデーション | 処理後の動作 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **新規作成 (Create)** | `POST` | `/admin/tags` | 新しいタグを作成する。 | `StoreTagRequest` (nameは必須、ユニーク) | `/admin` にリダイレクト |
| **更新 (Update)** | `PUT` | `/admin/tags/{tag}` | 指定されたIDのタグを更新する。 | `UpdateTagRequest` (nameは必須、ユニークだが自分自身は除外) | `/admin` にリダイレクト |
| **削除 (Delete)** | `DELETE` | `/admin/tags/{tag}` | 指定されたIDのタグを削除する。 | なし | `/admin` にリダイレクト |

**補足**: タグの一覧表示は`AdminController@index`で管理画面と一緒に処理されるため、`TagController`に`index`メソッドはありません。

## 3. 先輩エンジニアの思考プロセス 💭

タグ管理機能を設計する際、経験豊富なエンジニアはどのような設計判断を下すのでしょうか。

### Point 1: コントローラーの責務を明確に分ける

タグの一覧表示は管理画面全体のデータ取得と一緒に`AdminController`で行い、タグの作成・更新・削除という「データ変更操作」だけを`TagController`に任せます。このように、コントローラーごとに明確な責務を持たせることで、コードの見通しが良くなり、どこに何が書かれているかが一目瞭然になります。

### Point 2: バリデーションロジックは`FormRequest`に分離する

コントローラーのメソッド内でバリデーションロジックを書くこともできますが、これは責務の分離の原則に反します。コントローラーの責務は「リクエストを受け取り、処理して、リダイレクトする」ことであり、バリデーションという詳細なルールは別のクラスに任せるべきです。そこで`FormRequest`が登場します。`StoreTagRequest`や`UpdateTagRequest`のように、リクエストの種類ごとに専用のクラスを作成し、その中にバリデーションルールとエラーメッセージを記述します。これにより、コントローラーは驚くほどスリムになり、バリデーションルールを他の場所で再利用することも可能になります。また、`FormRequest`はバリデーションが失敗した場合、自動的にバリデーションエラーをセッションに保存して元のページにリダイレクトしてくれるため、コントローラーに余計なif文を書く必要がありません。

### Point 3: `Update`時のユニーク制約は「自分自身」を除外する

`Update`処理のバリデーションは、`Create`処理よりも少し複雑です。タグ名をユニークに保ちたい場合、`Create`時は単純に`unique:tags,name`というルールで問題ありません。しかし、`Update`時に同じルールを適用すると、「タグ名を変更しない」という操作ですら「その名前はすでに使われています」というバリデーションエラーになってしまいます。正しくは、「**更新対象のタグ自身のIDを除いて**、名前がユニークであること」を検証しなければなりません。これは、Laravelの`Rule`ファサードを使って`Rule::unique('tags', 'name')->ignore($tagId)`のように記述することで、エレガントに実現できます。

### Point 4: フォーム送信後はリダイレクトで画面を更新する

伝統的なWebアプリケーションでは、フォーム送信（POST/PUT/DELETE）の後にリダイレクトを行うのが定石です。これは「POST/Redirect/GET（PRG）パターン」と呼ばれ、ブラウザのリロードによるフォームの二重送信を防ぐための重要なテクニックです。Laravelでは`return redirect('/admin');`と書くだけで簡単に実現できます。

### Point 5: HTMLフォームで`PUT`や`DELETE`メソッドを使うには`@method`ディレクティブが必要

HTMLの`<form>`タグは、`GET`と`POST`の2つのHTTPメソッドしかサポートしていません。しかし、RESTfulなルーティングでは、更新に`PUT`、削除に`DELETE`を使いたい場面があります。Laravelでは、Bladeテンプレートの`@method('PUT')`や`@method('DELETE')`ディレクティブを使うことで、この制約を回避できます。内部的には、隠しフィールド`<input type="hidden" name="_method" value="PUT">`が生成され、Laravelのルーターがこれを検知して適切なルートにディスパッチしてくれます。また、CSRF保護のために`@csrf`ディレクティブも必ず含める必要があります。

## 4. 実装 🚀

タグ管理機能を実装するために必要なコンポーネントを一つずつ作成していきます。

### 4.1. `TagController`の作成

タグの作成・更新・削除を処理するコントローラーを生成します。

```bash
sail artisan make:controller TagController
```

生成された`app/Http/Controllers/TagController.php`を以下のように編集します。

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    public function store(StoreTagRequest $request)
    {
        Tag::create($request->validated());
        return redirect('/admin');
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());
        return redirect('/admin');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return redirect('/admin');
    }
}
```

### 4.2. `StoreTagRequest`の作成

タグの新規作成時のバリデーションルールを定義します。

```bash
sail artisan make:request StoreTagRequest
```

生成された`app/Http/Requests/StoreTagRequest.php`を編集します。

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ];
    }
}
```

### 4.3. `UpdateTagRequest`の作成

タグの更新時のバリデーションルールを定義します。

```bash
sail artisan make:request UpdateTagRequest
```

生成された`app/Http/Requests/UpdateTagRequest.php`を編集します。

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->ignore($tagId),
            ],
        ];
    }
}
```

### 4.4. ルーティングの追加

`routes/web.php`にタグ管理のルートを定義します。タグの操作は管理者のみが行えるべきなので、認証ミドルウェア（`auth`）の中に配置します。

-   **POST /admin/tags**: 新しいタグを作成する
-   **PUT /admin/tags/{tag}**: 指定したタグを更新する
-   **DELETE /admin/tags/{tag}**: 指定したタグを削除する

`routes/web.php`を開き、以下のハイライトされた部分を追記してください。

**`routes/web.php`**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TagController; // 追加

// ... 既存のルート ...

Route::middleware('auth')->group(function () {
    // 管理画面（タグの一覧表示もここで処理される）
    Route::get('/admin', [AdminController::class, 'index']);

    // --- ここから追記 ---
    // タグ登録
    Route::post('/admin/tags', [TagController::class, 'store']);
    // タグ更新
    Route::put('/admin/tags/{tag}', [TagController::class, 'update']);
    // タグ削除
    Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy']);
    // --- ここまで追記 ---
});
```

### 4.5. Bladeテンプレートでのフォーム実装

管理画面のBladeテンプレート（`admin/index.blade.php`）では、以下のようにフォームを使ってタグの操作を行います。

#### タグの新規作成フォーム

```html
<form action="/admin/tags" method="POST">
    @csrf
    <input type="text" name="name" placeholder="新しいタグ名">
    <button type="submit">追加</button>
</form>
```

`@csrf`ディレクティブは、CSRF（Cross-Site Request Forgery）攻撃を防ぐためのトークンを隠しフィールドとして生成します。Laravelでは、すべてのPOST/PUT/DELETEリクエストにこのトークンが必須です。

#### タグの更新フォーム

```html
<form action="/admin/tags/{{ $tag->id }}" method="POST">
    @csrf
    @method('PUT')
    <input type="text" name="name" value="{{ $tag->name }}">
    <button type="submit">更新</button>
</form>
```

HTMLフォームは`GET`と`POST`しかサポートしていないため、`@method('PUT')`ディレクティブを使って`PUT`メソッドを擬似的に実現しています。これにより、Laravelのルーターが`Route::put()`で定義されたルートにリクエストをマッチさせることができます。

#### タグの削除フォーム

```html
<form action="/admin/tags/{{ $tag->id }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="submit">削除</button>
</form>
```

削除も同様に、`@method('DELETE')`を使って`DELETE`メソッドを擬似的に送信します。

## 5. コードの詳細解説 🔍

### `app/Http/Controllers/TagController.php` の解説

- **`store(StoreTagRequest $request)`**: メソッドの引数で`StoreTagRequest`をタイプヒントしています。これにより、メソッドの本体が実行される前に自動的にバリデーションが行われます。バリデーションに失敗した場合、Laravelは自動的にエラーメッセージをセッションに保存し、元のページ（管理画面）にリダイレクトします。`$request->validated()`で、バリデーション済みの安全なデータのみを取得し、`Tag::create()`に渡して新しいタグを作成します。作成後は`redirect('/admin')`で管理画面に戻ります。
- **`update(UpdateTagRequest $request, Tag $tag)`**: ここでは「ルートモデルバインディング」という機能が使われています。URLの`{tag}`の部分にあるIDに一致する`Tag`モデルのインスタンスを、Laravelが自動的に検索して`$tag`変数に注入してくれます。これにより、`Tag::findOrFail($id)`のようなコードを書く必要がなくなります。バリデーション通過後、`$tag->update()`でデータを更新し、管理画面にリダイレクトします。
- **`destroy(Tag $tag)`**: `update`と同様にルートモデルバインディングが機能し、取得した`$tag`モデルインスタンスに対して`delete()`メソッドを呼び出すだけで削除が完了します。削除後も管理画面にリダイレクトします。
- **`redirect('/admin')`について**: すべてのメソッドが処理後に`redirect('/admin')`を返しています。これはPRG（Post/Redirect/Get）パターンと呼ばれ、ブラウザの「戻る」ボタンやリロードによるフォームの二重送信を防ぐための重要なテクニックです。

### `app/Http/Requests/UpdateTagRequest.php` の解説

- **`$tagId = $this->route('tag')?->id;`**: ルートモデルバインディングで注入された`tag`オブジェクトのIDを取得しています。`?->`はPHP 8のNullsafe演算子で、`$this->route('tag')`が`null`の場合にエラーを起こさず`null`を返します。
- **`Rule::unique('tags', 'name')->ignore($tagId)`**: `Rule`ファサードを使ったユニーク制約の定義です。`tags`テーブルの`name`カラムがユニークであることを検証しますが、`ignore($tagId)`によって、指定されたID（つまり、今更新しようとしているタグ自身のID）を持つレコードは検証の対象外となります。

### 💡 コラム: `@csrf`と`@method`の仕組み

Bladeテンプレートで使う`@csrf`と`@method`は、それぞれ以下のような隠しフィールドを生成します。

```html
<!-- @csrf が生成するもの -->
<input type="hidden" name="_token" value="ランダムなトークン文字列">

<!-- @method('PUT') が生成するもの -->
<input type="hidden" name="_method" value="PUT">
```

`_token`フィールドは、リクエストが自分のアプリケーションのフォームから送信されたものであることを検証するために使われます。外部サイトからの不正なリクエスト（CSRF攻撃）を防ぐ重要なセキュリティ機構です。

`_method`フィールドは、HTMLフォームの制約（GETとPOSTのみ）を回避するために使われます。Laravelのルーターはこのフィールドを検知し、実際のHTTPメソッドとして扱います。これにより、RESTfulなルーティング（PUT、DELETE等）をフォーム送信で利用できるようになります。

## 6. How to: この実装にたどり着くための調べ方 🗺️

### エンジニアが新しい技術を学ぶ4ステップ

実務では、未知の技術やライブラリを使いこなす能力が求められます。ここでは、Laravelの`FormRequest`やPRGパターンを学ぶというシナリオで、効率的な学習の4ステップを見ていきましょう。

#### Step 1: 公式ドキュメントを読みやすくまとめる

まずは公式ドキュメントを読んで全体像を掴みます。しかし、ドキュメントは情報量が多いことも。AIに要点をまとめてもらい、学習の地図を手に入れましょう。

**プロンプト例**
```
以下はLaravelの「FormRequest」に関する公式ドキュメントの一部です。 これを「実装できるように」分かりやすくまとめてください。

出力してほしい内容：
- 重要ポイント（10行以内）
- 用語の説明（重要なものだけ）
- できること / できないこと（境界をはっきり）
- よくある落とし穴（回避策つき）
- 最小で動かすための手順（コードはまだ不要）

--- ここから ---
（ここに公式ドキュメントの該当部分を貼り付ける）
--- ここまで ---
```

**AIから得られること**
- 「`FormRequest`はバリデーション失敗時に自動でリダイレクトしてくれる」といった核心的な情報
- 「`authorize()`」「`validated()`」といった基本メソッドの解説
- `@csrf`や`@method`ディレクティブの使い方
- 学習の全体像を素早く掴む

#### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

次に、技術の「なぜ」を深掘りします。自分の理解が正しいか、AIに壁打ち相手になってもらいましょう。

**プロンプト例**
```
Laravelの「ルートモデルバインディング」について、私の理解はこうです：
「URLのID（例: /admin/tags/1）を元に、Laravelが自動で対応するモデル（TagモデルのID=1）を見つけて、コントローラーのメソッドに渡してくれる仕組み」

お願い：
1) 正しいかチェックして、間違いがあれば「反例」で教えてください
2) 仕組みを「入力→中で起きること→出力」で説明してください
3) どこまでがこの概念の範囲か（境界）を教えてください
4) よくある勘違いを3つ教えてください
5) 理解チェック問題を3問ください（答えつき）
```

**AIから得られること**
- 自分の理解の正誤と、より正確な定義
- 「裏側で何が起きているか」という仕組みの理解
- 「カスタムキーでのバインディングも可能」といった応用知識
- 知識の定着度を確認するクイズ

#### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

概念を理解したら、いよいよ実装です。AIに実装のレシピを作ってもらい、それに沿って手を動かします。

**プロンプト例**
```
目的は「Laravelでタグを新規作成する機能（POST /admin/tags）の実装」です。
制約は「バリデーションはFormRequestで行い、処理後はリダイレクトする」です。
前提知識は「PHPのクラスとメソッドは理解している」です。

次の順番で出力してください：

A. 実装の手順・方針
- まず全体の方針（なぜそのやり方か）
- 手順を1〜Nで（各手順に「できたらOK」の条件も書く）

B. 関連技術の解説
- 必要な関連知識を3〜7個
- 各項目は「一言で説明 → この実装で何に使う → 注意点」

C. 実装例
- まず最小で動く例
- 次に実務向けの拡張例（エラー処理/フラッシュメッセージ/設定など）

D. コードの解説
- 重要な部分だけ「何をしてるか」「なぜそう書くか」
- よくあるバグと対策

追加で必要な情報があれば質問していいですが、最大3つまでにしてください。
```

**AIから得られること**
- 体系化された実装手順
- `FormRequest`, `Controller`, `Route`, `Blade`の連携方法
- 最小構成のコードと、より実践的なコードの比較
- 段階的に実装の解像度を上げる

#### Step 4: 設計レビュー（指摘をもらう）

最後に、自分の設計やコードをAIにレビューしてもらい、客観的な視点を取り入れます。

**プロンプト例**
```
以下のタグ管理機能の設計をレビューしてください。

- 目的：管理画面でのタグ管理機能
- 要件：タグの作成・更新・削除ができること
- 制約：Laravel 10, PHP 8.2, 伝統的なフォーム送信パターン
- 設計案：
  - POST /admin/tags (作成) → redirect('/admin')
  - PUT /admin/tags/{tag} (更新) → redirect('/admin')
  - DELETE /admin/tags/{tag} (削除) → redirect('/admin')
- 不安な点：
  - PRGパターンは正しく実装できているか？
  - バリデーションエラー時のユーザー体験は問題ないか？

見てほしい観点：
- RESTの原則に沿っているか
- セキュリティ（CSRF対策など）
- ユーザー体験（エラー表示、フラッシュメッセージなど）
- 変更しやすいか（拡張/分離）

出力：
- 指摘を「重要度：高/中/低」で出す
- 各指摘に「理由」「影響」「直し方」をつける
- 最後に「この設計が失敗しやすい例」を3つ出す
```

**AIから得られること**
- 設計の抜け漏れや改善点の発見
- フラッシュメッセージの追加提案など、UX改善のヒント
- CSRF保護が正しく機能しているかの確認
- セキュリティリスクの指摘

## 7. まとめ ✨

このチャプターでは、タグを管理するための作成・更新・削除機能を、Laravelの伝統的なフォーム送信パターンで実装しました。

-   **コントローラーの責務分離**: タグの一覧表示は`AdminController`に任せ、`TagController`はデータ変更操作（作成・更新・削除）に集中させました。
-   **FormRequestによるバリデーション分離**: `StoreTagRequest`と`UpdateTagRequest`を作成し、バリデーションロジックをコントローラーから分離しました。
-   **更新時のユニーク制約**: `Rule::unique()->ignore()`を使い、更新時に自分自身のIDをユニーク制約の対象から除外する方法を学びました。
-   **PRGパターン**: すべてのデータ変更操作の後に`redirect('/admin')`でリダイレクトすることで、フォームの二重送信を防ぐPRGパターンを実践しました。
-   **`@csrf`と`@method`ディレクティブ**: Bladeテンプレートでフォームを作成する際に、CSRF保護トークンの自動生成と、PUT/DELETEメソッドの擬似送信を実現する方法を学びました。
-   **認証ミドルウェアによる保護**: `routes/web.php`で`auth`ミドルウェアを適用し、ログイン済みユーザーのみがタグの操作を行えるようにしました。

これで、管理画面からタグを自由に操作するための機能が整いました。次のチャプターでは、お問い合わせを登録する際にタグを一緒に紐付けられるように、既存の機能を改修していきます。
