# Chapter 7: 提供Bladeファイルの読み解きとSSR描画の仕組み

## 🎯 このセクションで学ぶこと

このチャプターでは、事前に提供されているBladeテンプレートの中身を読み解き、LaravelのSSR（サーバーサイドレンダリング）における描画の仕組みを理解します。Bladeディレクティブ（`@foreach`, `@error`, `@csrf`など）の役割、`{{ }}`によるエスケープ出力、`old()`による入力値の復元、そして確認画面での`<input type="hidden">`によるデータ保持の仕組みを学びます。

**重要**: このチャプターではコードの実装は行いません。提供されているBladeファイルを「読んで理解する」ことに集中します。コントローラーの実装は次のChapter 8で行います。

## 1. はじめに 📖

### サーバーサイド描画とは？コントローラーとBladeの「連携プレイ」

Webアプリケーションには、大きく分けて2つのアーキテクチャパターンがあります。

1. **API駆動型（SPA方式）**: サーバーはJSON形式のデータだけを返し、フロントエンドのJavaScript（ReactやVueなど）がそのJSONを受け取ってHTMLを組み立てる方式。サーバーとフロントエンドが完全に分離されます。
2. **サーバーサイドレンダリング型（SSR方式）**: サーバー側でデータベースからデータを取得し、そのデータをテンプレートエンジン（Blade）に渡して、完成したHTMLをブラウザに返す方式。LaravelではこのSSR方式が伝統的かつ最も一般的なパターンです。

本プロジェクトでは、このSSR方式を採用しています。ユーザーがフォームに入力したデータは、通常のHTTPフォーム送信（POST）でサーバーに送られ、コントローラーが処理し、Bladeテンプレートを使ってHTMLを生成してブラウザに返します。APIリソースやJSONレスポンスは使用しません。

このSSR方式の最大の利点は、**シンプルさ**にあります。フロントエンドとバックエンドが一つのLaravelプロジェクト内で完結するため、別途フロントエンドアプリケーションを構築・管理する必要がありません。お問い合わせフォームのような、比較的シンプルなWebアプリケーションには最適なアーキテクチャです。

このチャプターでは、提供されているBladeテンプレートを読み解きながら、Blade側でデータを受け取り動的にHTMLを描画する仕組みを学びます。

## 2. 要件の確認 📋

このアプリケーションのお問い合わせフォームでは、以下のデータの流れを実現する必要があります。このチャプターでは、下図の**Blade側（右側）** の仕組みを理解することに焦点を当てます。コントローラー側の実装はChapter 8で行います。

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
    |                       | $category             |
    v                       v                       |
contact.index           contact.confirm         redirect('/thanks')
  └─ _form.blade.php

  ← Chapter 8 で実装 →  ← 提供済みBlade（このチャプターで読み解く）→
