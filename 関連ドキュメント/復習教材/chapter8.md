# Chapter 8: アプリケーションの司令塔 - コントローラーでビジネスロジックを操る

## このセクションで学ぶこと

このチャプターでは、アプリケーションの心臓部であり、ユーザーからのリクエストを処理し、適切なレスポンスを返す「司令塔」の役割を担う**コントローラー**の実装に焦点を当てます。これまでに作成したモデル、FormRequestといった部品をここで初めて組み合わせ、リクエストの受付、バリデーション、データ処理、レスポンス生成という一連のビジネスロジックを組み立てる方法を学びます。

## 1. はじめに

### コントローラーとは？リクエストとレスポンスの交通整理役

Laravelにおけるコントローラーは、ルーティングから受け取ったHTTPリクエストを処理するためのクラスです。ユーザーが特定のURLにアクセスすると、ルーターはそのリクエストを対応するコントローラーのメソッドに引き渡します。コントローラーのメソッドは、そのリクエストに応じて以下のようなタスクを実行します。

1.  **リクエストデータの受け取り**: フォームから送信された入力値や、URLのパラメータを取得します。
2.  **バリデーションの実行**: 受け取ったデータが正しい形式か、`FormRequest`を使って検証します。
3.  **ビジネスロジックの実行**: モデルを使ってデータベースを操作（取得、作成、更新、削除）したり、他のサービスクラスを呼び出したりします。
4.  **レスポンスの生成**: 処理結果をBladeビューに渡してHTMLを生成したり、リダイレクトで別のページに誘導したりして、ユーザーに返却します。

コントローラーは、モデル（データ）、ビュー（表示）、そしてユーザーからのリクエストという、アプリケーションの異なる関心事を仲介する重要な役割を担っています。ビジネスロジックをコントローラーに集約することで、ルーティングファイルはスッキリと保たれ、アプリケーションのどこで何が行われているかが見通しやすくなります。これを「関心の分離」と呼び、メンテナンス性の高いアプリケーションを構築するための基本原則です。

## 2. 要件の確認

このアプリケーションで必要となるコントローラーの機能と、それぞれの役割を整理します。

| コントローラーファイル | メソッド | HTTPメソッド | URI | 役割 |
| :--- | :--- | :--- | :--- | :--- |
| `ContactController` | `index` | GET | `/` | お問い合わせフォーム画面を表示する（カテゴリーとタグをビューに渡す） |
| | `confirm` | POST | `/contacts/confirm` | バリデーション済みデータで確認画面を表示する |
| | `store` | POST | `/contacts` | お問い合わせ情報を保存し、完了画面へリダイレクトする |
| | `thanks` | GET | `/thanks` | 送信完了画面を表示する |
| | `export` | GET | `/contacts/export` | 検索条件に合致するお問い合わせをCSVエクスポートする |
| `AdminController` | `index` | GET | `/admin` | 管理画面を表示する（検索・ページネーション付き） |
| | `show` | GET | `/admin/contacts/{contact}` | お問い合わせの詳細画面を表示する |
| | `destroy` | DELETE | `/admin/contacts/{contact}` | お問い合わせを削除し、管理画面へリダイレクトする |
| `TagController` | `store` | POST | `/admin/tags` | 新しいタグを作成し、管理画面へリダイレクトする |
| | `update` | PUT | `/admin/tags/{tag}` | タグを更新し、管理画面へリダイレクトする |
| | `destroy` | DELETE | `/admin/tags/{tag}` | タグを削除し、管理画面へリダイレクトする |

**ポイント**:
- **すべてのコントローラーがサーバーサイドレンダリング（SSR）**: このアプリケーションはAPI方式ではなく、従来のLaravel SSR方式を採用しています。コントローラーは`view()`でBladeテンプレートを返すか、`redirect()`で別のURLに転送します。JSONレスポンスは使用しません。
- **役割ごとの分離**: `ContactController`はエンドユーザー向けのお問い合わせフォーム、`AdminController`は管理者向けのお問い合わせ管理、`TagController`はタグのCRUD操作と、それぞれが異なる関心事を担当しています。

