# Chapter 7: Blade テンプレートへのデータ渡しとサーバーサイド描画

## 🎯 このセクションで学ぶこと

このチャプターでは、コントローラーからBladeテンプレートにデータを渡し、サーバーサイドでHTMLを描画する仕組みについて学びます。Laravelの伝統的なSSR（サーバーサイドレンダリング）パターンを理解し、`compact()`や`view()`ヘルパーを使ったデータの受け渡し、Bladeディレクティブ（`@foreach`など）を使った動的なHTML生成、そして確認画面でのhidden inputによるデータ保持の方法を習得します。

## 1. はじめに 📖

### サーバーサイド描画とは？コントローラーとBladeの「連携プレイ」

Webアプリケーションには、大きく分けて2つのアーキテクチャパターンがあります。

1. **API駆動型（SPA方式）**: サーバーはJSON形式のデータだけを返し、フロントエンドのJavaScript（ReactやVueなど）がそのJSONを受け取ってHTMLを組み立てる方式。サーバーとフロントエンドが完全に分離されます。
2. **サーバーサイドレンダリング型（SSR方式）**: サーバー側でデータベースからデータを取得し、そのデータをテンプレートエンジン（Blade）に渡して、完成したHTMLをブラウザに返す方式。LaravelではこのSSR方式が伝統的かつ最も一般的なパターンです。

本プロジェクトでは、このSSR方式を採用しています。ユーザーがフォームに入力したデータは、通常のHTTPフォーム送信（POST）でサーバーに送られ、コントローラーが処理し、Bladeテンプレートを使ってHTMLを生成してブラウザに返します。APIリソースやJSONレスポンスは使用しません。

このSSR方式の最大の利点は、**シンプルさ**にあります。フロントエンドとバックエンドが一つのLaravelプロジェクト内で完結するため、別途フロントエンドアプリケーションを構築・管理する必要がありません。お問い合わせフォームのような、比較的シンプルなWebアプリケーションには最適なアーキテクチャです。

このチャプターでは、コントローラーからBladeテンプレートにデータを渡す方法と、Blade側でそのデータを使って動的にHTMLを描画する方法を、実際のコードを通じて学んでいきます。

## 2. 要件の確認 📋

このアプリケーションのお問い合わせフォームでは、以下のデータの流れを実現する必要があります。

### 入力画面（`contact.index`）

- データベースから全カテゴリー（`categories`）と全タグ（`tags`）を取得する。
- それらをBladeテンプレートに渡し、`<select>`要素と`<input type="checkbox">`要素を動的に生成する。
- ユーザーがカテゴリーやタグを選択できるフォームを表示する。

### 確認画面（`contact.confirm`）

- ユーザーが入力・選択したデータをバリデーション済みの状態で受け取る。
- 選択された`category_id`から`Category`モデルを取得し、カテゴリー名を表示する。
- 選択された`tag_ids`から`Tag`モデルのコレクションを取得し、タグ名を表示する。
- すべてのデータを`<input type="hidden">`に保持し、最終送信時に再度サーバーに送れるようにする。

### データの流れの全体像

```
[入力画面]              [確認画面]              [保存処理]
GET /                   POST /contacts/confirm   POST /contacts
    |                       |                       |
    v                       v                       v
ContactController       ContactController       ContactController
  @index                  @confirm                @store
    |                       |                       |
    | $categories           | $validated            | Contact::create()
    | $tags                 | $category             | $contact->tags()->attach()
    v                       | $tags                 |
contact.index               v                       v
  └─ _form.blade.php   contact.confirm         redirect('/thanks')
```

## 3. 先輩エンジニアの思考プロセス 💭

なぜこのようなデータの渡し方をするのか？その背景にある設計思想を学びましょう。

### Point 1: コントローラーは「必要最小限のデータ」だけをビューに渡す

コントローラーの役割は、ビューが描画に必要とするデータを過不足なく準備し、渡すことです。例えば、入力フォームではカテゴリーとタグの一覧が必要なので、`Category::all()`と`Tag::all()`を取得してビューに渡します。ビューが必要としないデータ（例えば、ユーザー一覧など）まで渡してしまうと、不要なデータベースクエリが発生してパフォーマンスが低下するだけでなく、テンプレート側でどの変数が使えるのかが分かりにくくなり、保守性が下がります。「このビューは何のデータを必要としているか？」を常に意識し、必要なものだけを渡すことが大切です。

