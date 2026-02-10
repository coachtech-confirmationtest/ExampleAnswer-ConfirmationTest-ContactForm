# Chapter 13: 応用機能 - お問い合わせとタグの紐付け

## 🎯 このセクションで学ぶこと

これまでのチャプターで、タグを管理するためのCRUD APIが完成しました。このチャプターでは、そのAPIを実際に活用し、アプリケーションのコア機能である「お問い合わせ登録」の際に、タグを同時に紐付けられるように機能を拡張します。具体的には、既存の`ContactController`の`store`メソッドと`StoreContactRequest`を改修し、リクエストで送られてきたタグIDの配列を受け取り、新しく作成したお問い合わせと関連付ける処理を追加します。このプロセスを通じて、多対多リレーションにおけるデータの関連付け（`attach`メソッド）と、データベーストランザクションの重要性について深く学びます。

## 1. はじめに 📖

### `attach()` vs `sync()` vs `syncWithoutDetaching()`

多対多リレーションでデータを紐付ける際、Laravelはいくつかの便利なメソッドを提供しています。それぞれの違いを理解し、適切に使い分けることが重要です。

-   **`attach(id)`**: 中間テーブルに新しいレコードを追加します。単純に新しい関連を追加したい場合に使用します。すでに同じ関連が存在する場合、重複して追加されてしまう可能性があるため、複合ユニークキー制約と組み合わせることが推奨されます。
-   **`sync([id1, id2, ...])`**: 中間テーブルの状態を、引数で渡されたIDの配列と完全に同期させます。配列に含まれていない既存の関連は削除（`detach`）され、配列に新しく含まれた関連は追加（`attach`）されます。お問い合わせの「更新」処理で、タグを完全に入れ替えたい場合に非常に便利です。
-   **`syncWithoutDetaching([id1, id2, ...])`**: `sync`と似ていますが、既存の関連を削除しません。引数の配列に含まれるIDのうち、まだ関連付けられていないものだけを追加します。

今回は、新しいお問い合わせを作成する際にタグを紐付けるので、単純に新しい関連を追加する`attach()`メソッドを使用するのが最も適切です。`sync()`でも結果は同じですが、`attach()`の方が「追加する」という意図が明確になります。

## 2. 要件の確認 📋

このチャプターで改修する機能の具体的な要件を整理します。

| 機能 | 処理内容 | バリデーション |
| :--- | :--- | :--- |
| **お問い合わせ登録** | 1. お問い合わせ情報（`contacts`テーブル）を保存する。<br>2. リクエストで`tags`というキーでIDの配列が送られてきた場合、そのIDのタグを新しいお問い合わせに紐付ける（`contact_tag`テーブルに保存する）。<br>3. 上記の2つの処理を、データベーストランザクション内で実行する。 | `StoreContactRequest`に以下のルールを追加する。<br> - `tags`は配列でなければならない。<br> - `tags`配列内の各IDは、`tags`テーブルに実際に存在するIDでなければならない。 |

## 3. 先輩エンジニアの思考プロセス 💭

既存の機能に新しいロジックを追加する際、経験豊富なエンジニアはどのような点に注意を払うのでしょうか。

### Point 1: 影響範囲を最小限に留める

既存のコードを変更する際は、常に「この変更が他にどのような影響を与えるか」を考える必要があります。今回の改修は`ContactController`の`store`メソッドに集中しています。タグの紐付けロジックをこのメソッド内に閉じることで、`update`や`destroy`といった他のメソッドに意図しない影響が及ぶのを防ぎます。また、タグのIDはオプショナル（任意）なリクエストパラメータとして扱うべきです。つまり、`tags`配列がリクエストに含まれていなくても、これまで通りお問い合わせの登録は正常に完了するように実装します。これにより、既存のAPIクライアント（このAPIを利用している他のプログラム）を壊さずに、新しい機能を追加できます（**後方互換性**）。

### Point 2: バリデーションは入り口で徹底する

データベースにデータを保存する前に、そのデータが正しい形式であり、かつ整合性が取れていることを保証するのは非常に重要です。`tags`配列として送られてきたIDが、もし`tags`テーブルに存在しないものであった場合、外部キー制約によりデータベースエラーが発生してしまいます。このような事態を防ぐため、`FormRequest`の段階で「`tags`配列の各要素が、`tags`テーブルの`id`カラムに存在すること」を検証します。これは`exists:tags,id`というバリデーションルールで簡単に実現できます。入り口である`FormRequest`で不正なデータをシャットアウトすることで、コントローラー以降のロジックは「データは常に正しい」という前提でシンプルに記述できます。

### Point 3: リレーションメソッドを最大限に活用する