## 3. 先輩エンジニアの思考プロセス

なぜ複数のコントローラーに分けるのか？それぞれの責務をどう整理するのか？その設計思想に迫ります。

### Point 1: 1つのコントローラーには、1つの関心事を

Laravelでは、1つのコントローラーにすべてのロジックを詰め込むことも技術的には可能です。しかし、それは「Fat Controller（太ったコントローラー）」と呼ばれ、アンチパターンとされています。

このアプリケーションでは、3つのコントローラーがそれぞれ明確な責務を持っています。

- **`ContactController`**: エンドユーザー向けのお問い合わせフォームに関する処理（フォーム表示、確認、保存、完了画面、CSVエクスポート）
- **`AdminController`**: 管理者向けのお問い合わせ管理に関する処理（一覧表示・検索、詳細表示、削除）
- **`TagController`**: タグのマスタデータ管理に関する処理（作成、更新、削除）

このように、それぞれのコントローラーが担当する「関心事」を明確に分離することが重要です。これにより、コードが読みやすくなり、変更が必要になった際に影響範囲を特定しやすくなります。これは、ソフトウェア設計における**単一責任の原則**の実践です。

### Point 2: 対象ユーザーと操作対象でコントローラーを分離する

このアプリケーションでは、同じ`Contact`モデルを扱うメソッドであっても、対象ユーザーや操作の文脈に応じてコントローラーを分けています。

- **`ContactController`**: エンドユーザーが自分でお問い合わせを送信するフロー。`index`でフォームを表示し、`confirm`で確認画面を見せ、`store`で保存するという一連の画面遷移を管理します。
- **`AdminController`**: 管理者がお問い合わせを管理するフロー。検索・ページネーション付きの一覧、詳細表示、削除という管理操作を担当します。
- **`TagController`**: タグという別のリソースの操作。`Contact`ではなく`Tag`モデルを扱うため、独立したコントローラーにしています。

このように「誰が」「何を」操作するのかという軸でコントローラーを分けることで、各コントローラーの見通しがよくなり、ミドルウェアの適用（例: 管理画面には`auth`ミドルウェアを適用）もグループごとに管理しやすくなります。

### Point 3: メソッドインジェクションで、必要なものを自動で受け取る

Laravelの強力な機能の一つに「メソッドインジェクション」があります。コントローラーのメソッドの引数に、`StoreContactRequest $request`や`Contact $contact`のようにクラス名を指定するだけで、Laravelのサービスコンテナが自動的にそのクラスのインスタンスを生成または解決して、メソッドに渡してくれます。

- **`StoreContactRequest $request`**: `FormRequest`をインジェクトすると、メソッドの本体が実行される**前**に、自動でバリデーションが実行されます。バリデーションが失敗すれば、例外がスローされ、ユーザーはエラーメッセージと共に前のページにリダイレクトされます。これにより、コントローラー本体にはバリデーションが成功した場合のロジックだけを書けばよくなり、コードが非常にクリーンになります。
- **`Contact $contact`**: ルート定義で`{contact}`のようにモデル名と同じパラメータ名を使うと、「ルートモデルバインディング」が機能します。URLに含まれるID（例: `/admin/contacts/123`の`123`）を使って、自動的に`Contact`モデルをデータベースから検索し、インスタンスをインジェクトしてくれます。もし該当するモデルが見つからなければ、自動的に404 Not Foundレスポンスが返されます。これにより、`$contact = Contact::findOrFail($id);`のような定型コードを書く必要がなくなります。

これらの機能を最大限に活用することで、コントローラーのコードを驚くほどシンプルで宣言的に記述することができます。

## 4. 実装

それでは、3つのコントローラーを作成し、それぞれのビジネスロジックを実装していきましょう。

### 4.1. コントローラーファイルの作成

以下の`sail artisan make:controller`コマンドで、必要なコントローラーファイルを生成します。