### Point 2: Bladeの`@foreach`でHTMLを動的に生成する

カテゴリーやタグの選択肢は、データベースに格納されている動的なデータです。これをHTMLにハードコーディングしてしまうと、カテゴリーやタグが追加・変更されるたびにBladeファイルを手動で修正する必要があり、保守性が著しく低下します。`@foreach`ディレクティブを使ってモデルのコレクションをループ処理することで、データベースの内容が変わっても自動的にフォームの選択肢が更新されます。これにより、「データの変更はデータベースの管理だけで完結する」という理想的な状態を実現できます。

### Point 3: 確認画面ではhidden inputで「データを保持」する

通常のHTTPリクエストはステートレス（状態を持たない）です。つまり、入力画面から確認画面に遷移した時点で、入力データはサーバー側には保持されていません。確認画面から最終的にデータを保存するために、ユーザーが入力した全データを`<input type="hidden">`としてフォームに埋め込み、次のPOSTリクエストで再度サーバーに送信する必要があります。このパターンは、セッションにデータを保存する方法と並んで、確認画面付きフォームの最も一般的な実装方法です。

### Point 4: IDだけでなく「表示用データ」もビューに渡す

確認画面では、ユーザーが選択したカテゴリーやタグの「名前」を表示する必要があります。しかし、フォームから送られてくるのは`category_id`や`tag_ids`というIDの値だけです。コントローラーの`confirm`メソッドで、そのIDを使ってデータベースから`Category`モデルや`Tag`モデルを取得し、表示用のデータとしてビューに渡します。一方、hidden inputにはIDの値を保持します。このように「表示用のデータ」と「送信用のデータ」を分けて考えることが、確認画面の実装では重要です。

## 4. 実装 🚀

それでは、コントローラーからBladeにデータを渡し、サーバーサイドで描画する実装を見ていきましょう。

### 4.1. コントローラーでデータを取得しビューに渡す

`app/Http/Controllers/ContactController.php`の`index`メソッドと`confirm`メソッドを実装します。

#### `index`メソッド：入力フォームの表示

```php
public function index()
{
    $categories = Category::all();
    $tags = Tag::all();

    return view('contact.index', compact('categories', 'tags'));
}
```

#### `confirm`メソッド：確認画面の表示

```php
public function confirm(StoreContactRequest $request)
{
    $validated = $request->validated();
    $category = Category::find($validated['category_id']);
    $tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();

    return view('contact.confirm', compact('validated', 'category', 'tags'));
}
```

### 4.2. 入力フォームのBladeテンプレート

`resources/views/contact/index.blade.php`は、フォームの部分テンプレート`_form.blade.php`を`@include`で読み込みます。

```blade
<x-guest-layout>
    <div class="bg-white min-h-screen">
        <div class="max-w-3xl mx-auto px-8 py-12">
            <h1 class="text-2xl font-serif text-[#6b5744] text-center mb-10">Contact</h1>

            <!-- 入力フォーム -->
            <form action="/contacts/confirm" method="post">
                @csrf
                @include('contact._form')

                <!-- 確認画面ボタン -->
                <div class="flex justify-center mt-10">
                    <button type="submit"
                        class="px-16 py-3 bg-[#7d7470] hover:bg-[#6b5f57] border border-transparent rounded font-medium text-white transition">
                        確認画面
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
```

### 4.3. フォーム部分テンプレートでの`@foreach`の活用

`resources/views/contact/_form.blade.php`では、コントローラーから渡された`$categories`と`$tags`を`@foreach`でループし、フォームの選択肢を動的に生成します。

#### カテゴリーのセレクトボックス

```blade
<select name="category_id">
    <option value="" disabled {{ old('category_id') == '' ? 'selected' : '' }}>選択してください</option>
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
            {{ $category->content }}
        </option>
    @endforeach
</select>
```

#### タグのチェックボックス

```blade
<div class="flex flex-wrap gap-4 py-3">
    @foreach ($tags as $tag)
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                {{ in_array($tag->id, old('tag_ids', [])) ? 'checked' : '' }} />
            <span class="ml-2 text-gray-700">{{ $tag->name }}</span>
        </label>
    @endforeach
</div>
```

### 4.4. 確認画面での表示とhidden input