```

### 入力画面（`contact.index`）のBlade

- コントローラーから `$categories`（カテゴリー一覧）を受け取る想定。
- `@foreach` で `<select>` 要素を動的に生成する。
- タグ関連の表示は `@isset($tags)` で囲まれており、応用機能フェーズ（Chapter 13）で `$tags` を渡すまでは非表示になる。
- `old()` でバリデーションエラー時の入力値を復元する。

### 確認画面（`contact.confirm`）のBlade

- コントローラーから `$validated`（バリデーション済みデータ）、`$category`（カテゴリーモデル）を受け取る想定。
- タグ関連の表示は `@isset($tags)` で囲まれており、応用機能フェーズで `$tags` を渡すまでは非表示になる。
- 入力内容を表示しつつ、`<input type="hidden">` で全データを保持し、最終送信に備える。

## 3. 先輩エンジニアの思考プロセス 💭

提供Bladeファイルには、実務で使われる重要なパターンが多数含まれています。なぜそのように書かれているのか、設計思想を学びましょう。

### Point 1: Bladeの`@foreach`でHTMLを動的に生成する

カテゴリーの選択肢は、データベースに格納されている動的なデータです。これをHTMLにハードコーディングしてしまうと、カテゴリーが追加・変更されるたびにBladeファイルを手動で修正する必要があり、保守性が著しく低下します。`@foreach`ディレクティブを使ってモデルのコレクションをループ処理することで、データベースの内容が変わっても自動的にフォームの選択肢が更新されます。これにより、「データの変更はデータベースの管理だけで完結する」という理想的な状態を実現できます。なお、タグの選択肢も同じ仕組みですが、タグ機能は応用機能フェーズ（Chapter 13）で実装します。

### Point 2: `old()`でバリデーションエラー後の入力値を復元する

ユーザーがフォームを送信してバリデーションエラーが発生すると、入力画面にリダイレクトされます。このとき、せっかく入力したデータが全て消えてしまっては、ユーザー体験が非常に悪くなります。Laravelの`old()`ヘルパーは、直前のリクエストでセッションに一時保存（フラッシュ）された入力値を取得します。これにより、エラー後もユーザーの入力が復元され、修正が必要な箇所だけを直せばよくなります。

### Point 3: 確認画面ではhidden inputで「データを保持」する

通常のHTTPリクエストはステートレス（状態を持たない）です。つまり、入力画面から確認画面に遷移した時点で、入力データはサーバー側には保持されていません。確認画面から最終的にデータを保存するために、ユーザーが入力した全データを`<input type="hidden">`としてフォームに埋め込み、次のPOSTリクエストで再度サーバーに送信する必要があります。このパターンは、セッションにデータを保存する方法と並んで、確認画面付きフォームの最も一般的な実装方法です。

### Point 4: IDだけでなく「表示用データ」もビューに渡す

確認画面では、ユーザーが選択したカテゴリーの「名前」を表示する必要があります。しかし、フォームから送られてくるのは`category_id`というIDの値だけです。そのため、コントローラー側でIDからモデルを取得して名前を表示用に渡し、hidden inputにはIDを保持するという「表示用のデータ」と「送信用のデータ」の分離が重要になります。この仕組みはChapter 8のコントローラー実装で実現しますが、Blade側の構造を先に理解しておくことで、コントローラーが何を渡せばよいかが明確になります。なお、タグのID→名前変換も同じ考え方で、応用機能フェーズ（Chapter 13）で実装します。

## 4. 提供Bladeファイルの読み解き 📖

それでは、提供されているBladeファイルを一つずつ読み解いていきましょう。ここではコードを書くのではなく、提供済みのファイルの仕組みを理解します。

### 4.1. 入力フォーム画面：`contact/index.blade.php`

`resources/views/contact/index.blade.php` は、お問い合わせフォームのメイン画面です。フォームの入力部品は `_form.blade.php` という部分テンプレートに切り出されており、`@include` で読み込んでいます。

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

    @push('scripts')
        @vite(['resources/js/contact/init.js'])
    @endpush
</x-guest-layout>
```

**読み解きポイント:**

- **`<x-guest-layout>`**: Bladeコンポーネントです。`resources/views/components/guest-layout.blade.php` に定義された共通レイアウト（ヘッダー、`@vite`によるCSS読み込みなど）を適用します。
- **`@csrf`**: CSRF（クロスサイトリクエストフォージェリ）攻撃を防ぐためのトークンを自動生成します。LaravelではPOSTフォームに必ず必要です。
- **`@include('contact._form')`**: `_form.blade.php` を読み込みます。フォーム入力部品を別ファイルに切り出すことで、再利用性と見通しを向上させています。
- **`action="/contacts/confirm"`**: フォーム送信先は `POST /contacts/confirm`。Chapter 8で実装する `ContactController@confirm` がこのリクエストを受け取ります。
- **`@push('scripts')` / `@vite(...)`**: 電話番号の3分割入力欄を1つに結合するJavaScriptを読み込んでいます。`@push`はレイアウトの`@stack('scripts')`の位置にコンテンツを挿入するBladeディレクティブです。

### 4.2. フォーム部分テンプレート：`contact/_form.blade.php`