```bash
sail artisan make:controller ContactController
sail artisan make:controller AdminController
sail artisan make:controller TagController
```

### 4.2. `ContactController.php`の実装

このコントローラーは、エンドユーザー向けのお問い合わせフォームの一連のフローを担当します。フォーム表示、確認画面、保存処理、完了画面、そしてCSVエクスポート機能を提供します。

`app/Http/Controllers/ContactController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $category = Category::find($validated['category_id']);
        $tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);

        if (! empty($tagIds)) {
            $contact->tags()->attach($tagIds);
        }

        return redirect('/thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request)
    {
        $query = Contact::with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->get();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($contacts as $contact) {
                $genderText = match ($contact->gender) {
                    1 => '男性',
                    2 => '女性',
                    3 => 'その他',
                    default => '',
                };
                fputcsv($handle, [
                    $contact->id,
                    $contact->last_name.' '.$contact->first_name,
                    $genderText,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building ?? '',
                    $contact->category->content ?? '',
                    $contact->detail,
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'contacts_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
```

### 4.3. `AdminController.php`の実装

このコントローラーは、管理者向けのお問い合わせ管理機能を担当します。検索・ページネーション付きの一覧表示、詳細表示、削除を提供します。`auth`ミドルウェアにより、認証済みのユーザーしかアクセスできません。

`app/Http/Controllers/AdminController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->paginate(7);
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }
}
```

### 4.4. `TagController.php`の実装

このコントローラーは、タグのマスタデータ管理を担当します。タグの作成、更新、削除を行い、すべての操作後に管理画面へリダイレクトします。

`app/Http/Controllers/TagController.php`
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

## 5. コードの詳細解説

各コントローラーのメソッドを詳しく見ていきましょう。

### `ContactController` - お問い合わせフォームのフロー

#### `index`メソッド（フォーム表示）

- **`$categories = Category::all();`** と **`$tags = Tag::all();`**: フォームのセレクトボックスやチェックボックスに表示するための選択肢データを、データベースから全件取得します。
- **`return view('contact.index', compact('categories', 'tags'));`**: `view()`ヘルパーで`resources/views/contact/index.blade.php`テンプレートを返します。`compact()`関数で、変数名をキーとして連想配列に変換し、Bladeテンプレートに渡しています。テンプレート内では`$categories`や`$tags`としてアクセスできます。

#### `confirm`メソッド（確認画面表示）

- **`public function confirm(StoreContactRequest $request)`**: `StoreContactRequest`をインジェクトすることで、フォームから送信されたデータのバリデーションが自動で実行されます。バリデーションに失敗すると、ユーザーはエラーメッセージと共にフォーム画面にリダイレクトされます。
- **`$validated = $request->validated();`**: バリデーション済みのデータだけを連想配列として取得します。バリデーションルールに定義されていないフィールドは含まれないため、安全です。
- **`$category = Category::find($validated['category_id']);`**: 選択されたカテゴリーの名称を確認画面に表示するために、カテゴリーモデルを取得しています。
- **`$tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();`**: 選択されたタグが存在する場合はIDの配列で一括取得し、存在しない場合は空のコレクションを返します。`collect()`はLaravelの空コレクションを生成するヘルパーで、ビュー側で常に同じ型（コレクション）として扱えるようにしています。
- **`return view('contact.confirm', compact('validated', 'category', 'tags'));`**: 確認画面のBladeテンプレートにバリデーション済みデータ、カテゴリー、タグを渡して表示します。

#### `store`メソッド（保存処理）

