# Chapter 8: アプリケーションの司令塔 - コントローラーでビジネスロジックを操る 🚀

## 🎯 このセクションで学ぶこと

このチャプターでは、アプリケーションの心臓部であり、ユーザーからのリクエストを処理し、適切なレスポンスを返す「司令塔」の役割を担う**コントローラー**の実装に焦点を当てます。これまでに作成したモデル、FormRequest、APIリソースといった部品をここで初めて組み合わせ、リクエストの受付、バリデーション、データ処理、レスポンス生成という一連のビジネスロジックを組み立てる方法を学びます。

## 1. はじめに 📖

### コントローラーとは？リクエストとレスポンスの交通整理役

Laravelにおけるコントローラーは、ルーティングから受け取ったHTTPリクエストを処理するためのクラスです。ユーザーが特定のURLにアクセスすると、ルーターはそのリクエストを対応するコントローラーのメソッドに引き渡します。コントローラーのメソッドは、そのリクエストに応じて以下のようなタスクを実行します。

1.  **リクエストデータの受け取り**: フォームから送信された入力値や、URLのパラメータを取得します。
2.  **バリデーションの実行**: 受け取ったデータが正しい形式か、`FormRequest`を使って検証します。
3.  **ビジネスロジックの実行**: モデルを使ってデータベースを操作（取得、作成、更新、削除）したり、他のサービスクラスを呼び出したりします。
4.  **レスポンスの生成**: 処理結果を`View`に渡してHTMLを生成したり、`APIリソース`を使ってJSONを生成したりして、ユーザーに返却します。

コントローラーは、モデル（データ）、ビュー（表示）、そしてユーザーからのリクエストという、アプリケーションの異なる関心事を仲介する重要な役割を担っています。ビジネスロジックをコントローラーに集約することで、ルーティングファイルはスッキリと保たれ、アプリケーションのどこで何が行われているかが見通しやすくなります。これを「関心の分離」と呼び、メンテナンス性の高いアプリケーションを構築するための基本原則です。

## 2. 要件の確認 📋

このアプリケーションで必要となるコントローラーの機能と、それぞれの役割を整理します。

| コントローラーファイル | メソッド | HTTPメソッド | URI | 役割 |
| :--- | :--- | :--- | :--- | :--- |
| `ContactController` | `index` | GET | `/` | お問い合わせフォーム画面を表示する |
| | `thanks` | GET | `/thanks` | 送信完了画面を表示する |
| `AdminController` | `index` | GET | `/admin` | 管理者用のダッシュボード画面を表示する |
| `Api\CategoryController` | `index` | GET | `/api/categories` | 全てのカテゴリー情報をJSONで取得する |
| `Api\ContactController` | `index` | GET | `/api/contacts` | お問い合わせ情報を一覧で取得する（検索・ページネーション付き） |
| | `store` | POST | `/api/contacts` | 新しいお問い合わせ情報を登録する |
| | `show` | GET | `/api/contacts/{contact}` | 特定のお問い合わせ情報を取得する |
| | `destroy` | DELETE | `/api/contacts/{contact}` | 特定のお問い合わせ情報を削除する |

**ポイント**:
- **ビューを返すコントローラー**: `ContactController`と`AdminController`は、`view()`ヘルパー関数を使ってBladeテンプレートをレンダリングし、HTMLレスポンスを返します。
- **JSONを返すAPIコントローラー**: `Api`名前空間に配置されたコントローラーは、`APIリソース`や`response()->json()`を使ってJSONレスポンスを返します。これにより、フロントエンドのJavaScriptと非同期通信を行います。

## 3. 先輩エンジニアの思考プロセス 💭

なぜ複数のコントローラーに分けるのか？APIコントローラーをなぜ別のディレクトリに置くのか？その設計思想に迫ります。

### Point 1: 1つのコントローラーには、1つの関心事を