`resources/views/contact/confirm.blade.php`では、バリデーション済みデータの表示と、hidden inputによるデータの保持を行います。

#### カテゴリー名の表示

```blade
<!-- コントローラーから渡された $category モデルの content を表示 -->
<span class="text-[#6b5744]">{{ $category->content }}</span>
```

#### タグ名の表示

```blade
@if ($tags->isNotEmpty())
    <span class="text-[#6b5744]">{{ $tags->pluck('name')->join(', ') }}</span>
@endif
```

#### hidden inputによるデータ保持

```blade
<input type="hidden" name="first_name" value="{{ $validated['first_name'] }}">
<input type="hidden" name="last_name" value="{{ $validated['last_name'] }}">
<input type="hidden" name="gender" value="{{ $validated['gender'] }}">
<input type="hidden" name="email" value="{{ $validated['email'] }}">
<input type="hidden" name="tel" value="{{ $validated['tel'] }}">
<input type="hidden" name="address" value="{{ $validated['address'] }}">
<input type="hidden" name="building" value="{{ $validated['building'] ?? '' }}">
<input type="hidden" name="category_id" value="{{ $validated['category_id'] }}">
@if (!empty($validated['tag_ids']))
    @foreach ($validated['tag_ids'] as $tagId)
        <input type="hidden" name="tag_ids[]" value="{{ $tagId }}">
    @endforeach
@endif
<input type="hidden" name="detail" value="{{ $validated['detail'] }}">
```

## 5. コードの詳細解説 🔍

### `app/Http/Controllers/ContactController.php`

- **`$categories = Category::all();`**
  - **何をしているか**: `Category`モデルの`all()`メソッドを呼び出し、`categories`テーブルの全レコードをEloquentコレクションとして取得しています。
  - **なぜそう書くか**: 入力フォームのセレクトボックスに全カテゴリーの選択肢を表示する必要があるためです。カテゴリー数が限定的（数件〜数十件程度）であるため、`all()`で全件取得しても問題ありません。

- **`$tags = Tag::all();`**
  - **何をしているか**: `Tag`モデルの全レコードをEloquentコレクションとして取得しています。
  - **なぜそう書くか**: チェックボックスに全タグの選択肢を表示するためです。

- **`return view('contact.index', compact('categories', 'tags'));`**
  - **何をしているか**: `view()`ヘルパー関数を使い、`resources/views/contact/index.blade.php`テンプレートをレンダリングします。第2引数の`compact('categories', 'tags')`で、`$categories`変数と`$tags`変数をビューに渡しています。
  - **なぜそう書くか**: `compact()`はPHPの組み込み関数で、変数名の文字列から`['categories' => $categories, 'tags' => $tags]`という連想配列を生成します。`view('contact.index', ['categories' => $categories, 'tags' => $tags])`と書くのと同じ意味ですが、`compact()`の方が簡潔で、Laravelのコードでは広く使われている慣習的な書き方です。

- **`$validated = $request->validated();`**
  - **何をしているか**: `StoreContactRequest`でバリデーションを通過したデータだけを連想配列として取得しています。
  - **なぜそう書くか**: バリデーション済みの安全なデータのみを扱うためです。ユーザーが送信した生のリクエストデータではなく、FormRequestで検証された値を使うことで、不正なデータがビューに渡ることを防ぎます。

- **`$category = Category::find($validated['category_id']);`**
  - **何をしているか**: バリデーション済みデータに含まれる`category_id`を使って、該当する`Category`モデルをデータベースから取得しています。
  - **なぜそう書くか**: 確認画面ではカテゴリーの「名前」（`content`）を表示する必要がありますが、フォームから送られてくるのはIDだけです。IDからモデルを取得することで、`$category->content`としてカテゴリー名にアクセスできるようになります。

- **`$tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();`**
  - **何をしているか**: タグが選択されている場合は、選択されたIDの配列を使って`whereIn`クエリで該当するタグモデルのコレクションを取得します。タグが未選択の場合は、空のコレクション（`collect()`）を返します。
  - **なぜそう書くか**: カテゴリーと同様に、確認画面でタグの名前を表示するためです。`collect()`で空のコレクションを返す理由は、ビュー側で`$tags->isNotEmpty()`や`$tags->pluck('name')->join(', ')`などのコレクションメソッドをエラーなく呼び出せるようにするためです。`null`を渡してしまうと、ビュー側でメソッドチェーンがエラーになります。

