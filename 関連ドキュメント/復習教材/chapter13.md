# Chapter 13: 応用機能 - お問い合わせとタグの紐付け

## 🎯 このセクションで学ぶこと

これまでのチャプターで、タグを管理するためのCRUD機能が完成しました。このチャプターでは、その機能を実際に活用し、アプリケーションのコア機能である「お問い合わせ登録」の際に、タグを同時に紐付けられるように機能を拡張します。具体的には、既存の`ContactController`の`store`メソッドと`StoreContactRequest`を改修し、フォームから送られてきたタグIDの配列を受け取り、新しく作成したお問い合わせと関連付ける処理を追加します。このプロセスを通じて、多対多リレーションにおけるデータの関連付け（`attach`メソッド）と、フォームPOSTフローにおけるデータの受け渡しについて深く学びます。

## 1. はじめに 📖

### `attach()` vs `sync()` vs `syncWithoutDetaching()`

多対多リレーションでデータを紐付ける際、Laravelはいくつかの便利なメソッドを提供しています。それぞれの違いを理解し、適切に使い分けることが重要です。

-   **`attach(id)`**: 中間テーブルに新しいレコードを追加します。単純に新しい関連を追加したい場合に使用します。すでに同じ関連が存在する場合、重複して追加されてしまう可能性があるため、複合ユニークキー制約と組み合わせることが推奨されます。
-   **`sync([id1, id2, ...])`**: 中間テーブルの状態を、引数で渡されたIDの配列と完全に同期させます。配列に含まれていない既存の関連は削除（`detach`）され、配列に新しく含まれた関連は追加（`attach`）されます。お問い合わせの「更新」処理で、タグを完全に入れ替えたい場合に非常に便利です。
-   **`syncWithoutDetaching([id1, id2, ...])`**: `sync`と似ていますが、既存の関連を削除しません。引数の配列に含まれるIDのうち、まだ関連付けられていないものだけを追加します。

今回は、新しいお問い合わせを作成する際にタグを紐付けるので、単純に新しい関連を追加する`attach()`メソッドを使用するのが最も適切です。`sync()`でも結果は同じですが、`attach()`の方が「追加する」という意図が明確になります。

## 2. 要件の確認 📋

このチャプターで改修する機能の具体的な要件を整理します。

### フォームPOSTフロー

本アプリケーションでは、お問い合わせの登録は以下の3ステップで行われます。

1. **入力画面** (`GET /contacts/create`): ユーザーがフォームに情報を入力し、確認ボタンを押す。
2. **確認画面** (`POST /contacts/confirm`): バリデーションを実行し、入力内容を確認画面で表示する。tag_idsなどのデータはhidden inputとして保持する。
3. **保存処理** (`POST /contacts`): 確認画面から送信されたデータをデータベースに保存し、サンクスページにリダイレクトする。

| 機能 | 処理内容 | バリデーション |
| :--- | :--- | :--- |
| **お問い合わせ登録** | 1. お問い合わせ情報（`contacts`テーブル）を保存する。<br>2. フォームで`tag_ids`というキーでIDの配列が送られてきた場合、そのIDのタグを新しいお問い合わせに紐付ける（`contact_tag`テーブルに保存する）。<br>3. 保存完了後、サンクスページ（`/thanks`）にリダイレクトする。 | `StoreContactRequest`に以下のルールを追加する。<br> - `tag_ids`は配列でなければならない。<br> - `tag_ids`配列内の各IDは、`tags`テーブルに実際に存在するIDでなければならない。 |

## 3. 先輩エンジニアの思考プロセス 💭

既存の機能に新しいロジックを追加する際、経験豊富なエンジニアはどのような点に注意を払うのでしょうか。

### Point 1: 影響範囲を最小限に留める

既存のコードを変更する際は、常に「この変更が他にどのような影響を与えるか」を考える必要があります。今回の改修は`ContactController`の`store`メソッドに集中しています。タグの紐付けロジックをこのメソッド内に閉じることで、他のメソッドに意図しない影響が及ぶのを防ぎます。また、タグのIDはオプショナル（任意）なフォームパラメータとして扱うべきです。つまり、`tag_ids`配列がフォームに含まれていなくても、これまで通りお問い合わせの登録は正常に完了するように実装します。これにより、既存のフォーム送信フローを壊さずに、新しい機能を追加できます（**後方互換性**）。