- **`$tagIds = $validated['tag_ids'] ?? [];`**: タグIDの配列を取り出します。`??`（Null合体演算子）で、タグが未選択の場合は空配列をデフォルトにしています。
- **`unset($validated['tag_ids']);`**: `tag_ids`は`contacts`テーブルのカラムではなく、中間テーブルで管理するため、`$validated`配列から除外します。これをしないと`Contact::create()`で不正なカラムとしてエラーになります。
- **`$contact = Contact::create($validated);`**: バリデーション済みのデータで新しい`Contact`レコードを作成します。`create`メソッドを使うには、`Contact`モデル側で`$fillable`プロパティが正しく設定されている必要があります。
- **`$contact->tags()->attach($tagIds);`**: 多対多リレーションの中間テーブルにタグを紐付けます。`attach()`メソッドはIDの配列を受け取り、中間テーブルにレコードを挿入します。
- **`return redirect('/thanks');`**: 保存が完了したら、`redirect()`ヘルパーで完了画面（`/thanks`）にリダイレクトします。これはPRG（Post/Redirect/Get）パターンと呼ばれ、フォームの二重送信を防ぐための定石です。

#### `thanks`メソッド（完了画面表示）

- **`return view('contact.thanks');`**: 送信完了画面のBladeテンプレートをそのまま返します。データの受け渡しがない最もシンプルな形です。

#### `export`メソッド（CSVエクスポート）

- **検索クエリの構築**: `AdminController`の`index`メソッドと同様に、検索条件を動的にクエリに追加しています。管理画面で表示している検索結果と同じ条件でCSVを出力するためです。
- **`$contacts = $query->latest()->get();`**: ページネーションではなく`get()`で全件取得します。CSVエクスポートでは全データが必要なためです。
- **`return response()->streamDownload(...)`**: `streamDownload`メソッドは、大量のデータを扱う際にメモリ効率がよい方法です。ファイルを一度メモリに全て載せるのではなく、出力ストリームに直接書き込みます。
- **`fwrite($handle, "\xEF\xBB\xBF");`**: BOM（Byte Order Mark）を出力の先頭に追加しています。これにより、ExcelでCSVを開いた際に日本語が文字化けしないようにしています。
- **`match ($contact->gender) { ... }`**: PHP 8の`match`式で、数値の性別コードを日本語の表示用テキストに変換しています。

### `AdminController` - 管理画面のお問い合わせ管理

#### `index`メソッド（一覧表示・検索）

- **`public function index(IndexContactRequest $request)`**: `IndexContactRequest`をインジェクトし、検索パラメータのバリデーションを自動で行います。
- **`$query = Contact::with(['category', 'tags']);`**: クエリビルダのインスタンスを生成します。`with(['category', 'tags'])`で、`Contact`を取得する際に`Category`モデルと`Tag`モデルも同時に取得（Eager Loading）し、N+1問題を回避しています。
- **`if ($request->filled('keyword')) { ... }`**: `keyword`パラメータが存在し、かつ空文字でない場合のみ、検索処理を実行します。`filled()`は`has()`と異なり、値が空文字の場合は`false`を返します。
- **`$query->where(function ($q) use ($keyword) { ... });`**: 複数の`orWhere`をグループ化するためのクロージャです。これを使わないと、他の`where`条件との組み合わせが意図しない結果になる可能性があります。例えば、`WHERE gender = 1 OR first_name LIKE '%田中%'`ではなく、`WHERE gender = 1 AND (first_name LIKE '%田中%' OR last_name LIKE '%田中%' OR email LIKE '%田中%')`という正しいSQL条件を生成するために必要です。
- **`->orWhere('first_name', 'like', "%{$keyword}%")`**: `LIKE`句を使って部分一致検索を行っています。
- **`if ($request->filled('gender') && $request->gender != 0)`**: `gender`が`0`（すべて）でない場合にのみ、絞り込みを行います。
- **`$query->whereDate('created_at', $request->date);`**: `whereDate`メソッドで、`created_at`の日付部分だけで検索します。
- **`$contacts = $query->latest()->paginate(7);`**: これまで組み立てたクエリを実行します。`latest()`は`orderBy('created_at', 'desc')`のショートカットです。`paginate(7)`で、7件ごとにページ分割した結果を取得します。
- **`$categories = Category::all();`** と **`$tags = Tag::all();`**: 検索フォームのセレクトボックスやタグ管理に使うデータも取得します。
- **`return view('admin.index', compact('contacts', 'categories', 'tags'));`**: 検索結果とフォーム用データをBladeテンプレートに渡してHTMLを返します。

