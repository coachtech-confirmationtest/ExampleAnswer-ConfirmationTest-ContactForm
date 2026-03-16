# Chapter 11: 応用編 - 公開APIの提供 🌐

## 1. はじめに 📖

このチャプターでは、これまでBlade（SSR）で構築してきたお問い合わせ管理システムに、**公開API**を追加します。

### APIとは？

API（Application Programming Interface）とは、アプリケーション同士がデータをやり取りするための「窓口」です。Webアプリケーションでは一般的に、**JSON形式**でデータを送受信するHTTPベースのAPIを指します。

これまで実装してきたWebアプリケーションは、ブラウザに**HTML**を返す仕組みでした。一方、APIは**JSONデータ**を返します。これにより、モバイルアプリや他のWebサービス、フロントエンドフレームワーク（React、Vue.jsなど）からデータを取得・操作できるようになります。

### なぜWebアプリにAPIを追加するのか？

実務では、1つのバックエンドが複数のクライアント（Webブラウザ、スマホアプリ、外部サービス）にデータを提供するケースが非常に多くあります。APIを提供することで、同じデータやビジネスロジックを再利用できます。

### このチャプターで学ぶこと

- **API Resources** — EloquentモデルをJSON形式に変換する仕組み
- **API用FormRequest** — Web版とは別のバリデーションルール
- **API用コントローラー** — JSON応答、HTTPステータスコード（200, 201, 204, 404, 422）
- **APIルーティング** — `routes/api.php` と `/api` プレフィックスの仕組み

---

## 2. 要件確認 📋

今回実装するAPIの要件を確認しましょう。

### エンドポイント一覧

| HTTPメソッド | URI | 説明 |
|---|---|---|
| GET | `/api/v1/contacts` | お問い合わせ一覧（検索・ページネーション付き） |
| GET | `/api/v1/contacts/{contact}` | お問い合わせ詳細 |
| POST | `/api/v1/contacts` | お問い合わせ新規作成 |
| PUT | `/api/v1/contacts/{contact}` | お問い合わせ更新 |
| DELETE | `/api/v1/contacts/{contact}` | お問い合わせ削除 |

### 設計方針

- **認証不要**: 公開APIとして、認証なしでアクセスできる（Sanctumは使用しない）
- **API Resources使用**: EloquentモデルのJSON変換にAPI Resourcesを使用する
- **Web版と分離**: コントローラー・FormRequestはAPI専用のものを `Api\V1` 名前空間に作成する
- **バージョニング**: `/api/v1/` プレフィックスでAPIバージョンを管理する

> **💡 なぜWeb版と分離するのか？**
>
> Web版のコントローラーはBladeビューを返し、FormRequestのgenderルールは `in:0,1,2,3`（0=全て）です。
> API版はJSONを返し、genderは `in:1,2,3`（パラメータ省略=全て）です。
> レスポンス形式やバリデーションルールが異なるため、責務を分離します。

---

## 3. API Resourcesの作成 🎨

API Resourcesは、EloquentモデルをJSON形式に変換する「変換レイヤー」です。モデルの全カラムをそのまま返すのではなく、APIとして公開したいフィールドだけを選択的に返せます。

### 3-1. CategoryResourceの作成

まず、カテゴリ用のAPI Resourceを作成します。

```bash
sail artisan make:resource CategoryResource
```