### Point 2: バリデーションは入り口で徹底する

データベースにデータを保存する前に、そのデータが正しい形式であり、かつ整合性が取れていることを保証するのは非常に重要です。`tag_ids`配列として送られてきたIDが、もし`tags`テーブルに存在しないものであった場合、外部キー制約によりデータベースエラーが発生してしまいます。このような事態を防ぐため、`FormRequest`の段階で「`tag_ids`配列の各要素が、`tags`テーブルの`id`カラムに存在すること」を検証します。これは`exists:tags,id`というバリデーションルールで簡単に実現できます。入り口である`FormRequest`で不正なデータをシャットアウトすることで、コントローラー以降のロジックは「データは常に正しい」という前提でシンプルに記述できます。

### Point 3: リレーションメソッドを最大限に活用する

お問い合わせとタグを紐付ける際、`DB::table('contact_tag')->insert(...)`のように手動で中間テーブルを操作することも可能です。しかし、これはEloquent ORMの恩恵を全く受けていない、非常に冗長なやり方です。前チャプターで定義した`tags()`リレーションメソッドを使えば、`$contact->tags()->attach($tagIds)`という一行で、同じことを遥かに直感的かつ安全に行うことができます。このコードは、`$contact`のIDを自動的に取得し、引数で渡された`$tagIds`の各IDと組み合わせて、中間テーブルにレコードを挿入してくれます。常に高レベルな抽象（リレーションメソッド）を利用することを心がけ、低レベルな実装（手動でのテーブル操作）を避けるのが、Eloquentを使いこなす鍵です。

### Point 4: 確認画面でのデータの受け渡しを意識する

従来のSSR（サーバーサイドレンダリング）によるフォーム送信フローでは、入力画面 → 確認画面 → 保存処理 という複数ステップを経由します。このとき、確認画面でユーザーが入力した値を保持し、次の保存処理に正しく渡す必要があります。`tag_ids`のような配列データは、確認画面のBladeテンプレートでhidden inputとして埋め込むことで、次のPOSTリクエストに含めることができます。この「データの受け渡し」を正しく設計することが、SSRフォームフローにおける重要なポイントです。

## 4. 実装 🚀

既存の`StoreContactRequest`と`ContactController`を改修し、Bladeテンプレートでのタグ表示・受け渡しを実装していきます。

### 4.1. `StoreContactRequest`の改修

お問い合わせ登録時のリクエストに`tag_ids`配列を含められるようにし、そのバリデーションルールを追加します。

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

### 4.2. `ContactController`の改修

Chapter 8で作成した`ContactController`に、タグ関連の処理を追加します。変更が必要なのは`index`、`confirm`、`store`の3メソッドです。

**`app/Http/Controllers/ContactController.php`**

まず、ファイル上部のuse文に`Tag`モデルを追加します。

```php
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag; // 追加
```

次に、各メソッドを以下のように改修します。

```php
public function index()
{
    $categories = Category::all();
    $tags = Tag::all(); // 追加

    return view('contact.index', compact('categories', 'tags')); // 'tags' を追加
}

public function confirm(StoreContactRequest $request)
{
    $validated = $request->validated();
    $category = Category::find($validated['category_id']);
    $tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect(); // 追加

    return view('contact.confirm', compact('validated', 'category', 'tags')); // 'tags' を追加
}

public function store(StoreContactRequest $request)
{
    $validated = $request->validated();
    $tagIds = $validated['tag_ids'] ?? []; // 追加
    unset($validated['tag_ids']); // 追加

    $contact = Contact::create($validated); // 変更: 戻り値を変数に格納

    // 追加: タグの紐付け
    if (! empty($tagIds)) {
        $contact->tags()->attach($tagIds);
    }

    return redirect('/thanks');
}
```

### 4.3. 入力フォームでのタグ表示（`_form.blade.php`）

提供Bladeファイル（`_form.blade.php`）には、タグのチェックボックス表示が `@isset($tags)` で囲まれた状態で既に記載されています。基礎機能フェーズではコントローラーから `$tags` を渡していなかったため非表示でしたが、上記の `index` メソッドで `$tags` を渡すようにしたことで、自動的にタグのチェックボックスが表示されるようになります。