### `resources/views/contact/_form.blade.php`

- **`@foreach ($categories as $category)`**
  - **何をしているか**: Bladeの`@foreach`ディレクティブを使い、コントローラーから渡された`$categories`コレクションをループ処理しています。ループの各回で、1つの`Category`モデルインスタンスが`$category`変数に代入されます。
  - **なぜそう書くか**: データベースに格納されたカテゴリーの数だけ`<option>`要素を動的に生成するためです。ハードコーディングせず`@foreach`を使うことで、データベースにカテゴリーが追加されても、Bladeファイルの修正は一切不要になります。

- **`{{ $category->content }}`**
  - **何をしているか**: `$category`モデルの`content`プロパティの値をHTMLエスケープした上で出力しています。`{{ }}`はBladeのエコー構文で、内部的には`htmlspecialchars()`が適用されます。
  - **なぜそう書くか**: XSS（クロスサイトスクリプティング）攻撃を防ぐために、ユーザー由来のデータは必ずエスケープして出力する必要があります。`{{ }}`を使うだけで自動的にエスケープされるため、安全にデータを表示できます。

- **`{{ old('category_id') == $category->id ? 'selected' : '' }}`**
  - **何をしているか**: `old()`ヘルパーでバリデーションエラー時に前回入力された値を取得し、現在のカテゴリーIDと一致する場合に`selected`属性を付与しています。
  - **なぜそう書くか**: バリデーションエラーでフォームに戻された際、ユーザーが選択していたカテゴリーを復元するためです。これがないと、エラー後にセレクトボックスの選択状態がリセットされてしまい、ユーザーは再度選択し直す必要があります。

- **`name="tag_ids[]"`**
  - **何をしているか**: チェックボックスの`name`属性に`[]`（ブラケット）を付けることで、複数選択された値が配列としてサーバーに送信されるようにしています。
  - **なぜそう書くか**: タグは複数選択可能です。ブラケットなしの`name="tag_ids"`だと、複数チェックされた場合に最後の値だけが送信されてしまいます。`[]`を付けることで、PHPのリクエスト処理が自動的に値を配列として解釈してくれます。

### `resources/views/contact/confirm.blade.php`

- **`{{ $validated['first_name'] }}`**
  - **何をしているか**: コントローラーから渡された`$validated`連想配列の`first_name`キーの値を表示しています。
  - **なぜそう書くか**: 確認画面では、ユーザーが入力したデータを「読み取り専用」で表示する必要があります。`$validated`はバリデーション済みの安全なデータであるため、直接表示しても問題ありません。

- **`<input type="hidden" name="first_name" value="{{ $validated['first_name'] }}">`**
  - **何をしているか**: 表示とは別に、同じデータを非表示のフォームフィールドとして埋め込んでいます。
  - **なぜそう書くか**: 確認画面の「送信」ボタンを押した際に、このhidden inputのデータが次のPOSTリクエスト（`store`メソッド）に送信されます。HTTPはステートレスなので、前のリクエストのデータは自動的には引き継がれません。hidden inputは、複数ステップのフォームでデータを引き渡すための標準的な手法です。

- **`$tags->pluck('name')->join(', ')`**
  - **何をしているか**: タグのコレクションから`name`プロパティだけを抽出し（`pluck`）、カンマ区切りの文字列に結合しています（`join`）。
  - **なぜそう書くか**: 複数のタグ名をユーザーに分かりやすく一行で表示するためです。例えば、「引っ越し, 転職」のように表示されます。Eloquentコレクションのメソッドチェーンを活用することで、簡潔に記述できます。

- **`@foreach ($validated['tag_ids'] as $tagId)` と `<input type="hidden" name="tag_ids[]" value="{{ $tagId }}">`**
  - **何をしているか**: 選択されたタグのIDを、1つずつ個別のhidden inputとして出力しています。
  - **なぜそう書くか**: 配列データをhidden inputで送信するには、各要素を個別の`<input>`として出力する必要があります。`name="tag_ids[]"`とすることで、サーバー側では`$request->tag_ids`が配列として受け取れます。

## 6. How to: この実装にたどり着くための調べ方 🗺️

実務でBladeへのデータ渡しとSSR描画を使いこなすための、AIアシスタントを活用した4ステップの学習方法を紹介します。