`resources/views/contact/_form.blade.php` は入力フォームの各項目を定義しています。ここでは特に重要な **動的な選択肢の生成** と **エラー表示** の部分を抜粋して見ていきます。（実際のファイルにはCSSクラスやレイアウト用のHTML要素がさらに含まれますが、ロジックに関わる部分に絞って紹介します。）

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

- **`@foreach ($categories as $category)`**: コントローラーから渡される `$categories` コレクションをループし、`<option>` 要素を動的に生成します。
- **`{{ $category->content }}`**: `{{ }}` はBladeのエコー構文で、値をHTMLエスケープして出力します。XSS攻撃を防ぐための重要な仕組みです。
- **`old('category_id') == $category->id ? 'selected' : ''`**: バリデーションエラー後に前回選択されていたカテゴリーを復元します。

#### タグのチェックボックス

```blade
@isset($tags)
<div class="flex flex-wrap gap-4 py-3">
    @foreach ($tags as $tag)
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                {{ in_array($tag->id, old('tag_ids', [])) ? 'checked' : '' }} />
            <span class="ml-2 text-gray-700">{{ $tag->name }}</span>
        </label>
    @endforeach
</div>
@endisset
```

- **`@isset($tags)` / `@endisset`**: `$tags` 変数がコントローラーから渡されている場合のみ、タグセクションを表示します。基礎機能フェーズでは `$tags` を渡さないため非表示になり、応用機能フェーズ（Chapter 13）でタグ機能を実装した後に表示されるようになります。
- **`name="tag_ids[]"`**: `[]` を付けることで、複数選択された値が**配列**としてサーバーに送信されます。これがないと最後の1つしか送信されません。
- **`in_array($tag->id, old('tag_ids', []))`**: エラー後に前回チェックされていたタグを復元します。`old()` の第2引数 `[]` はデフォルト値で、初回表示時に `null` でエラーになるのを防ぎます。

#### エラーメッセージの表示

```blade
@error('first_name')
    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
@enderror
```

- **`@error('first_name')`**: `first_name` フィールドにバリデーションエラーがある場合のみ、このブロック内を表示します。
- **`{{ $message }}`**: Laravelが自動的に用意するエラーメッセージ変数です。Chapter 6で実装した `StoreContactRequest` の `messages()` メソッドで定義したカスタムメッセージが表示されます。

### 4.3. 確認画面：`contact/confirm.blade.php`

`resources/views/contact/confirm.blade.php` は、入力内容の表示とhidden inputによるデータ保持の2つの役割を担います。以下はロジックに関わる部分の抜粋です。

#### カテゴリー名・タグ名の表示

```blade
<!-- コントローラーから渡された $category モデルの content を表示 -->
<span class="text-[#6b5744]">{{ $category->content }}</span>
```

```blade
@isset($tags)
    @if ($tags->isNotEmpty())
        <span class="text-[#6b5744]">{{ $tags->pluck('name')->join(', ') }}</span>
    @endif
@endisset
```

- **`$category->content`**: コントローラーがIDからCategoryモデルを取得して渡してくれるので、ここでは名前を表示するだけです。
- **`@isset($tags)`**: 入力フォームと同様に、`$tags` が渡されている場合のみタグ名を表示します。
- **`$tags->pluck('name')->join(', ')`**: タグのコレクションから `name` だけを抽出し、カンマ区切りの文字列に変換しています（例：「質問, 要望」）。

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

- **`$validated`**: コントローラーからバリデーション済みデータが連想配列として渡されます。
- **`type="hidden"`**: ユーザーには見えないが、フォーム送信時にデータとして含まれます。「送信」ボタンを押すと、これらの値が `POST /contacts` に送られます。
- **`{{ $validated['building'] ?? '' }}`**: `??` はPHPのNull合体演算子です。`building` は任意項目のため `null` の可能性があり、その場合は空文字を出力します。
- **`name="tag_ids[]"`**: 入力フォームと同様、配列として送信するために `[]` を付けています。`@if (!empty(...))` で囲まれているため、基礎機能フェーズで `tag_ids` がなくてもエラーにはなりません。