**`resources/views/contact/_form.blade.php`（タグ部分の抜粋）**
```blade
@isset($tags)
<div class="grid grid-cols-3 gap-8 mb-4">
    <!-- ... ラベル部分省略 ... -->
    <div class="col-span-2">
        <div class="flex flex-wrap gap-4 py-3">
            @foreach ($tags as $tag)
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                        {{ in_array($tag->id, old('tag_ids', [])) ? 'checked' : '' }} />
                    <span class="ml-2 text-gray-700">{{ $tag->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
@endisset
```

ここでのポイントは以下の通りです。

-   **`@isset($tags)`**: `$tags` 変数がコントローラーから渡されている場合のみ、タグセクションを表示します。基礎機能フェーズでは非表示だったものが、このチャプターで `$tags` を渡すことで表示されるようになります。
-   **`$tags`はサーバーサイドから渡される**: `ContactController@index`で`Tag::all()`を取得し、`compact('tags')`でビューに渡しています。
-   **`name="tag_ids[]"`**: `[]`を付けることで、チェックされた複数の値がPHP側で配列として受け取れます。
-   **`old('tag_ids', [])`**: バリデーションエラー時に、以前選択していたタグを復元するために`old()`ヘルパーを使用しています。

### 4.4. 確認画面でのhidden inputによるデータの受け渡し

確認画面（`confirm.blade.php`）でも、タグ名の表示部分は `@isset($tags)` で囲まれており、`confirm` メソッドから `$tags` を渡すことで自動的に表示されます。また、次の`store`アクションにデータを渡すためにhidden inputとして埋め込みます。`tag_ids`は配列なので、`@foreach`でループして1つずつhidden inputを出力します。

**`resources/views/contact/confirm.blade.php`（tag_ids部分の抜粋）**
```blade
<form action="/contacts" method="POST">
    @csrf

    {{-- 他のフィールドのhidden input（省略） --}}
    <input type="hidden" name="first_name" value="{{ $validated['first_name'] }}">
    <input type="hidden" name="last_name" value="{{ $validated['last_name'] }}">
    {{-- ... --}}

    {{-- タグIDのhidden input --}}
    @if (!empty($validated['tag_ids']))
        @foreach ($validated['tag_ids'] as $tagId)
            <input type="hidden" name="tag_ids[]" value="{{ $tagId }}">
        @endforeach
    @endif

    <button type="submit">送信</button>
</form>
```

ここでのポイントは以下の通りです。

-   **`@if (!empty(...))`で存在チェック**: `tag_ids`はオプショナルなので、存在しない場合にエラーにならないよう、`!empty()`で囲んでいます。
-   **`@foreach`で1つずつ出力**: `tag_ids`は配列なので、1つのhidden inputにまとめることはできません。`@foreach`で配列の各要素に対して個別のhidden inputを出力します。
-   **`name="tag_ids[]"`**: 入力フォームと同様に`[]`を付けることで、POST先のコントローラーで配列として受け取れます。

### 4.5. `AdminController`の改修（Eager Loading）

管理画面でお問い合わせ一覧を表示する際には、紐づいたタグ情報も一緒に取得する必要があります。`AdminController@index`の`with()`メソッドにタグを追加し、ビューにもタグデータを渡します。

**`app/Http/Controllers/AdminController.php`（変更箇所）**

まず、ファイル上部のuse文に`Tag`モデルを追加します。

```php
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag; // 追加
```

次に、`index`メソッドと`show`メソッドの該当箇所を変更します。

```php
// index メソッド内
$query = Contact::with(['category', 'tags']); // 変更: 'category' → ['category', 'tags']

// ... 検索ロジックはそのまま ...

$contacts = $query->latest()->paginate(7);
$categories = Category::all();
$tags = Tag::all(); // 追加

return view('admin.index', compact('contacts', 'categories', 'tags')); // 'tags' を追加
```

```php
// show メソッド内
$contact->load(['category', 'tags']); // 変更: 'category' → ['category', 'tags']
```

管理画面のBladeテンプレート（`admin/index.blade.php`）では、タグ管理セクションが `@isset($tags)` で囲まれています。`AdminController@index` から `$tags` を渡すことで、タグの一覧表示・追加・編集・削除のUIが自動的に表示されるようになります。また、お問い合わせ一覧のタグ列も `method_exists` で防御されているため、`tags()` リレーション定義後に自動的に表示されます。

