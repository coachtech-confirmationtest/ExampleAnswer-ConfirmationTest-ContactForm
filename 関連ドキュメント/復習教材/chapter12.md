# Chapter 12: 応用機能 - タグAPIの実装

## 🎯 このセクションで学ぶこと

データベースとモデルの準備が整ったので、いよいよタグを実際に操作するための「API」を実装します。API（Application Programming Interface）とは、フロントエンドのJavaScriptや外部アプリケーションが、バックエンドの機能を利用するための窓口です。このチャプターでは、タグの**C**reate（作成）、**R**ead（読み取り）、**U**pdate（更新）、**D**elete（削除）という、データ操作の基本となる4つの機能（**CRUD**）をAPIとして実装します。具体的には、`TagController`を作成し、`FormRequest`によるバリデーション、`API Resource`によるレスポンス整形を組み合わせることで、堅牢で再利用性の高いAPIを構築する手法を学びます。

## 1. はじめに 📖

### なぜAPIとして実装するのか？

管理画面のタグ管理機能を実装するにあたり、なぜ伝統的なWebフォームの送信ではなく、APIを介した方法を取るのでしょうか。それは、フロントエンドとバックエンドを分離するモダンな開発スタイル（SPA: Single Page Applicationなど）のメリットを享受するためです。

APIを介することで、フロントエンドは画面遷移を伴わないスムーズなユーザー体験（非同期通信）を提供できます。例えば、タグを新しく追加したとき、ページ全体をリロードすることなく、タグの一覧部分だけを動的に更新できます。また、バックエンドはデータの操作と提供という責務に集中でき、将来的にWebフロントエンド以外のクライアント（例: スマートフォンアプリ）が登場した場合でも、同じAPIを再利用することができます。このように、APIを中心とした設計は、アプリケーションの拡張性と柔軟性を飛躍的に高めます。

### CRUDとは？データ操作の基本原則

CRUDは、ほとんどのアプリケーションに共通する、永続的なデータを扱うための4つの基本的な操作を指す頭字語です。

-   **Create**: 新しいデータを作成する（例: 新しいタグを登録する）。
-   **Read**: 既存のデータを読み取る（例: タグの一覧を取得する）。
-   **Update**: 既存のデータを更新する（例: タグの名前を変更する）。
-   **Delete**: 既存のデータを削除する（例: タグを削除する）。

このCRUDという考え方は、APIのエンドポイント設計における基本となります。このチャプターで実装する`TagController`の各メソッド（`store`, `index`, `update`, `destroy`）は、それぞれCRUDの各操作に綺麗に対応しています。

## 2. 要件の確認 📋

このチャプターで実装するタグ管理APIの具体的な仕様を整理します。

| 機能 | HTTPメソッド | URL | 処理内容 | バリデーション | レスポンス |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **一覧取得 (Read)** | `GET` | `/api/tags` | 全てのタグを一覧で取得する。 | なし | `TagResource`のコレクション (200 OK) |
| **新規作成 (Create)** | `POST` | `/api/tags` | 新しいタグを作成する。 | `StoreTagRequest` (nameは必須、ユニーク) | なし (201 CREATED) |
| **更新 (Update)** | `PUT` / `PATCH` | `/api/tags/{tag}` | 指定されたIDのタグを更新する。 | `UpdateTagRequest` (nameは必須、ユニークだが自分自身は除外) | なし (204 NO CONTENT) |
| **削除 (Delete)** | `DELETE` | `/api/tags/{tag}` | 指定されたIDのタグを削除する。 | なし | なし (204 NO CONTENT) |

## 3. 先輩エンジニアの思考プロセス 💭

RESTfulなCRUD APIを設計する際、経験豊富なエンジニアはどのような設計判断を下すのでしょうか。

### Point 1: コントローラーは「リソースコントローラー」として作成する

Artisanコマンドでコントローラーを作成する際、`--resource`オプションを付けると、CRUD操作に対応する基本的なメソッド（`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`）がスタブ（雛形）として自動的に生成されます。これは「リソースコントローラー」と呼ばれ、RESTfulな設計原則に従うための素晴らしい出発点となります。`tags`というリソース（資源）に対する操作を一つのコントローラーにまとめることで、コードの見通しが良くなり、どこに何が書かれているかが一目瞭然になります。API専用のコントローラーなので、`--api`オプションも併用し、`create`や`edit`といったビューを返す不要なメソッドを除外するのが定石です。