### Step 1: 公式ドキュメントを読みやすくまとめる

まずは、LaravelのBladeテンプレートに関する公式ドキュメントをAIに要約してもらい、全体像を素早く掴みます。

> **プロンプト例**
> 以下はLaravelの公式ドキュメントの一部です。 これを「実装できるように」分かりやすくまとめてください。
>
> 出力してほしい内容：
> - 重要ポイント（10行以内）
> - 用語の説明（重要なものだけ）
> - できること / できないこと（境界をはっきり）
> - よくある落とし穴（回避策つき）
> - 最小で動かすための手順（コードはまだ不要）
>
> --- ここから ---
> {ここにLaravel公式ドキュメントの「Bladeテンプレート」のセクションを貼り付ける}
> --- ここまで ---

**🤖 このプロンプトのポイント**
- **目的の明確化**: 「実装できるように」と目的を伝えることで、AIは単なる要約ではなく、実践的な観点から情報を整理してくれます。
- **構造化された出力形式**: 出力形式を指定することで、自分が欲しい情報を漏れなく、かつ比較しやすい形で得ることができます。
- **情報源の限定**: `{---}`で囲んで情報源を限定することで、AIが不確かな情報源から回答を生成するのを防ぎ、公式ドキュメントに基づいた正確な回答を引き出せます。

### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

次に、SSRとデータ渡しの核心的な概念について、自分の理解が正しいかを確認し、知識を固めます。

> **プロンプト例**
> LaravelのBladeテンプレートへのデータ渡しについて、私の理解はこうです：
> 「コントローラーでview()ヘルパーの第2引数にデータを渡すと、Bladeテンプレート内でそのデータを変数として使える。compact()はPHPの組み込み関数で、変数名から連想配列を作る便利な方法。Blade内では{{ }}でエスケープ出力、@foreachでループ処理ができる。」
>
> お願い：
> 1) 正しいかチェックして、間違いがあれば「反例」で教えてください
> 2) `compact()`と`['key' => $value]`形式の違いとメリット・デメリットを教えてください
> 3) `{{ }}`と`{!! !!}`の違いと、どちらをいつ使うべきかを教えてください
> 4) よくある勘違いを3つ教えてください
> 5) 理解チェック問題を3問ください（答えつき）

**🤖 このプロンプトのポイント**
- **自分の理解を提示**: 自分の現在の理解を先に示すことで、AIはどこが間違っているのか、どこが不足しているのかを的確に指摘できます。
- **具体的な質問**: 「`compact()`と連想配列の違い」「`{{ }}`と`{!! !!}`の違い」など、ピンポイントで具体的な質問をすることで、曖昧さを排除し、深い理解につながる回答を得られます。
- **理解度チェック**: 「理解チェック問題」を要求することで、自分が本当に知識を消化できているかを確認し、記憶の定着を促します。

### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

概念を理解したら、具体的な実装方法を学びます。構造化されたフォーマットで出力させることで、体系的に知識を吸収できます。

> **プロンプト例**
> 目的は、Laravelでお問い合わせフォームを作り、入力画面→確認画面→保存という3ステップのフォームフローを実装することです。
> 制約は、API/JSONは使わず、従来のフォームPOST + Blade SSRで実装することです。
> 前提知識は、Eloquentモデルとバリデーション（FormRequest）の基本は理解しています。
>
> 次の順番で出力してください：
>
> A. 実装の手順・方針
>  - まず全体の方針（なぜそのやり方か）
>  - 手順を1〜Nで（各手順に「できたらOK」の条件も書く）
>
> B. 関連技術の解説
>  - 必要な関連知識を3〜7個（例: `view()`, `compact()`, `@foreach`, `@include`, `old()`, `{{ }}`）
>  - 各項目は「一言で説明 → この実装で何に使う → 注意点」
>
> C. 実装例
>  - まず最小で動く例（コントローラーとBladeの基本的なデータ渡し）
>  - 次に実務向けの拡張例（確認画面付きフォームフロー）
>
> D. コードの解説
>  - 重要な部分だけ「何をしてるか」「なぜそう書くか」
>  - よくあるバグと対策