#### `show`メソッド（詳細表示）

- **`public function show(Contact $contact)`**: ルートモデルバインディングにより、URLのIDに対応する`Contact`モデルが自動的にインジェクトされます。
- **`$contact->load(['category', 'tags']);`**: 既に取得済みのモデルに対してリレーションを後から読み込む（Lazy Eager Loading）メソッドです。詳細画面でカテゴリー名やタグ名を表示するために必要です。
- **`return view('admin.show', compact('contact'));`**: 詳細画面のBladeテンプレートにお問い合わせ情報を渡して表示します。

#### `destroy`メソッド（削除）

- **`public function destroy(Contact $contact)`**: `show`メソッドと同様に、削除対象の`Contact`モデルがルートモデルバインディングでインジェクトされます。
- **`$contact->delete();`**: モデルの`delete`メソッドを呼び出し、データベースからレコードを削除します。
- **`return redirect('/admin');`**: 削除完了後、管理画面の一覧ページにリダイレクトします。ユーザーは一覧に戻り、削除されたレコードが消えていることを確認できます。

### `TagController` - タグのマスタデータ管理

#### `store`メソッド（タグ作成）

- **`public function store(StoreTagRequest $request)`**: `StoreTagRequest`でバリデーションを実行します。
- **`Tag::create($request->validated());`**: バリデーション済みのデータだけを使って、新しい`Tag`レコードを作成します。
- **`return redirect('/admin');`**: 作成後、管理画面にリダイレクトします。

#### `update`メソッド（タグ更新）

- **`public function update(UpdateTagRequest $request, Tag $tag)`**: `UpdateTagRequest`でバリデーションを行いつつ、ルートモデルバインディングで更新対象の`Tag`モデルを受け取ります。1つのメソッド引数に`FormRequest`とモデルの両方をインジェクトする例です。
- **`$tag->update($request->validated());`**: モデルの`update`メソッドで、バリデーション済みのデータでレコードを更新します。
- **`return redirect('/admin');`**: 更新後、管理画面にリダイレクトします。

#### `destroy`メソッド（タグ削除）

- **`public function destroy(Tag $tag)`**: ルートモデルバインディングで削除対象の`Tag`モデルを受け取ります。
- **`$tag->delete();`**: タグを削除します。
- **`return redirect('/admin');`**: 削除後、管理画面にリダイレクトします。

## 6. How to: この実装にたどり着くための調べ方

複雑な検索機能やフォームの確認画面フローを実装するまでの思考プロセスを、AIアシスタントを活用しながら進める方法を探ってみましょう。

### Step 1: 公式ドキュメントを読みやすくまとめる（全体像の把握）

まずはLaravelのコントローラーに関する公式ドキュメントをAIに要約してもらい、全体像を掴みます。

**プロンプト例**
```
以下はLaravelのコントローラーに関する公式ドキュメントの一部です。 これを「実装できるように」分かりやすくまとめてください。

出力してほしい内容：
- 重要ポイント（10行以内）
- 用語の説明（重要なものだけ）
- できること / できないこと（境界をはっきり）
- よくある落とし穴（回避策つき）
- 最小で動かすための手順（コードはまだ不要）

--- ここから ---
（ここにLaravelのコントローラーに関する公式ドキュメントを貼り付ける）
--- ここまで ---
```

### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

コントローラーの役割、特にメソッドインジェクションやルートモデルバインディングの仕組みについて、自分の理解が正しいかを確認します。

**プロンプト例**
```
Laravelのコントローラーについて、私の理解はこうです：
「コントローラーは、ルーティングからリクエストを受け取って、モデルやビューと連携するクラス。メソッドの引数にクラス名を書くと、Laravelが自動でインスタンスを用意してくれる（メソッドインジェクション）。特に、URLのIDと対応するモデルを自動で探してくれるのがルートモデルバインディング。」

お願い：
1) 正しいかチェックして、間違いがあれば「反例」で教えてください
2) 仕組みを「入力→中で起きること→出力」で説明してください
3) どこまでがこの概念の範囲か（境界）を教えてください
4) よくある勘違いを3つ教えてください
5) 理解チェック問題を3問ください（答えつき）
```

### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

確認画面付きのお問い合わせフォームと、検索機能付きの管理画面を実装するという具体的な目標を設定し、AIに実装計画を立ててもらいます。

**プロンプト例**
```
目的は、確認画面付きのお問い合わせフォーム（入力→確認→保存→完了）と、
キーワード、性別、カテゴリー、日付で検索できる管理画面を作ることです。
ページネーションも必要です。すべてサーバーサイドレンダリング（SSR）で実装します。
前提知識は、Laravelの基本的なCRUDとモデルの知識がある程度です。

次の順番で出力してください：

A. 実装の手順・方針
- まず全体の方針（なぜそのやり方か）
- 手順を1〜Nで（各手順に「できたらOK」の条件も書く）

B. 関連技術の解説
- 必要な関連知識を3〜7個
- 各項目は「一言で説明 → この実装で何に使う → 注意点」

C. 実装例
- まず最小で動くフォーム送信の例
- 次に確認画面フローと検索機能を追加した拡張例

D. コードの解説
- 重要な部分だけ「何をしてるか」「なぜそう書くか」
- よくあるバグと対策

追加で必要な情報があれば質問していいですが、最大3つまでにしてください。
```

### Step 4: 設計レビュー（指摘をもらう）

自分で書いた、あるいはAIが生成したコードをレビューしてもらい、改善点を探します。

**プロンプト例**
```
以下のコントローラーの設計をレビューしてください。

- 目的：確認画面付きお問い合わせフォームと管理画面
- 要件：入力→確認→保存→完了のフロー、管理画面では検索・ページネーション・削除ができる
- 制約：Laravel 10, PHP 8.2, SSR（Bladeテンプレート使用）
- 設計案：
（ここにContactController.phpとAdminController.phpのコードを貼り付ける）
- 不安な点：PRGパターンは正しく実装できているか。N+1問題が起きないか。

見てほしい観点：
- 正しく動くか（抜け漏れ）
- 運用しやすいか（監視/障害対応）
- 変更しやすいか（拡張/分離）
- パフォーマンス（N+1問題など）
- セキュリティ

出力：
- 指摘を「重要度：高/中/低」で出す
- 各指摘に「理由」「影響」「直し方」をつける
- 最後に「この設計が失敗しやすい例」を3つ出す
```

このように、AIを単なるコード生成器としてではなく、学習のパートナーとして活用することで、技術の深い理解と実践的なスキルを効率的に身につけることができます。

## 7. まとめ

このチャプターでは、アプリケーションの司令塔であるコントローラーを実装し、これまで作成してきた部品を組み合わせて具体的なビジネスロジックを構築しました。

- **責務の分離**: エンドユーザー向けの`ContactController`、管理者向けの`AdminController`、タグ管理の`TagController`と、対象ユーザーや操作対象ごとにコントローラーを分離し、それぞれの責務を明確にしました。
- **メソッドインジェクションの活用**: `FormRequest`による自動バリデーションと、ルートモデルバインディングによるモデルの自動解決を活用し、コードをクリーンに保ちました。
- **高度なクエリの構築**: クエリビルダを使い、クロージャによる条件のグループ化や、`if`文による動的なクエリ構築を行うことで、複雑な検索機能を実現しました。
- **SSRパターンの実践**: `view()`でBladeテンプレートにデータを渡してHTMLを返す方法と、`redirect()`でPRGパターンを実現する方法を学びました。すべてのコントローラーがサーバーサイドレンダリングに統一されており、`view()`と`redirect()`がレスポンス生成の基本となっています。

これで、アプリケーションのバックエンドロジックの主要な部分が完成しました。次のチャプターでは、これらのコントローラーとメソッドを、どのURL（URI）に結びつけるかを定義する、アプリケーションの「交通標識」である**ルーティング**の設定について学んでいきます。