### Point 2: バリデーションロジックは`FormRequest`に分離する

コントローラーのメソッド内でバリデーションロジックを書くこともできますが、これは責務の分離の原則に反します。コントローラーの責務は「リクエストを受け取り、レスポンスを返す」ことであり、バリデーションという詳細なルールは別のクラスに任せるべきです。そこで`FormRequest`が登場します。`StoreTagRequest`や`UpdateTagRequest`のように、リクエストの種類ごとに専用のクラスを作成し、その中にバリデーションルールとエラーメッセージを記述します。これにより、コントローラーは驚くほどスリムになり、バリデーションルールを他の場所で再利用することも可能になります。また、`FormRequest`はバリデーションが失敗した場合、自動的に適切なエラーレスポンスを返してくれるため、コントローラーに余計なif文を書く必要がありません。

### Point 3: `Update`時のユニーク制約は「自分自身」を除外する

`Update`処理のバリデーションは、`Create`処理よりも少し複雑です。タグ名をユニークに保ちたい場合、`Create`時は単純に`unique:tags,name`というルールで問題ありません。しかし、`Update`時に同じルールを適用すると、「タグ名を変更しない」という操作ですら「その名前はすでに使われています」というバリデーションエラーになってしまいます。正しくは、「**更新対象のタグ自身のIDを除いて**、名前がユニークであること」を検証しなければなりません。これは、Laravelの`Rule`ファサードを使って`Rule::unique(\'tags\', \'name\')->ignore($tagId)`のように記述することで、エレガントに実現できます。

### Point 4: レスポンスの形式は`API Resource`で統一する

APIから返すJSONの構造は、アプリケーション全体で一貫しているべきです。`API Resource`クラスは、モデルのデータをJSONに変換する際の「変換レイヤー」として機能します。`TagResource`を作成し、そこで`id`と`name`だけを返すように定義しておけば、将来的に`tags`テーブルに`description`カラムなどを追加したとしても、APIのレスポンスに意図せずそれが含まれてしまうのを防ぐことができます。APIのレスポンス形式という「契約」を明確に定義し、それを一箇所で管理することは、APIの安定した運用に不可欠です。

### Point 5: 適切なHTTPステータスコードを返す

APIのレスポンスにおいて、返されるデータと同じくらい重要なのが「HTTPステータスコード」です。ステータスコードは、リクエストが成功したのか、失敗したのか、その結果どうなったのかをクライアントに伝えるための標準的な方法です。

-   `200 OK`: リクエストは成功し、レスポンスボディにデータが含まれている（例: `GET`リクエスト）。
-   `201 Created`: リクエストは成功し、新しいリソースが作成された（例: `POST`リクエスト）。
-   `204 No Content`: リクエストは成功したが、返すデータはない（例: `PUT`や`DELETE`リクエスト）。
-   `422 Unprocessable Entity`: バリデーションエラー。

これらの規約に従って適切なステータスコードを返すことで、クライアント側はレスポンスのボディを見なくても、処理の結果を正確に判断できます。

## 4. 実装 🚀

タグのCRUD APIを実装するために必要なコンポーネントを一つずつ作成していきます。

### 4.1. `TagController`の作成

API用のリソースコントローラーとして`TagController`を生成します。

```bash
sail artisan make:controller Api/TagController --api --model=Tag
```

生成された`app/Http/Controllers/Api/TagController.php`を以下のように編集します。

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::all();
        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $tag = Tag::create($request->validated());

        return response()->json(null, Response::HTTP_CREATED);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
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

### 4.4. `TagResource`の作成

APIレスポンスのJSON形式を定義します。

```bash
sail artisan make:resource TagResource
```

生成された`app/Http/Resources/TagResource.php`を編集します。

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

### 4.5. ルーティングの追加

最後に、`routes/api.php`にタグAPIのルートを定義します。このチャプターでは、応用機能であるタグ管理APIのルートを追加します。具体的には、以下の4つのルートです。

-   **GET /api/tags**: 全てのタグを取得する
-   **POST /api/tags**: 新しいタグを作成する
-   **PUT /api/tags/{tag}**: 指定したタグを更新する
-   **DELETE /api/tags/{tag}**: 指定したタグを削除する