**app/Http/Resources/CategoryResource.php**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
        ];
    }
}
```

#### コード解説
- `JsonResource` を継承し、`toArray()` メソッドでJSON出力するフィールドを定義します。
- `$this->id` や `$this->content` は、元のCategoryモデルのプロパティにアクセスしています。
- `created_at` や `updated_at` はAPIとして不要なので含めていません。

### 3-2. TagResourceの作成

```bash
sail artisan make:resource TagResource
```

**app/Http/Resources/TagResource.php**
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

### 3-3. ContactResourceの作成

ContactResourceは、リレーションデータ（category, tags）もネストして返します。

```bash
sail artisan make:resource ContactResource
```

**app/Http/Resources/ContactResource.php**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'email' => $this->email,
            'tel' => $this->tel,
            'address' => $this->address,
            'building' => $this->building,
            'detail' => $this->detail,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### コード解説
- `new CategoryResource($this->whenLoaded('category'))`: categoryリレーションが**ロード済みの場合のみ**出力します。`whenLoaded()` を使うことで、リレーションが未ロードの場合にN+1問題を引き起こすのを防ぎます。
- `TagResource::collection($this->whenLoaded('tags'))`: tagsリレーションをTagResourceのコレクションとして出力します。
- `created_at`, `updated_at`: Laravelが自動的にISO 8601形式（`2026-03-10T10:00:00.000000Z`）に変換します。

> **💡 `whenLoaded()` とは？**
>
> `whenLoaded('relation')` は、そのリレーションが `with()` で事前にロード（Eager Loading）されている場合にのみ値を返します。ロードされていない場合はキーごと省略されます。これにより、コントローラー側で `with()` を書き忘れた場合に、意図せずN+1クエリが発生するのを防げます。

---

## 4. API用FormRequestの作成 📝

### 4-1. 一覧検索用: IndexContactRequest

Web版の `IndexContactRequest` とは異なるバリデーションルールを定義します。

```bash
sail artisan make:request Api/V1/IndexContactRequest
```

作成された `app/Http/Requests/Api/V1/IndexContactRequest.php` を以下のように編集します。

**app/Http/Requests/Api/V1/IndexContactRequest.php**
```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'gender.in' => '性別の値が不正です',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'date.date' => '日付の形式が正しくありません',
            'per_page.max' => '1ページあたりの件数は100件以内で指定してください',
        ];
    }
}
```

#### Web版との差異

| 項目 | Web版 (`App\Http\Requests`) | API版 (`App\Http\Requests\Api\V1`) |
|---|---|---|
| 名前空間 | `App\Http\Requests` | `App\Http\Requests\Api\V1` |
| gender | `in:0,1,2,3`（0=全て） | `in:1,2,3`（省略=全て） |
| per_page | なし（固定7件） | `min:1, max:100`（デフォルト20件） |
| page | なし | `min:1` |

Web版ではHTMLのselectボックスで「全て（0）」を送信しますが、APIではパラメータを省略することで「全て」を表現します。

### 4-2. 作成用: StoreContactRequest

```bash
sail artisan make:request Api/V1/StoreContactRequest
```

作成された `app/Http/Requests/Api/V1/StoreContactRequest.php` を以下のように編集します。

**app/Http/Requests/Api/V1/StoreContactRequest.php**
```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'integer', 'in:1,2,3'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'tel' => ['required', 'string', 'regex:/^[0-9]{10,11}$/'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'detail' => ['required', 'string', 'max:120'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => '姓を入力してください',
            'last_name.required' => '名を入力してください',
            'gender.required' => '性別を選択してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式で入力してください',
            'tel.required' => '電話番号を入力してください',
            'tel.regex' => '電話番号はハイフンなしの10〜11桁で入力してください',
            'address.required' => '住所を入力してください',
            'category_id.required' => 'お問い合わせの種類を選択してください',
            'detail.required' => 'お問い合わせ内容を入力してください',
            'detail.max' => 'お問い合わせ内容は120文字以内で入力してください',
        ];
    }
}
```

バリデーションルール自体はWeb版の `StoreContactRequest` と同一ですが、**名前空間が異なります**。Web版とAPI版で将来的にルールが分岐する可能性を考慮し、別クラスとして作成しています。

### 4-3. 更新用: UpdateContactRequest

```bash
sail artisan make:request Api/V1/UpdateContactRequest
```

作成された `app/Http/Requests/Api/V1/UpdateContactRequest.php` を以下のように編集します。StoreContactRequestと同一のルールです。更新時もすべてのフィールドを必須とするフルリプレース方式（PUT）を採用しています。

**app/Http/Requests/Api/V1/UpdateContactRequest.php**

```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'integer', 'in:1,2,3'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'tel' => ['required', 'string', 'regex:/^[0-9]{10,11}$/'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'detail' => ['required', 'string', 'max:120'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => '姓を入力してください',
            'last_name.required' => '名を入力してください',
            'gender.required' => '性別を選択してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式で入力してください',
            'tel.required' => '電話番号を入力してください',
            'tel.regex' => '電話番号はハイフンなしの10〜11桁で入力してください',
            'address.required' => '住所を入力してください',
            'category_id.required' => 'お問い合わせの種類を選択してください',
            'detail.required' => 'お問い合わせ内容を入力してください',
            'detail.max' => 'お問い合わせ内容は120文字以内で入力してください',
        ];
    }
}
```

> **💡 PUTとPATCHの違い**
>
> REST APIでは、`PUT` はリソースの**全体置換**、`PATCH` は**部分更新**を意味します。今回は `PUT` を採用しているため、更新時もすべてのフィールドを送信する必要があります。`PATCH` を使う場合は、各ルールを `sometimes` にして「送信されたフィールドのみバリデーション」にする設計もあります。

---

## 5. API用コントローラーの作成 🎮

API用のコントローラーは `Api\V1` 名前空間に配置し、Web用コントローラーと完全に分離します。

```bash
sail artisan make:controller Api/V1/ContactController
```

作成された `app/Http/Controllers/Api/V1/ContactController.php` を以下のように編集します。

**app/Http/Controllers/Api/V1/ContactController.php**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
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

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $perPage = $request->input('per_page', 20);
        $contacts = $query->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
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

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact->update($validated);
        $contact->tags()->sync($tagIds);

        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
```