**🤖 このプロンプトのポイント**
- **PDR (Purpose, Definition, Role)**: 「目的」「制約」「前提知識」を最初に伝えることで、AIにコンテキストを正確に理解させ、的外れな回答を防ぎます。
- **構造化フォーマットの指定**: 「A. 手順 → B. 解説 → C. 実装例 → D. コード解説」という流れを指定することで、知識をインプット（B, D）とアウトプット（A, C）に分けながら、段階的に学習を進めることができます。
- **具体例の要求**: 「最小で動く例」と「実務向けの拡張例」の両方を要求することで、基本から応用までをスムーズに繋げて学ぶことができます。

### Step 4: 設計レビュー（指摘をもらう）

最後に、自分で書いたコードや設計案をAIにレビューしてもらい、より良い設計への改善点を探ります。

> **プロンプト例**
> 以下のコントローラーとBladeテンプレートの設計をレビューしてください。
>
> - 目的：お問い合わせフォームの入力→確認→保存のフロー実装
> - 設計案：
> ```php
> // ContactController.php
> public function index()
> {
>     $categories = Category::all();
>     $tags = Tag::all();
>     return view('contact.index', compact('categories', 'tags'));
> }
>
> public function confirm(StoreContactRequest $request)
> {
>     $validated = $request->validated();
>     $category = Category::find($validated['category_id']);
>     $tags = isset($validated['tag_ids']) ? Tag::whereIn('id', $validated['tag_ids'])->get() : collect();
>     return view('contact.confirm', compact('validated', 'category', 'tags'));
> }
> ```
> - 不安な点：
>   - `Category::find()`がnullを返す可能性はあるか？（バリデーション済みなので大丈夫？）
>   - hidden inputにユーザーデータを埋め込むのはセキュリティ的に安全か？
>
> 見てほしい観点：
> - セキュリティ（XSS、CSRF、データ改ざん）
> - パフォーマンス（不要なクエリが発生していないか）
> - 保守性（コードの可読性、責務の分離）
>
> 出力：
> - 指摘を「重要度：高/中/低」で出す
> - 各指摘に「理由」「影響」「直し方」をつける
> - 最後に「この設計が失敗しやすい例」を3つ出す

**🤖 このプロンプトのポイント**
- **レビュー依頼の明確化**: 「設計案」と「不安な点」を具体的に示すことで、AIは何に焦点を当ててレビューすればよいかを正確に理解します。
- **観点の指定**: 「セキュリティ」「パフォーマンス」など、見てほしい観点を指定することで、多角的な視点からのフィードバックを得られます。
- **構造化された指摘**: 指摘を「重要度」「理由」「影響」「直し方」のセットで要求することで、なぜそれが問題で、どう直せばよいのかを具体的に理解でき、次のアクションに繋がりやすくなります。

## 7. まとめ ✨

このチャプターでは、LaravelのSSR（サーバーサイドレンダリング）パターンにおける、コントローラーからBladeテンプレートへのデータ渡しと動的なHTML描画の方法を学びました。

- **SSRパターンの理解**: API/JSON方式とは異なり、サーバー側でHTMLを組み立ててブラウザに返す方式の利点と仕組みを理解しました。シンプルなWebアプリケーションでは、SSR方式が効率的で保守しやすい選択肢です。
- **`view()`と`compact()`によるデータ渡し**: コントローラーで取得したデータを`compact()`を使って簡潔にビューに渡す方法を学びました。ビューが必要とするデータだけを過不足なく渡すことが重要です。
- **`@foreach`による動的HTML生成**: Bladeの`@foreach`ディレクティブを使って、データベースから取得したカテゴリーやタグのコレクションをループ処理し、`<select>`や`<input type="checkbox">`の選択肢を動的に生成する方法を習得しました。
- **確認画面でのデータ表示とhidden input**: バリデーション済みデータの表示と、`<input type="hidden">`によるデータ保持の仕組みを理解しました。HTTPのステートレスな性質を補い、複数ステップのフォームフローを実現するための標準的な手法です。
- **表示用データと送信用データの分離**: 確認画面では、IDからモデルを取得して「名前」を表示しつつ、hidden inputには「ID」を保持するという、表示と送信の役割分担を学びました。

これで、コントローラーからBladeテンプレートにデータを渡し、サーバーサイドで完結するフォームフローを構築するための基本技術を習得しました。次のチャプターでは、このフォームフロー全体を統合し、実際にデータを保存する**コントローラー**の実装に焦点を当てていきます。