## 5. コードの詳細解説 🔍

### `StoreContactRequest`のバリデーションルール

-   **`'tag_ids' => ['nullable', 'array']`**: `tag_ids`フィールドはリクエストに含まれていなくても良い（`nullable`）が、もし含まれている場合は配列でなければならない（`array`）、というルールです。
-   **`'tag_ids.*' => ['integer', 'exists:tags,id']`**: `*`はワイルドカードで、「`tag_ids`配列の全ての要素」を意味します。つまり、`tag_ids`配列に含まれる各IDが整数（`integer`）であり、かつ`tags`テーブルの`id`カラムに実際に存在するかどうかを検証します。これにより、存在しないタグIDが送られてくるのを防ぎます。

### `ContactController`の`store`メソッド

-   **`$validated = $request->validated();`**: `StoreContactRequest`でバリデーション済みのデータを取得します。
-   **`$tagIds = $validated['tag_ids'] ?? [];`**: `validated`データから`tag_ids`キーの値を取得します。もし`tag_ids`が存在しない場合は、null合体演算子（`??`）を使って空の配列`[]`をデフォルト値として設定します。これにより、タグが選択されていない場合でもエラーなく処理を続行できます。
-   **`unset($validated['tag_ids']);`**: `Contact::create()`メソッドは、`contacts`テーブルに存在するカラムのデータのみを受け付けます。`validated`データには`tag_ids`が含まれていますが、これは`contacts`テーブルのカラムではないため、このまま`create`メソッドに渡すとエラーになります。そこで、`unset()`を使って`validated`配列から`tag_ids`キーを事前に削除します。
-   **`$contact = Contact::create($validated);`**: `tag_ids`が削除された`validated`データを使って、`contacts`テーブルに新しいレコードを作成します。
-   **`if (! empty($tagIds)) { ... }`**: `$tagIds`配列が空でない場合（つまり、ユーザーが少なくとも1つのタグを選択した場合）のみ、紐付け処理を実行します。
-   **`$contact->tags()->attach($tagIds);`**: `create`メソッドで返された`$contact`モデルインスタンスのリレーションメソッド`tags()`を呼び出し、`attach()`メソッドでタグを紐付けます。`attach()`の引数には、タグIDの配列を渡します。
-   **`return redirect('/thanks');`**: 保存が完了したら、サンクスページにリダイレクトします。SSRアプリケーションでは、フォーム送信後にリダイレクトを行うのが一般的です（PRG: Post/Redirect/Getパターン）。これにより、ブラウザの再読み込みで重複送信が起きるのを防ぎます。

### `confirm`メソッドとhidden inputによるデータの受け渡し

-   **`confirm`メソッド**: `StoreContactRequest`でバリデーションを実行し、通過した場合に確認画面のビューを返します。バリデーション済みのデータを`$validated`として確認画面に渡します。`tag_ids`が含まれている場合は`Tag::whereIn()`で該当するタグのモデルを取得し、確認画面でタグ名を表示できるようにしています。
-   **hidden inputの役割**: 確認画面はあくまで「表示」のための画面です。ユーザーが「送信」ボタンを押したとき、改めてPOSTリクエストが`store`メソッドに送られます。このとき、入力データを引き継ぐために、hidden inputとしてフォームに埋め込んでおく必要があります。
-   **配列データのhidden input**: `tag_ids`のような配列データは、1つのhidden inputでは表現できません。`@foreach`で配列をループし、各要素ごとに`<input type="hidden" name="tag_ids[]" value="{{ $tagId }}">`を出力することで、POST先で配列として受け取れるようにします。

### Eager LoadingとN+1問題

-   **`Contact::with(['category', 'tags'])`**: 管理画面の`index`メソッドで`paginate`を呼び出す前に、`with()`メソッドで`category`と`tags`のリレーションをEager Loadingしています。これにより、`Contact`を取得する最初のクエリの際に、関連する`Category`と`Tag`のデータもまとめて取得します。もしこれをしないと、各`Contact`の`tags`にアクセスするたびにクエリが発行され、10件のお問い合わせがあれば1+10回のクエリ（N+1問題）が発生してしまいます。
-   **Bladeテンプレートでの利用**: Eager Loadingされたリレーションは、Bladeテンプレート内で`$contact->tags`のようにプロパティアクセスするだけで利用できます。追加のクエリは発行されません。

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
LaravelのSSRフォームフロー（入力 → 確認 → 保存）について、私の理解はこうです：
「入力画面でフォームに入力し、確認画面でバリデーション＆表示を行い、hidden inputで値を保持した上で、保存処理にPOSTする。保存後はリダイレクトでサンクスページに飛ばす（PRGパターン）。」

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
制約は「Laravel 10.x, PHP 8.2, SSR（Blade）でのフォーム送信フロー」です。
前提知識は「Laravelの基本的なCRUD操作、多対多リレーションの概念」です。