### 4.4. この読み解きから分かること

提供Bladeファイルを読み解くことで、**コントローラーが何を渡せばよいか**が明確になります。

| Bladeファイル | コントローラーから受け取る変数 | 渡す側のメソッド |
|:---|:---|:---|
| `contact/index.blade.php` + `_form.blade.php` | `$categories`（全カテゴリー）※ `$tags` は応用機能フェーズで追加 | `ContactController@index` |
| `contact/confirm.blade.php` | `$validated`（バリデーション済みデータ）, `$category`（カテゴリーモデル）※ `$tags` は応用機能フェーズで追加 | `ContactController@confirm` |

次のChapter 8では、この表の「渡す側のメソッド」を実際に実装します。

## 5. コードの詳細解説 🔍

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

- **`@isset($tags)` / `@endisset`**（タグセクション）
  - **何をしているか**: `$tags` 変数が存在するかチェックし、存在する場合のみタグのチェックボックスを表示しています。
  - **なぜそう書くか**: タグ機能は応用機能フェーズ（Chapter 13）で実装するため、基礎機能フェーズではコントローラーから `$tags` が渡されません。`@isset` で囲むことで、タグ機能の実装前でもエラーにならず、タグ以外の機能を正常に動作確認できます。

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

- **`@isset($tags)` / `@endisset`**（タグ表示部分）
  - **何をしているか**: 入力フォームと同様、`$tags` 変数が存在する場合のみタグ名を表示します。基礎機能フェーズでは非表示になります。

- **`$tags->pluck('name')->join(', ')`**
  - **何をしているか**: タグのコレクションから`name`プロパティだけを抽出し（`pluck`）、カンマ区切りの文字列に結合しています（`join`）。
  - **なぜそう書くか**: 複数のタグ名をユーザーに分かりやすく一行で表示するためです。例えば、「質問, 要望」のように表示されます。Eloquentコレクションのメソッドチェーンを活用することで、簡潔に記述できます。

- **`@foreach ($validated['tag_ids'] as $tagId)` と `<input type="hidden" name="tag_ids[]" value="{{ $tagId }}">`**
  - **何をしているか**: 選択されたタグのIDを、1つずつ個別のhidden inputとして出力しています。
  - **なぜそう書くか**: 配列データをhidden inputで送信するには、各要素を個別の`<input>`として出力する必要があります。`name="tag_ids[]"`とすることで、サーバー側では`$request->tag_ids`が配列として受け取れます。

## 6. How to: Bladeテンプレートを読み解くための調べ方 🗺️

提供されたBladeファイルに見慣れない記法があった場合、AIアシスタントを活用して効率よく理解する方法を紹介します。

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

次に、Bladeの記法について、自分の理解が正しいかを確認し、知識を固めます。

> **プロンプト例**
> LaravelのBladeテンプレートについて、私の理解はこうです：
> 「Blade内では{{ }}でエスケープ出力、@foreachでループ処理ができる。@errorディレクティブでバリデーションエラーを表示し、old()で前回の入力値を復元できる。@csrfでCSRFトークンを埋め込む。」
>
> お願い：
> 1) 正しいかチェックして、間違いがあれば「反例」で教えてください
> 2) `{{ }}`と`{!! !!}`の違いと、どちらをいつ使うべきかを教えてください
> 3) `@include`と`<x-コンポーネント>`の違いを教えてください
> 4) よくある勘違いを3つ教えてください
> 5) 理解チェック問題を3問ください（答えつき）

**🤖 このプロンプトのポイント**
- **自分の理解を提示**: 自分の現在の理解を先に示すことで、AIはどこが間違っているのか、どこが不足しているのかを的確に指摘できます。
- **具体的な質問**: 「`{{ }}`と`{!! !!}`の違い」「`@include`とコンポーネントの違い」など、ピンポイントで具体的な質問をすることで、曖昧さを排除し、深い理解につながる回答を得られます。
- **理解度チェック**: 「理解チェック問題」を要求することで、自分が本当に知識を消化できているかを確認し、記憶の定着を促します。