お問い合わせとタグを紐付ける際、`DB::table(\'contact_tag\')->insert(...)`のように手動で中間テーブルを操作することも可能です。しかし、これはEloquent ORMの恩恵を全く受けていない、非常に冗長なやり方です。前チャプターで定義した`tags()`リレーションメソッドを使えば、`$contact->tags()->attach($tagIds)`という一行で、同じことを遥かに直感的かつ安全に行うことができます。このコードは、`$contact`のIDを自動的に取得し、引数で渡された`$tagIds`の各IDと組み合わせて、中間テーブルにレコードを挿入してくれます。常に高レベルな抽象（リレーションメソッド）を利用することを心がけ、低レベルな実装（手動でのテーブル操作）を避けるのが、Eloquentを使いこなす鍵です。

## 4. 実装 🚀

既存の`StoreContactRequest`と`ContactController`を改修していきます。

### 4.1. `StoreContactRequest`の改修

お問い合わせ登録時のリクエストに`tags`配列を含められるようにし、そのバリデーションルールを追加します。

**`app/Http/Requests/StoreContactRequest.php`**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'category_id' => ['required', 'exists:categories,id'],
            'detail' => ['required', 'string', 'max:120'],
            // タグに関する記述を追加
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

### 4.2. `ContactController`の`store`メソッド改修

`store`メソッド内でデータベーストランザクションを使用し、お問い合わせの作成とタグの紐付けをアトミックな操作として実行します。

**`app/Http/Controllers/Api/ContactController.php`**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Response;

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
        return ContactResource::collection($contacts);
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);

        if (!empty($tagIds)) {
            $contact->tags()->attach($tagIds);
        }

        return response()->json(null, Response::HTTP_CREATED);
    }

    public function show(Contact $contact)
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
```

### 4.3. `ContactResource`の改修

お問い合わせの詳細や一覧を取得する際に、紐づいているタグの情報も一緒にJSONに含めるように`ContactResource`を改修します。

**`app/Http/Resources/ContactResource.php`**
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
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'email' => $this->email,
            'tel' => $this->tel,
            'address' => $this->address,
            'building' => $this->building,
            'detail' => $this->detail,
        ];
    }
}
```

## 5. コードの詳細解説 🔍

### `StoreContactRequest`のバリデーションルール

-   **`'tag_ids' => ['nullable', 'array']`**: `tag_ids`フィールドはリクエストに含まれていなくても良い（`nullable`）が、もし含まれている場合は配列でなければならない（`array`）、というルールです。
-   **`'tag_ids.*' => ['integer', 'exists:tags,id']`**: `*`はワイルドカードで、「`tag_ids`配列の全ての要素」を意味します。つまり、`tag_ids`配列に含まれる各IDが整数（`integer`）であり、かつ`tags`テーブルの`id`カラムに実際に存在するかどうかを検証します。これにより、存在しないタグIDが送られてくるのを防ぎます。

### `ContactController`の`store`メソッド

-   **`$validated = $request->validated();`**: `StoreContactRequest`でバリデーション済みのデータを取得します。
-   **`$tagIds = $validated['tag_ids'] ?? [];`**: `validated`データから`tag_ids`キーの値を取得します。もし`tag_ids`が存在しない場合は、null合体演算子（`??`）を使って空の配列`[]`をデフォルト値として設定します。これにより、タグが選択されていない場合でもエラーなく処理を続行できます。
-   **`unset($validated['tag_ids']);`**: `Contact::create()`メソッドは、`contacts`テーブルに存在するカラムのデータのみを受け付けます。`validated`データには`tag_ids`が含まれていますが、これは`contacts`テーブルのカラムではないため、このまま`create`メソッドに渡すとエラーになります。そこで、`unset()`を使って`validated`配列から`tag_ids`キーを事前に削除します。
-   **`$contact = Contact::create($validated);`**: `tag_ids`が削除された`validated`データを使って、`contacts`テーブルに新しいレコードを作成します。
-   **`if (!empty($tagIds)) { ... }`**: `$tagIds`配列が空でない場合（つまり、ユーザーが少なくとも1つのタグを選択した場合）のみ、紐付け処理を実行します。
-   **`$contact->tags()->attach($tagIds);`**: `create`メソッドで返された`$contact`モデルインスタンスのリレーションメソッド`tags()`を呼び出し、`attach()`メソッドでタグを紐付けます。`attach()`の引数には、タグIDの配列を渡します。

### `ContactResource`とEager Loading