`routes/api.php`を開き、以下のハイライトされた部分を追記してください。

**`routes/api.php`**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController; // 追加

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// カテゴリー一覧
Route::get('/categories', [CategoryController::class, 'index']);

// お問い合わせ一覧
Route::get('/contacts', [ContactController::class, 'index']);
// お問い合わせ登録
Route::post('/contacts', [ContactController::class, 'store']);
// お問い合わせ詳細
Route::get('/contacts/{contact}', [ContactController::class, 'show']);
// お問い合わせ削除
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);

// --- ここから追記 ---
// タグ一覧
Route::get('/tags', [TagController::class, 'index']);
// タグ登録
Route::post('/tags', [TagController::class, 'store']);
// タグ更新
Route::put('/tags/{tag}', [TagController::class, 'update']);
// タグ削除
Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
// --- ここまで追記 ---
```

## 5. コードの詳細解説 🔍

### `app/Http/Controllers/Api/TagController.php` の解説

- **`index()`**: `Tag::all()`で全てのタグを取得し、`TagResource::collection()`でリソースのコレクションに変換して返します。`collection()`メソッドは、複数のモデルインスタンスをまとめてリソースに変換する際に使用します。
- **`store(StoreTagRequest $request)`**: メソッドの引数で`StoreTagRequest`をタイプヒントしています。これにより、メソッドの本体が実行される前に自動的にバリデーションが行われます。`$request->validated()`で、バリデーション済みの安全なデータのみを取得し、`Tag::create()`に渡して新しいタグを作成します。
- **`update(UpdateTagRequest $request, Tag $tag)`**: ここでは「ルートモデルバインディング」という機能が使われています。URLの`{tag}`の部分にあるIDに一致する`Tag`モデルのインスタンスを、Laravelが自動的に検索して`$tag`変数に注入してくれます。これにより、`Tag::findOrFail($id)`のようなコードを書く必要がなくなります。
- **`destroy(Tag $tag)`**: `update`と同様にルートモデルバインディングが機能し、取得した`$tag`モデルインスタンスに対して`delete()`メソッドを呼び出すだけで削除が完了します。

### `app/Http/Requests/UpdateTagRequest.php` の解説

- **`$tagId = $this->route('tag')?->id;`**: ルートモデルバインディングで注入された`tag`オブジェクトのIDを取得しています。`?->`はPHP 8のNullsafe演算子で、`$this->route('tag')`が`null`の場合にエラーを起こさず`null`を返します。
- **`Rule::unique('tags', 'name')->ignore($tagId)`**: `Rule`ファサードを使ったユニーク制約の定義です。`tags`テーブルの`name`カラムがユニークであることを検証しますが、`ignore($tagId)`によって、指定されたID（つまり、今更新しようとしているタグ自身のID）を持つレコードは検証の対象外となります。

### 💡 コラム: `apiResource`でもっとシンプルに書く

今回の教材では、学習のために一つ一つのルートを`Route::get`や`Route::post`で定義しました。しかし、実務では、このような典型的なCRUD操作のルートを一行で定義できる`Route::apiResource`という便利なメソッドがよく使われます。

例えば、今回定義したタグAPIのルートは、`apiResource`を使うと以下のように書き換えることができます。

```php
// この4行が...
Route::get('/tags', [TagController::class, 'index']);
Route::post('/tags', [TagController::class, 'store']);
Route::put('/tags/{tag}', [TagController::class, 'update']);
Route::delete('/tags/{tag}', [TagController::class, 'destroy']);

// この1行になる！
Route::apiResource("tags", TagController::class)->except(["show"]);
```

`apiResource`は、APIでよく使われる`index`, `store`, `show`, `update`, `destroy`の5つのアクションに対応するルートを自動で生成します。今回は`show`アクションは不要なので、`except`メソッドで除外しています。

このように、Laravelには冗長な記述を減らし、コードをよりクリーンに保つための便利な機能がたくさん用意されています。まずは基本の書き方をマスターし、慣れてきたらこのようなショートカットも活用していくと、より効率的に開発を進めることができるでしょう。

## 6. How to: この実装にたどり着くための調べ方 🗺️

### エンジニアが新しい技術を学ぶ4ステップ

実務では、未知の技術やライブラリを使いこなす能力が求められます。ここでは、Laravelの`リソースコントローラー`や`FormRequest`を学ぶというシナリオで、効率的な学習の4ステップを見ていきましょう。

#### Step 1: 公式ドキュメントを読みやすくまとめる

まずは公式ドキュメントを読んで全体像を掴みます。しかし、ドキュメントは情報量が多いことも。AIに要点をまとめてもらい、学習の地図を手に入れましょう。

**プロンプト例**
```
以下はLaravelの「リソースコントローラー」に関する公式ドキュメントの一部です。 これを「実装できるように」分かりやすくまとめてください。

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
- 「`--api`オプションでAPI用のコントローラーが作れる」といった核心的な情報
- 「リソース」「RESTful」といった基本用語の解説
- `Route::apiResource`という便利な書き方の存在
- 学習の全体像を素早く掴む

#### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

次に、技術の「なぜ」を深掘りします。自分の理解が正しいか、AIに壁打ち相手になってもらいましょう。

**プロンプト例**
```
Laravelの「ルートモデルバインディング」について、私の理解はこうです：
「URLのID（例: /tags/1）を元に、Laravelが自動で対応するモデル（TagモデルのID=1）を見つけて、コントローラーのメソッドに渡してくれる仕組み」

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
目的は「Laravelでタグを新規作成するAPI（POST /api/tags）の実装」です。
制約は「バリデーションはFormRequestで行い、レスポンスは201 Createdを返す」です。
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
- 次に実務向けの拡張例（エラー処理/ログ/設定など）

D. コードの解説
- 重要な部分だけ「何をしてるか」「なぜそう書くか」
- よくあるバグと対策

追加で必要な情報があれば質問していいですが、最大3つまでにしてください。
```

**AIから得られること**
- 体系化された実装手順
- `FormRequest`, `Controller`, `Route`の連携方法
- 最小構成のコードと、より実践的なコードの比較
- 段階的に実装の解像度を上げる

#### Step 4: 設計レビュー（指摘をもらう）

最後に、自分の設計やコードをAIにレビューしてもらい、客観的な視点を取り入れます。

**プロンプト例**
```
以下のAPI設計をレビューしてください。

- 目的：タグ管理機能
- 要件：タグのCRUD操作ができること
- 制約：Laravel 10, PHP 8.2
- 設計案：
  - GET /api/tags (一覧)
  - POST /api/tags (作成)
  - PUT /api/tags/{id} (更新)
  - DELETE /api/tags/{id} (削除)
- 不安な点：
  - 更新時のURLはPUT /api/tags/{id}で良いか？PATCHとの違いは？
  - 大量にタグが増えた時のパフォーマンスは大丈夫か？

見てほしい観点：
- RESTの原則に沿っているか
- 運用しやすいか（監視/障害対応）
- 変更しやすいか（拡張/分離）
- パフォーマンス
- セキュリティ

出力：
- 指摘を「重要度：高/中/低」で出す
- 各指摘に「理由」「影響」「直し方」をつける
- 最後に「この設計が失敗しやすい例」を3つ出す
```

**AIから得られること**
- 設計の抜け漏れや改善点の発見
- `PUT`と`PATCH`の使い分けといった、より深い知識
- パフォーマンス改善（ページネーション）の提案
- セキュリティリスクの指摘

## 7. まとめ ✨

このチャプターでは、タグを管理するための完全なCRUD APIを実装しました。

-   **リソースコントローラー**: `--api`オプション付きのリソースコントローラーで、APIの骨格を効率的に作成しました。
-   **FormRequestによるバリデーション分離**: `StoreTagRequest`と`UpdateTagRequest`を作成し、バリデーションロジックをコントローラーから分離しました。
-   **更新時のユニーク制約**: `Rule::unique()->ignore()`を使い、更新時に自分自身のIDをユニーク制約の対象から除外する方法を学びました。
-   **APIリソースによるレスポンス整形**: `TagResource`を使って、APIのレスポンス形式を統一・管理する方法を実装しました。
-   **`apiResource`ルーティング**: `Route::apiResource`を使い、CRUDのAPIルートを一行で簡潔に定義しました。

これで、フロントエンドからタグを自由に操作するためのバックエンドの準備が整いました。次のチャプターでは、このAPIを利用して、お問い合わせを登録する際にタグを一緒に紐付けられるように、既存の`ContactController`を改修していきます。