### コード解説

#### indexメソッド（一覧取得）
- Web版の `AdminController@index` と検索ロジックは同じですが、Bladeビューではなく `ContactResource::collection()` でJSON応答を返します。
- `paginate($perPage)` により、Laravelが自動的に `data` 配列と `meta`（ページネーション情報）をJSON応答に含めます。
- Web版はgenderが `0` のとき全件表示ですが、API版はgenderパラメータを**省略**することで全件表示になります。

#### showメソッド（詳細取得）
- `Contact $contact` でルートモデルバインディングを使用し、存在しないIDの場合は自動的に404例外がスローされます。
- `$contact->load(['category', 'tags'])` でリレーションをEager Loadingし、`new ContactResource($contact)` でJSON変換します。

#### storeメソッド（新規作成）
- Web版の `ContactController@store` と同じビジネスロジック（Contact作成 + タグ紐付け）ですが、リダイレクトの代わりに**201 Created** ステータスでJSONレスポンスを返します。
- `->response()->setStatusCode(201)` でHTTPステータスコードを明示的に設定しています。

#### updateメソッド（更新）
- `$contact->update($validated)` でモデルを更新します。
- `$contact->tags()->sync($tagIds)` で、タグの関連を **同期** します。`attach()` は追加のみですが、`sync()` は「指定されたIDだけが関連付けられた状態」にします（不要な関連は自動削除）。

#### destroyメソッド（削除）
- `$contact->delete()` でレコードを削除し、**204 No Content**（レスポンスボディなし）を返します。
- 204はREST APIにおいて「処理は成功したが、返すデータはない」ことを意味するステータスコードです。

> **💡 HTTPステータスコードの使い分け**
>
> | コード | 意味 | 使用場面 |
> |---|---|---|
> | 200 OK | 成功 | 取得・更新成功時 |
> | 201 Created | 作成成功 | 新規リソース作成時 |
> | 204 No Content | 成功（ボディなし） | 削除成功時 |
> | 404 Not Found | 未検出 | リソースが存在しない時 |
> | 422 Unprocessable Entity | バリデーションエラー | 入力値が不正な時 |

---

## 6. ルート定義 🛤️

APIルートは `routes/api.php` に定義します。

**routes/api.php**
```php
<?php

use App\Http\Controllers\Api\V1\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('contacts', ContactController::class);
});
```

### コード解説

- `Route::prefix('v1')`: URLに `/v1` プレフィックスを追加します。
- `Route::apiResource('contacts', ...)`: RESTfulな5つのルート（index, show, store, update, destroy）を一括定義します。`Route::resource()` との違いは、`create` と `edit`（HTMLフォーム表示用）が含まれないことです。

> **💡 `/api` プレフィックスの自動付与**
>
> `routes/api.php` に定義したルートには、Laravelが自動的に `/api` プレフィックスを付与します。そのため、`prefix('v1')` の `apiResource('contacts')` は、実際には `/api/v1/contacts` というURLになります。
>
> この設定は `app/Providers/RouteServiceProvider.php` の `boot()` メソッドで行われています。

### ルーティングの確認

定義したルートを確認するには、以下のコマンドを実行します。

```bash
sail artisan route:list --path=api
```

以下のようなルートが表示されるはずです。

```
  GET|HEAD  api/v1/contacts ............ contacts.index
  POST      api/v1/contacts ............ contacts.store
  GET|HEAD  api/v1/contacts/{contact} .. contacts.show
  PUT|PATCH api/v1/contacts/{contact} .. contacts.update
  DELETE    api/v1/contacts/{contact} .. contacts.destroy
```

---

## 7. 404エラーハンドリング 🚨

存在しないIDにアクセスした場合、カスタムエラーメッセージをJSON形式で返すようにします。

**app/Exceptions/Handler.php** に `render()` メソッドを追加します。