-   **`'tags' => TagResource::collection($this->whenLoaded('tags'))`**: `ContactResource`にタグの情報を追加します。`whenLoaded('tags')`は、「`tags`リレーションがEager Loadingされている場合にのみ、この`tags`キーをJSONに含める」という条件分岐です。これにより、意図せずN+1問題を引き起こすのを防ぐことができます。
-   **`$query->with(['category', 'tags'])`**: `index`メソッドで`paginate`を呼び出す前に、`with()`メソッドで`category`と`tags`のリレーションをEager Loadingしています。これにより、`Contact`を取得する最初のクエリの際に、関連する`Category`と`Tag`のデータもまとめて取得します。もしこれをしないと、各`Contact`の`tags`にアクセスするたびにクエリが発行され、10件のお問い合わせがあれば1+10回のクエリ（N+1問題）が発生してしまいます。

## 6. How to: この実装にたどり着くための調べ方 🗺️

### Step 1: 公式ドキュメントを読みやすくまとめる

**プロンプト例**
```
以下はLaravelの公式ドキュメントの一部です。 これを「実装できるように」分かりやすくまとめてください。

出力してほしい内容：
- 重要ポイント（10行以内）
- 用語の説明（重要なものだけ）
- できること / できないこと（境界をはっきり）
- よくある落とし穴（回避策つき）
- 最小で動かすための手順（コードはまだ不要）

--- ここから ---
{ここに公式ドキュメントの「Eloquent: Relationships」の「Attaching / Detaching」セクションを貼り付ける}
--- ここまで ---
```

### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

**プロンプト例**
```
Laravelのデータベーストランザクションについて、私の理解はこうです：
「複数のデータベース操作を一つのグループにまとめる機能。グループ内のどれか一つでも失敗したら、全部まとめて取り消してくれる（ロールバック）。これにより、データの不整合を防げる。」

お願い：
1) 正しいかチェックして、間違いがあれば「反例」で教えてください
2) 仕組みを「入力→中で起きること→出力」で説明してください
3) どこまでがこの概念の範囲か（境界）を教えてください
4) よくある勘違いを3つ教えてください
5) 理解チェック問題を3問ください（答えつき）
```

### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

**プロンプト例**
```
目的は「お問い合わせ登録時に、タグも一緒に登録できるようにする」ことです。
制約は「Laravel 10.x, PHP 8.2」です。
前提知識は「Laravelの基本的なCRUD操作、多対多リレーションの概念」です。

次の順番で出力してください：

A. 実装の手順・方針
- まず全体の方針（なぜそのやり方か）
- 手順を1〜Nで（各手順に「できたらOK」の条件も書く）

B. 関連技術の解説
- 必要な関連知識を3〜7個
- 各項目は「一言で説明 → この実装で何に使う → 注意点」

C. 実装例
- まず最小で動く例
- 次に実務向けの拡張例（トランザクション処理を追加）

D. コードの解説
- 重要な部分だけ「何をしてるか」「なぜそう書くか」
- よくあるバグと対策

追加で必要な情報があれば質問していいですが、最大3つまでにしてください。
```

### Step 4: 設計レビュー（指摘をもらう）

**プロンプト例**
```
以下の設計をレビューしてください。

- 目的：お問い合わせ登録時にタグを紐付ける
- 要件：
  - リクエストの`tag_ids`配列でタグIDを受け取る
  - `tag_ids`はオプショナル
  - 存在しないタグIDが送られてきたらエラーにする
  - お問い合わせ登録とタグ紐付けはアトミックな操作であること
- 制約：Laravel 10.x
- 設計案：
  - `StoreContactRequest`で`tag_ids`が配列であること、各IDが`tags`テーブルに存在することをバリデーションする
  - `ContactController@store`で、まず`Contact::create()`を実行する
  - その後、`if`文で`tag_ids`が存在すれば`$contact->tags()->attach($tagIds)`を実行する
  - 上記の処理全体を`DB::transaction()`クロージャで囲む
- 不安な点：
  - `attach()`で重複したレコードが作られないか？
  - もっと効率的な書き方はないか？
  - トランザクションの分離レベルは考慮すべきか？

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

## 7. まとめ ✨

このチャプターでは、既存のお問い合わせ登録機能を拡張し、タグを同時に紐付ける処理を実装しました。

-   **多対多リレーションのデータ操作**: `attach()`メソッドを使い、中間テーブルに新しい関連を簡単に追加する方法を実践しました。
-   **配列のバリデーション**: `tag_ids.*`や`exists`ルールを組み合わせ、リクエストで送られてくるID配列の妥当性を検証する方法を学びました。
-   **Eager LoadingとN+1問題**: `with()`メソッドでリレーションを事前読み込みすることの重要性と、`whenLoaded()`で安全にリソースを構築する方法を理解しました。
-   **データの前処理**: `unset()`を使って、データベーステーブルに存在しないキーを削除してから`create()`メソッドに渡す方法を学びました。

これで、タグ機能のバックエンド実装はほぼ完了です。次の最終チャプターでは、応用機能の最後として、検索結果をCSVファイルとしてエクスポートする機能を実装します。