Laravelでは、1つのコントローラーにすべてのロジックを詰め込むことも技術的には可能です。しかし、それは「Fat Controller（太ったコントローラー）」と呼ばれ、アンチパターンとされています。`ContactController`は「お問い合わせ関連のページの表示」、`AdminController`は「管理画面の表示」、`Api\ContactController`は「お問い合わせデータの操作」というように、それぞれのコントローラーが担当する「関心事」を明確に分離することが重要です。これにより、コードが読みやすくなり、変更が必要になった際に影響範囲を特定しやすくなります。これは、ソフトウェア設計における**単一責任の原則**の実践です。

### Point 2: WebとAPIでコントローラーを分離する

このアプリケーションでは、`app/Http/Controllers`配下に、Webページを返すコントローラーと、`Api`サブディレクトリにJSONを返すAPIコントローラーを分けて配置しています。これは非常に一般的な設計パターンです。なぜなら、Webページを返すコントローラーとAPIコントローラーでは、責務や利用されるコンテキストが根本的に異なるからです。

- **Webコントローラー**: 主に`view()`を返し、セッションやCookieを扱うことが多い。
- **APIコントローラー**: 主にJSONを返し、認証にはトークン（例: Sanctum）を使い、ステートレスであることが多い。

このように物理的にディレクトリを分けることで、両者の違いが明確になり、ミドルウェアの適用などをグループごとに管理しやすくなります。

### Point 3: メソッドインジェクションで、必要なものを自動で受け取る

Laravelの強力な機能の一つに「メソッドインジェクション」があります。コントローラーのメソッドの引数に、`StoreContactRequest $request`や`Contact $contact`のようにクラス名を指定するだけで、Laravelのサービスコンテナが自動的にそのクラスのインスタンスを生成または解決して、メソッドに渡してくれます。

- **`StoreContactRequest $request`**: `FormRequest`をインジェクトすると、メソッドの本体が実行される**前**に、自動でバリデーションが実行されます。バリデーションが失敗すれば、例外がスローされ、ユーザーはエラーメッセージと共に前のページにリダイレクトされます。これにより、コントローラー本体にはバリデーションが成功した場合のロジックだけを書けばよくなり、コードが非常にクリーンになります。
- **`Contact $contact`**: ルート定義で`{contact}`のようにモデル名と同じパラメータ名を使うと、「ルートモデルバインディング」が機能します。URLに含まれるID（例: `/api/contacts/123`の`123`）を使って、自動的に`Contact`モデルをデータベースから検索し、インスタンスをインジェクトしてくれます。もし該当するモデルが見つからなければ、自動的に404 Not Foundレスポンスが返されます。これにより、`$contact = Contact::findOrFail($id);`のような定型コードを書く必要がなくなります。

これらの機能を最大限に活用することで、コントローラーのコードを驚くほどシンプルで宣言的に記述することができます。

## 4. 実装 🚀

それでは、4つのコントローラーを作成し、それぞれのビジネスロジックを実装していきましょう。

### 4.1. コントローラーファイルの作成

以下の`sail artisan make:controller`コマンドで、必要なコントローラーファイルを生成します。

```bash
# Webページ用コントローラー
sail artisan make:controller ContactController
sail artisan make:controller AdminController

# API用コントローラー（--apiオプションで不要なメソッドを除外）
sail artisan make:controller Api/CategoryController --api
sail artisan make:controller Api/ContactController --api
```

`--api`オプションを付けると、`create`や`edit`といったHTMLビューを返すためのメソッドが除外された、APIに特化したコントローラーの雛形が作成されます。

### 4.2. `ContactController.php`の実装

このコントローラーは、単純にお問い合わせフォーム画面と送信完了画面のビューを返すだけです。

`app/Http/Controllers/ContactController.php`
```php
<?php

namespace App\Http\Controllers;

class ContactController extends Controller
{
    public function index()
    {
        return view("contact.index");
    }

    public function thanks()
    {
        return view("contact.thanks");
    }
}
```

### 4.3. `AdminController.php`の実装

このコントローラーは、管理画面のビューを返します。`auth`ミドルウェアによって、認証済みのユーザーしかアクセスできません。