```php
<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') && $e instanceof ModelNotFoundException) {
            return response()->json([
                'error' => 'お問い合わせが見つかりませんでした。',
            ], 404);
        }

        return parent::render($request, $e);
    }
}
```

### コード解説

- `render()` メソッドをオーバーライドし、例外のレスポンス変換をカスタマイズしています。
- `$e instanceof ModelNotFoundException`: ルートモデルバインディングでモデルが見つからない場合、Laravelは `ModelNotFoundException` をスローします。この例外を直接捕捉してJSON応答を返します。
- `$request->is('api/*')`: APIリクエスト（`/api/` で始まるURL）の場合のみカスタムレスポンスを返します。Web画面のリクエストには影響しません。
- `return parent::render($request, $e)`: 上記の条件に合致しない例外は、親クラスのデフォルト処理に委譲します。

Handler.phpを編集した後は、以下のコマンドを実行してキャッシュをクリアしてください。Docker/Sail環境では、PHPのOPcache（コンパイル済みコードのキャッシュ）が古いコードを保持し続けることがあり、編集が反映されない場合があります。

```bash
sail artisan optimize:clear
```

---

## 8. 動作確認（Postman） 🔍

実装したAPIが正しく動作するか、Postmanを使って確認しましょう。

### 8-1. 一覧取得: GET /api/v1/contacts

1. Postmanで「New Request」を作成します。
2. HTTPメソッドを **GET** に設定します。
3. URLに `http://localhost/api/v1/contacts` を入力します。
4. 「Send」ボタンをクリックします。

**確認ポイント:**
- ステータスコードが **200 OK** であること
- レスポンスボディに `"data"` 配列と `"meta"` オブジェクト（current_page, last_page, per_page, total）が含まれること

### 8-2. 検索パラメータ付き一覧: GET /api/v1/contacts?keyword=...

1. HTTPメソッドを **GET** のまま、URLに `http://localhost/api/v1/contacts` を入力します。
2. 「Params」タブをクリックし、以下のクエリパラメータを追加します。

| KEY | VALUE |
|---|---|
| per_page | 5 |

3. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **200 OK** であること
- `"meta"` の `per_page` が `5` であること
- `"data"` 配列に最大5件のデータが含まれること

続けて、`keyword` パラメータも試してみましょう。一覧レスポンスの `"data"` 配列から任意のContactの `first_name` の値をコピーし、以下のように設定します。

| KEY | VALUE |
|---|---|
| keyword | （コピーした姓） |

**確認ポイント:**
- `"data"` 配列に、指定したキーワードに一致するContactのみが含まれること
- キーワードは姓（first_name）、名（last_name）、メールアドレス（email）の部分一致で検索されること

### 8-3. 詳細取得: GET /api/v1/contacts/1

1. HTTPメソッドを **GET** に設定します。
2. URLに `http://localhost/api/v1/contacts/1` を入力します。
3. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **200 OK** であること
- `"data"` オブジェクト内に `"category"` と `"tags"` がネストされて含まれること

### 8-4. 新規作成: POST /api/v1/contacts

1. HTTPメソッドを **POST** に変更します。
2. URLに `http://localhost/api/v1/contacts` を入力します。
3. 「Headers」タブをクリックし、以下のヘッダーを追加します。

| KEY | VALUE |
|---|---|
| Accept | application/json |

4. 「Body」タブをクリックし、「raw」を選択、ドロップダウンで「JSON」を選びます。
5. 以下のJSONを入力します。

```json
{
    "first_name": "テスト",
    "last_name": "太郎",
    "gender": 1,
    "email": "test-api@example.com",
    "tel": "09012345678",
    "address": "東京都渋谷区1-1-1",
    "building": "テストビル101",
    "category_id": 1,
    "detail": "APIからの問い合わせです",
    "tag_ids": [1, 2]
}
```

6. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **201 Created** であること
- レスポンスボディに作成されたリソースのJSONが返ること
- `"tags"` 配列に指定したタグが含まれていること
- phpMyAdminで `contacts` テーブルにレコードが追加され、`contact_tag` テーブルにも紐付けレコードが作成されていること

### 8-5. 更新: PUT /api/v1/contacts/{id}

1. HTTPメソッドを **PUT** に変更します。
2. URLに `http://localhost/api/v1/contacts/1` を入力します（`1` は更新したいContactのIDに置き換えてください）。
3. 「Headers」タブで `Accept: application/json` ヘッダーが設定されていることを確認します。
4. 「Body」タブで「raw」→「JSON」を選択し、以下のJSONを入力します。