### Step 3: 提供コードの疑問点を質問する

提供Bladeファイルの中で理解できない部分があれば、該当コードを貼り付けて質問します。

> **プロンプト例**
> 以下のBladeコードの各行が何をしているか、初学者向けに1行ずつ解説してください。
>
> ```blade
> @foreach ($tags as $tag)
>     <label class="flex items-center cursor-pointer">
>         <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
>             {{ in_array($tag->id, old('tag_ids', [])) ? 'checked' : '' }} />
>         <span class="ml-2 text-gray-700">{{ $tag->name }}</span>
>     </label>
> @endforeach
> ```
>
> 特に知りたいこと：
> - `name="tag_ids[]"` の `[]` は何の意味か？
> - `old('tag_ids', [])` の第2引数 `[]` はなぜ必要か？
> - `in_array()` は何をチェックしているか？

**🤖 このプロンプトのポイント**
- **コードを貼り付ける**: 実際のコードを提示することで、具体的で正確な解説が得られます。
- **「特に知りたいこと」を明示**: 分からないポイントを具体的に伝えることで、AIは的外れな一般論ではなく、あなたが知りたいことに集中して回答してくれます。

### Step 4: 設計レビュー（指摘をもらう）

Chapter 8でコントローラーを実装した後、提供Bladeとの連携が正しいかをAIにレビューしてもらいましょう。

> **プロンプト例**
> 以下のコントローラーが、提供されたBladeテンプレートに正しくデータを渡せているかレビューしてください。
>
> - Bladeが期待する変数：`$categories`, `$tags`（入力画面）、`$validated`, `$category`, `$tags`（確認画面）
> - 設計案：
> {ここにChapter 8で書いたコントローラーコードを貼り付ける}
>
> 見てほしい観点：
> - Bladeが期待する変数名と一致しているか
> - 変数の型は正しいか（コレクション、モデル、配列）
> - エッジケース（タグ未選択時など）で問題が起きないか

**🤖 このプロンプトのポイント**
- **Bladeの期待値を明示**: 提供Bladeが何を期待しているかを伝えることで、コントローラーとの整合性を的確にチェックしてもらえます。

## 7. まとめ ✨

このチャプターでは、提供されているBladeテンプレートを読み解き、LaravelのSSR（サーバーサイドレンダリング）における描画の仕組みを学びました。

- **SSRパターンの理解**: API/JSON方式とは異なり、サーバー側でHTMLを組み立ててブラウザに返す方式の利点と仕組みを理解しました。シンプルなWebアプリケーションでは、SSR方式が効率的で保守しやすい選択肢です。
- **`@foreach`による動的HTML生成**: Bladeの`@foreach`ディレクティブを使って、データベースから取得したカテゴリーやタグのコレクションをループ処理し、`<select>`や`<input type="checkbox">`の選択肢を動的に生成する仕組みを理解しました。
- **`old()`による入力値の復元**: バリデーションエラー後にユーザーの入力を復元するための`old()`ヘルパーの役割を学びました。
- **`@error`によるエラー表示**: Chapter 6で定義したバリデーションルールのエラーメッセージが、Blade側でどのように表示されるかを理解しました。
- **確認画面でのhidden input**: HTTPのステートレスな性質を補い、複数ステップのフォームフローを実現するための`<input type="hidden">`の仕組みを理解しました。
- **Bladeが期待する変数の把握**: 提供Bladeファイルを読むことで、コントローラーが渡すべき変数（`$categories`, `$tags`, `$validated`, `$category`）を明確にしました。

これで、提供Bladeファイルの仕組みと、コントローラーとの連携パターンを理解しました。次のチャプターでは、ここで把握した「Bladeが期待する変数」を実際に渡す**コントローラー**の実装に焦点を当てていきます。