`app/Http/Controllers/AdminController.php`
```php
<?php

namespace App\Http\Controllers;

class AdminController extends Controller
{
    public function index()
    {
        return view("admin.index");
    }
}
```

### 4.4. `Api/CategoryController.php`の実装

カテゴリーの一覧をAPIとして提供します。`Category`モデルから全件取得し、`CategoryResource`を使ってJSONに変換します。

`app/Http/Controllers/Api/CategoryController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return CategoryResource::collection($categories);
    }
}
```

### 4.5. `Api/ContactController.php`の実装

このコントローラーが、お問い合わせ機能のバックエンド処理の中心です。一覧取得、新規作成、詳細取得、削除の4つの機能（CRUD）を実装します。

`app/Http/Controllers/Api/ContactController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $query = Contact::with("category");

        if ($request->filled("keyword")) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where("first_name", "like", "%{$keyword}%")
                    ->orWhere("last_name", "like", "%{$keyword}%")
                    ->orWhere("email", "like", "%{$keyword}%");
            });
        }

        if ($request->filled("gender") && $request->gender != 0) {
            $query->where("gender", $request->gender);
        }

        if ($request->filled("category_id")) {
            $query->where("category_id", $request->category_id);
        }

        if ($request->filled("date")) {
            $query->whereDate("created_at", $request->date);
        }

        $contacts = $query->latest()->paginate(7);
        return ContactResource::collection($contacts);
    }

    public function store(StoreContactRequest $request)
    {
        Contact::create($request->validated());
        return response()->json(null, 201);
    }

    public function show(Contact $contact)
    {
        return new ContactResource($contact->load("category"));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(null, 204);
    }
}
```

## 5. コードの詳細解説 🔍

`Api/ContactController.php`の各メソッドを詳しく見ていきましょう。

### `index`メソッド (一覧取得)

- **`public function index(IndexContactRequest $request)`**: `IndexContactRequest`をインジェクトし、検索パラメータのバリデーションを自動で行います。
- **`$query = Contact::with("category");`**: クエリビルダのインスタンスを生成します。`with("category")`で、`Contact`を取得する際に`Category`モデルも同時に取得（Eager Loading）し、N+1問題を回避しています。
- **`if ($request->filled("keyword")) { ... }`**: `keyword`パラメータが存在する場合のみ、検索処理を実行します。
- **`$query->where(function ($q) use ($keyword) { ... });`**: 複数の`orWhere`をグループ化するためのクロージャです。これを使わないと、他の`where`条件との組み合わせが意図しない結果になる可能性があります。
- **`->orWhere("first_name", "like", "%{$keyword}%")`**: `LIKE`句を使って部分一致検索を行っています。
- **`if ($request->filled("gender") && $request->gender != 0)`**: `gender`が`0`（すべて）でない場合にのみ、絞り込みを行います。
- **`$query->whereDate("created_at", $request->date);`**: `whereDate`メソッドで、`created_at`の日付部分だけで検索します。
- **`$contacts = $query->latest()->paginate(7);`**: これまで組み立てたクエリを実行します。`latest()`は`orderBy("created_at", "desc")`のショートカットです。`paginate(7)`で、7件ごとにページ分割した結果を取得します。
- **`return ContactResource::collection($contacts);`**: 取得したページネーション済みのコレクションを`ContactResource`に渡し、JSONレスポンスを生成します。

### `store`メソッド (新規作成)

- **`public function store(StoreContactRequest $request)`**: `StoreContactRequest`でバリデーションを実行します。
- **`Contact::create($request->validated());`**: バリデーション済みのデータのみを使って、新しい`Contact`レコードを作成します。`create`メソッドを使うには、`Contact`モデル側で`$fillable`プロパティが正しく設定されている必要があります。
- **`return response()->json(null, 201);`**: 作成が成功したことを示すため、ステータスコード`201 Created`で空のJSONレスポンスを返します。

### `show`メソッド (詳細取得)