```json
{
    "first_name": "更新",
    "last_name": "太郎",
    "gender": 2,
    "email": "updated@example.com",
    "tel": "08011112222",
    "address": "大阪府大阪市1-2-3",
    "category_id": 1,
    "detail": "更新されたお問い合わせです",
    "tag_ids": [3, 4]
}
```

5. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **200 OK** であること
- レスポンスボディに更新後のデータが反映されていること
- `"tags"` 配列が更新後のタグ（`tag_ids` で指定したもの）に置き換わっていること（`sync()` による同期）

### 8-6. 削除: DELETE /api/v1/contacts/{id}

1. HTTPメソッドを **DELETE** に変更します。
2. URLに `http://localhost/api/v1/contacts/1` を入力します（`1` は削除したいContactのIDに置き換えてください）。
3. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **204 No Content** であること
- レスポンスボディが空であること
- phpMyAdminで該当レコードが削除されていること

### 8-7. 存在しないIDへのアクセス（404確認）

1. HTTPメソッドを **GET** に設定します。
2. URLに `http://localhost/api/v1/contacts/9999` を入力します。
3. 「Headers」タブで `Accept: application/json` ヘッダーが設定されていることを確認します。
4. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **404 Not Found** であること
- レスポンスボディが以下のJSONであること

```json
{
    "error": "お問い合わせが見つかりませんでした。"
}
```

### 8-8. バリデーションエラーの確認（422確認）

1. HTTPメソッドを **POST** に変更します。
2. URLに `http://localhost/api/v1/contacts` を入力します。
3. 「Headers」タブで `Accept: application/json` ヘッダーが設定されていることを確認します。
4. 「Body」タブで「raw」→「JSON」を選択し、空のJSONオブジェクト `{}` を入力します。
5. 「Send」をクリックします。

**確認ポイント:**
- ステータスコードが **422 Unprocessable Entity** であること
- レスポンスボディに `"message"` と `"errors"` オブジェクトが含まれ、各必須フィールドのエラーメッセージが表示されること

> **💡 `Accept: application/json` ヘッダーの重要性**
>
> POST/PUTリクエストでバリデーションエラーが発生した場合、Laravelは `Accept` ヘッダーを確認して応答形式を決定します。`Accept: application/json` がない場合、Laravelはリダイレクト（302）を返してしまいます。Postmanでは「Headers」タブで `Accept: application/json` を明示的に設定しましょう。
>
> なお、GETリクエストやPostmanの「Send」ボタンではデフォルトで `Accept: */*` が送信されますが、GETリクエストの場合はLaravelがJSONを正しく返すため、特に問題ありません。POST/PUT/DELETEの場合は必ず設定してください。

---

## 9. まとめ ✨

お疲れ様でした！このチャプターでは、既存のSSR（Blade）アプリケーションに公開APIを追加し、以下のスキルを習得しました。

- **API Resources** でEloquentモデルをJSON形式に変換する方法
- **API用コントローラー** の設計とWeb版との分離（名前空間の活用）
- **routes/api.php** でのルート定義と `/api` プレフィックスの仕組み
- **HTTPステータスコード** の使い分け（200, 201, 204, 404, 422）
- **エラーハンドリング** （404でのカスタムJSON応答）

> **💡 コラム: 検索ロジックの共通化**
>
> 今回、Web版の `AdminController@index` とAPI版の `Api\V1\ContactController@index` で検索ロジック（keyword, gender, category_id, date のフィルタリング）が重複していることに気づいたかもしれません。
>
> 実務では、このような重複を解消するために以下の手法が使われます：
>
> 1. **Eloquent Local Scope**: Contactモデルに `scopeSearch($query, $filters)` メソッドを定義し、両コントローラーから `Contact::search($filters)` のように呼び出す
> 2. **Service層**: `ContactSearchService` クラスを作成し、検索ロジックをカプセル化する
> 3. **Query Builder クラス**: `ContactQueryBuilder` のような専用クラスで検索条件を組み立てる
>
> これらはリファクタリングのテーマとして、ぜひ挑戦してみてください。テストが書かれていれば、リファクタリング後もテストを実行して既存の動作が壊れていないことを確認できます。

次の最終チャプターでは、これまでに実装してきた全ての機能（Web + API）に対する**自動テスト**を実装していきます。一度書けば何度でも同じ検証を瞬時に実行してくれるテストは、品質保証の強力な武器です。