次の順番で出力してください：

A. 実装の手順・方針
- まず全体の方針（なぜそのやり方か）
- 手順を1〜Nで（各手順に「できたらOK」の条件も書く）

B. 関連技術の解説
- 必要な関連知識を3〜7個
- 各項目は「一言で説明 → この実装で何に使う → 注意点」

C. 実装例
- フォーム入力画面でのタグ表示（@foreachによるチェックボックス描画）
- 確認画面でのhidden inputによるtag_idsの受け渡し
- storeメソッドでのattach処理とリダイレクト

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
  - フォームの`tag_ids`配列でタグIDを受け取る
  - `tag_ids`はオプショナル
  - 存在しないタグIDが送られてきたらエラーにする
  - フォーム → 確認画面（hidden input） → 保存処理（attach + redirect）のフロー
- 制約：Laravel 10.x, SSR（Blade）
- 設計案：
  - `StoreContactRequest`で`tag_ids`が配列であること、各IDが`tags`テーブルに存在することをバリデーションする
  - `ContactController@confirm`でバリデーション後、確認画面ビューにデータを渡す
  - 確認画面ではtag_idsを@foreachでhidden inputとして埋め込む
  - `ContactController@store`で、まず`Contact::create()`を実行する
  - その後、`if`文で`tag_ids`が存在すれば`$contact->tags()->attach($tagIds)`を実行する
  - 保存完了後、`redirect('/thanks')`でサンクスページにリダイレクトする
- 不安な点：
  - `attach()`で重複したレコードが作られないか？
  - hidden inputで配列データを正しく渡せるか？
  - 確認画面と保存処理で2回バリデーションが走るのは冗長ではないか？

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

## 7. 動作確認 🔍

タグ紐付け機能が正しく動作するか、以下を確認してください。

**お問い合わせフォーム**
1. `http://localhost/`にアクセスし、タグのチェックボックスが表示されること
2. タグを選択してフォームを送信し、確認画面で選択したタグ名が表示されること
3. 確認画面で「送信」を押し、サンクスページが表示されること
4. phpMyAdminの`contact_tag`テーブルに、選択したタグとの紐付けデータが登録されていること

**管理画面**
5. 管理画面の一覧ページで、各お問い合わせに紐づくタグが表示されること
6. 詳細ページでも、タグが正しく表示されること

## 8. まとめ ✨

このチャプターでは、既存のお問い合わせ登録機能を拡張し、タグを同時に紐付ける処理を実装しました。

-   **フォームPOSTフローにおけるデータの受け渡し**: 入力画面 → 確認画面（hidden input） → 保存処理（redirect）という、SSRアプリケーションにおける典型的なフォーム送信フローを理解しました。配列データ（`tag_ids`）をhidden inputで正しく受け渡す方法を学びました。
-   **多対多リレーションのデータ操作**: `attach()`メソッドを使い、中間テーブルに新しい関連を簡単に追加する方法を実践しました。
-   **配列のバリデーション**: `tag_ids.*`や`exists`ルールを組み合わせ、フォームから送られてくるID配列の妥当性を検証する方法を学びました。
-   **Eager LoadingとN+1問題**: 管理画面の一覧表示において、`with()`メソッドでリレーションを事前読み込みすることの重要性を理解しました。
-   **PRGパターン**: Post/Redirect/Getパターンにより、フォーム送信後のリダイレクトでブラウザの再読み込みによる重複送信を防ぐ方法を学びました。
-   **データの前処理**: `unset()`を使って、データベーステーブルに存在しないキーを削除してから`create()`メソッドに渡す方法を学びました。

これで、タグ機能のバックエンド実装はほぼ完了です。次のチャプターでは、検索結果をCSVファイルとしてエクスポートする機能を実装します。