- **`public function show(Contact $contact)`**: ルートモデルバインディングにより、URLのIDに対応する`Contact`モデルが自動的にインジェクトされます。
- **`return new ContactResource($contact->load("category"));`**: `ContactResource`を使って単一のモデルをJSONに変換します。`load("category")`は、既に取得済みのモデルに対してリレーションを後から読み込む（Lazy Eager Loading）メソッドです。これにより、`show`メソッドが呼ばれたときだけカテゴリー情報を取得できます。

### `destroy`メソッド (削除)

- **`public function destroy(Contact $contact)`**: `show`メソッドと同様に、削除対象の`Contact`モデルがインジェクトされます。
- **`$contact->delete();`**: モデルの`delete`メソッドを呼び出し、データベースからレコードを削除します。
- **`return response()->json(null, 204);`**: 削除が成功し、返すコンテンツがないことを示すため、ステータスコード`204 No Content`で空のJSONレスポンスを返します。

## 6. How to: この実装にたどり着くための調べ方 🗺️

複雑な検索機能を実装するまでの思考プロセスを、AIアシスタントを活用しながら進める方法を探ってみましょう。

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

検索機能付きのAPIを実装するという具体的な目標を設定し、AIに実装計画を立ててもらいます。

**プロンプト例**
```
目的は、キーワード、性別、カテゴリー、日付で検索できるお問い合わせ一覧APIを作ることです。ページネーションも必要です。
前提知識は、Laravelの基本的なCRUDとモデルの知識がある程度です。

次の順番で出力してください：

A. 実装の手順・方針
- まず全体の方針（なぜそのやり方か）
- 手順を1〜Nで（各手順に「できたらOK」の条件も書く）

B. 関連技術の解説
- 必要な関連知識を3〜7個
- 各項目は「一言で説明 → この実装で何に使う → 注意点」

C. 実装例
- まず最小で動く一覧取得の例
- 次に検索機能とページネーションを追加した拡張例

D. コードの解説
- 重要な部分だけ「何をしてるか」「なぜそう書くか」
- よくあるバグと対策

追加で必要な情報があれば質問していいですが、最大3つまでにしてください。
```

### Step 4: 設計レビュー（指摘をもらう）

自分で書いた、あるいはAIが生成したコードをレビューしてもらい、改善点を探します。

**プロンプト例**
```
以下のコントローラーの`index`メソッドの設計をレビューしてください。

- 目的：お問い合わせ一覧の検索API
- 要件：キーワード、性別、カテゴリー、日付で検索でき、ページネーションに対応する
- 制約：Laravel 10, PHP 8.2
- 設計案：
（ここにApi/ContactController.phpのindexメソッドのコードを貼り付ける）
- 不安な点：N+1問題が起きないか。もっと効率的な書き方はないか。

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

## 7. まとめ ✨

このチャプターでは、アプリケーションの司令塔であるコントローラーを実装し、これまで作成してきた部品を組み合わせて具体的なビジネスロジックを構築しました。

- **責務の分離**: Webページを返すコントローラーとJSONを返すAPIコントローラーを物理的に分離し、それぞれの責務を明確にしました。
- **メソッドインジェクションの活用**: `FormRequest`による自動バリデーションと、ルートモデルバインディングによるモデルの自動解決を活用し、コードをクリーンに保ちました。
- **高度なクエリの構築**: クエリビルダを使い、クロージャによる条件のグループ化や、`if`文による動的なクエリ構築を行うことで、複雑な検索機能を実現しました。
- **レスポンスの生成**: `view()`ヘルパーでHTMLを、`APIリソース`でJSONを生成し、適切なHTTPステータスコードと共にレスポンスを返却する方法を学びました。

これで、アプリケーションのバックエンドロジックの主要な部分が完成しました。次のチャプターでは、これらのコントローラーとメソッドを、どのURL（URI）に結びつけるかを定義する、アプリケーションの「交通標識」である**ルーティング**の設定について学んでいきます。
