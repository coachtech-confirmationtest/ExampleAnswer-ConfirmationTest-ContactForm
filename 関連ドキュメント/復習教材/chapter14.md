# Chapter 14: 管理画面の心臓部 - CSVエクスポート機能を実装する

## 1. はじめに 📖

お疲れ様です！応用機能編の最後を飾るのは、多くのWebアプリケーションで必須とも言える「CSVエクスポート機能」です。管理画面に蓄積されたお問い合わせデータを、CSVファイルとしてダウンロードできるようにします。

これまでのチャプターでは、APIを通じてデータをJSON形式でやり取りする方法を学んできました。しかし、今回はブラウザから直接ファイルをダウンロードさせるという、少し異なるアプローチを取ります。この機能により、ユーザーは使い慣れた表計算ソフト（Excelなど）でデータを自由に分析・加工できるようになります。

このチャプターでは、Laravelが提供する便利なストリームダウンロード機能を利用して、メモリ効率を意識しつつ、検索条件にも連動した柔軟なエクスポート機能を実装する方法を学びます。

## 2. 要件の確認 📋

実装に入る前に、今回作成するCSVエクスポート機能の具体的な要件を整理しましょう。

| 機能 | 要件 |
|:---|:---|
| **エンドポイント** | `GET /contacts/export` | 
| **アクセス制御** | 認証済みのユーザーのみがアクセス可能（`auth`ミドルウェア） |
| **機能** | 管理画面で指定された検索条件（キーワード、性別、カテゴリ、日付）に基づいて、該当するお問い合わせデータをCSV形式でダウンロードする。 |
| **ファイル名** | `contacts_YYYYMMDD_HHMMSS.csv` のように、ダウンロードした日時が含まれる動的なファイル名にする。 |
| **CSVフォーマット** | - 文字コードは`UTF-8`（BOM付き）とし、Excelでの文字化けを防ぐ。<br>- ヘッダー行は含めず、データ行のみを出力する。 |
| **出力項目** | ID, 氏名（姓と名を連結）, 性別（文字列）, メールアドレス、電話番号、住所、建物名、お問い合わせ種類、お問い合わせ内容、登録日時 |

## 3. 先輩エンジニアの思考プロセス 💭

このCSVエクスポート機能を実装するにあたり、先輩エンジニアはどのようなことを考えているのでしょうか。その思考プロセスを覗いてみましょう。

### Point 1: なぜAPIではなくWebルートなのか？

「今回はJSONデータを返すんじゃなくて、ブラウザに『このファイルをダウンロードしてね』って命令する必要がある。こういうファイルダウンロード系の処理は、`routes/api.php`じゃなくて`routes/web.php`に書くのが一般的だな。APIはあくまでデータ交換のための口だから、ファイルのダウンロードはWebの領域だ。」

### Point 2: どうやってファイルをダウンロードさせる？

「Laravelにはファイルダウンロード用の便利なレスポンスがいくつかあるな。`response()->download()`と`response()->streamDownload()`が代表的か。前者は既存のファイルをダウンロードさせる時に使う。今回は動的にCSVを生成するから、後者の`streamDownload`がピッタリだ。コールバック関数の中でCSVデータを少しずつ作ってレスポンスに乗せられるから、シンプルに書ける。」

### Point 3: 検索ロジックはどうする？

「管理画面の検索機能とエクスポートの検索条件は同じはず。`ContactController`の`export`メソッドの中に、`index`メソッドと似たような検索クエリを組み立てるロジックを書けばいいな。今回は`buildSearchQuery`のような共通メソッドは作らず、`export`メソッド内に直接書くことで、このメソッド単体で完結させてみよう。その方が、メソッドの見通しが良くなることもある。」

### Point 4: 大量データでも大丈夫？

「模範解答では`get()`で全件取得しているな。これは、今回のプロジェクトのデータ量なら問題ないと判断したんだろう。でも、実務では何十万件ものデータを扱う可能性がある。その場合は`get()`だとメモリを使い果たす危険があるから、`chunk()`や`cursor()`を使って少しずつ処理するのが鉄則だ。今回は模範解答に合わせて`get()`で実装するけど、`chunk()`を使う方法も頭の片隅に置いておこう。」

### Point 5: 細かいけど大事な気配り

「ExcelでCSVを開くと日本語が文字化けすることがあるんだよな。これはBOM（バイトオーダーマーク）をファイルの先頭に付けてあげれば解決する。`\xEF\xBB\xBF`っていうおまじないを最初に書き込んであげよう。あと、ファイル名に日時を入れておけば、ユーザーが何回ダウンロードしてもファイルが上書きされないし、いつのデータか分かりやすくて親切だ。」

## 4. 実装 🚀

それでは、思考プロセスを基にCSVエクスポート機能を実装していきましょう。

### 4.1. `ExportContactRequest`の修正

まずは、以下のコマンドでフォームリクエストファイルを作成し、バリデーションルールを設定していきます。

`php artisan make:request ExportContactRequest`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportContactRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:0,1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
        ];
    }
}
```

### 4.2. `ContactController`の改修

次に、`app/Http/Controllers/ContactController.php`（**Apiコントローラーではない**点に注意）に`export`メソッドを実装します。

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Controllers\Controller;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
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
            // BOMを追加（Excel対応）
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
                    $contact->last_name . ' ' . $contact->first_name,
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
        }, 'contacts_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
```

### 4.3. ルーティングの追加

`routes/web.php`にCSVエクスポート用のルートを追加します。認証が必要な管理画面の機能なので、`auth`ミドルウェアグループの中に追加します。

**`routes/web.php`**
```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminController;

// ... (中略) ...

// 管理画面（認証必須）
Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    // ここに追加
    Route::get('/contacts/export', [ContactController::class, 'export']);
});
```

## 5. コードの詳細解説 🔍

### `app/Http/Requests/ExportContactRequest.php` の解説

このFormRequestクラスは、CSVエクスポート時に渡される検索パラメータのバリデーションを担当します。

**バリデーションルール:**
- `keyword`: 任意の文字列（最大255文字）。氏名やメールアドレスの部分一致検索に使用
- `gender`: 任意の整数で、0（全て）、1（男性）、2（女性）、3（その他）のいずれか
- `category_id`: 任意の整数で、`categories`テーブルに存在するIDであること
- `date`: 任意の日付形式の文字列。登録日での絞り込みに使用

すべてのパラメータが`nullable`（省略可能）なのは、検索条件を指定せずに全件エクスポートすることも可能にするためです。

### `app/Http/Controllers/ContactController.php` の `export` メソッドの解説

#### 検索クエリの構築

```php
$query = Contact::with('category');
```

まず、`Contact`モデルのクエリビルダを作成し、`with('category')`でCategoryリレーションをEager Loadingします。これにより、後でCSV出力時に`$contact->category->content`にアクセスしてもN+1問題が発生しません。

#### 検索条件の適用

```php
if ($request->filled('keyword')) {
    $keyword = $request->keyword;
    $query->where(function ($q) use ($keyword) {
        $q->where('first_name', 'like', "%{$keyword}%")
            ->orWhere('last_name', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%");
    });
}
```

`$request->filled('keyword')`は、`keyword`パラメータが存在し、かつ空でない場合に`true`を返します。クロージャ内で`orWhere`を使うことで、「名前（姓または名）またはメールアドレスのいずれかに`$keyword`が含まれる」という条件を実現しています。

```php
if ($request->filled('gender') && $request->gender != 0) {
    $query->where('gender', $request->gender);
}
```

性別の条件は、`gender`が指定されており、かつ`0`（全て）でない場合のみ適用します。

#### ストリームダウンロードの実装

```php
return response()->streamDownload(function () use ($contacts) {
    $handle = fopen('php://output', 'w');
    fwrite($handle, "\xEF\xBB\xBF");
    // ...
}, 'contacts_' . now()->format('Ymd_His') . '.csv', [
    'Content-Type' => 'text/csv',
]);
```

**`response()->streamDownload()`の3つの引数:**
1. **コールバック関数**: ファイルの内容を生成する処理。`php://output`を使うことで、直接HTTPレスポンスに書き込みます
2. **ファイル名**: `now()->format('Ymd_His')`で現在日時をフォーマットし、`contacts_20260210_143022.csv`のような一意なファイル名を生成
3. **ヘッダー**: `Content-Type: text/csv`を指定することで、ブラウザにCSVファイルであることを伝えます

**BOM（バイトオーダーマーク）の追加:**
```php
fwrite($handle, "\xEF\xBB\xBF");
```

Excelで日本語CSVを正しく表示するために、UTF-8のBOMをファイルの先頭に書き込みます。これがないと、Excelで開いた時に文字化けが発生します。

**性別の変換:**
```php
$genderText = match ($contact->gender) {
    1 => '男性',
    2 => '女性',
    3 => 'その他',
    default => '',
};
```

PHP 8.0以降の`match`式を使い、数値の性別コードを日本語文字列に変換します。`switch`文と異なり、値を直接返せるため、コードが簡潔になります。

### `routes/web.php` のルーティング

```php
Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/contacts/export', [ContactController::class, 'export']);
});
```

`middleware('auth')`グループ内にルートを定義することで、未ログインユーザーがアクセスしようとすると自動的にログインページにリダイレクトされます。

## 6. How to: この実装にたどり着くための調べ方 🗺️

CSVエクスポート機能を実装する際に、エンジニアがどのようにAIを活用して学習を進めるか、4つのステップで見ていきましょう。

### Step 1: 公式ドキュメントを読みやすくまとめる

新しい技術を学ぶ第一歩は、公式ドキュメントの理解です。しかし、公式ドキュメントは網羅的すぎて、必要な情報を素早く把握するのが難しいことがあります。

**プロンプト例:**

```
以下はLaravelの公式ドキュメント「HTTP Responses - File Downloads」の一部です。
これを「実装できるように」分かりやすくまとめてください。

出力してほしい内容：
- 重要ポイント（10行以内）
- 用語の説明（重要なものだけ）
- できること / できないこと（境界をはっきり）
- よくある落とし穴（回避策つき）
- 最小で動かすための手順（コードはまだ不要）

--- ここから ---
[公式ドキュメントの該当部分を貼り付け]
--- ここまで ---
```

**このプロンプトのポイント:**
- 「実装できるように」という目的を明確にすることで、理論だけでなく実践的な情報を得られる
- 出力形式を具体的に指定することで、必要な情報が整理された形で返ってくる
- 「できること / できないこと」を明確にすることで、技術の適用範囲を正しく理解できる

### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

ドキュメントを読んだだけでは、仕組みの本質を理解できないことがあります。自分の理解が正しいかを確認し、概念の境界を明確にします。

**プロンプト例:**

```
`response()->streamDownload()`について、私の理解はこうです：
「動的に生成したデータを、サーバーのディスクに保存せずに直接ブラウザにダウンロードさせる機能。コールバック関数内でデータを生成しながら、少しずつHTTPレスポンスに書き込んでいく。」

お願い：
1) 正しいかチェックして、間違いがあれば「反例」で教えてください
2) 仕組みを「入力→中で起きること→出力」で説明してください
3) どこまでがこの機能の範囲か（境界）を教えてください
4) よくある勘違いを3つ教えてください
5) 理解チェック問題を3問ください（答えつき）
```

**このプロンプトのポイント:**
- 自分の理解を言語化することで、曖昧な部分が明確になる
- 「反例」を求めることで、間違った理解を修正できる
- 「境界」を明確にすることで、適用範囲を正しく把握できる
- 理解チェック問題で知識を定着させる

### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

概念を理解したら、実際のコードに落とし込みます。段階的に実装を進めることで、確実に動くコードを書けます。

**プロンプト例:**

```
目的は「Laravelで検索条件付きCSVエクスポート機能を実装する」です。
制約は「Laravel 10、PHP 8.1以上、実装時間は2時間以内」です。
前提知識は「Eloquent、クエリビルダ、ルーティングの基本は理解している」です。

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

**このプロンプトのポイント:**
- 目的・制約・前提知識を明確にすることで、適切なレベルの回答が得られる
- 出力順序を指定することで、段階的に理解を深められる
- 「最小で動く例」から始めることで、確実に動作確認できる
- 「よくあるバグと対策」を聞くことで、実装時のトラブルを回避できる

### Step 4: 設計レビュー（指摘をもらう）

実装が完了したら、設計の妥当性を確認します。自分では気づかない問題点を発見できます。

**プロンプト例:**

```
以下の設計をレビューしてください。

- 目的：お問い合わせデータのCSVエクスポート機能
- 要件：検索条件（キーワード、性別、カテゴリ、日付）に基づいてフィルタリング、Excel対応
- 制約：Laravel 10、データ量は現状1万件程度だが将来10万件以上になる可能性
- 設計案：`response()->streamDownload()`を使い、`get()`で全件取得してループでCSV生成
- 不安な点：大量データ時のメモリ消費、パフォーマンス

見てほしい観点：
- 正しく動くか（抜け漏れ）
- 運用しやすいか（監視/障害対応）
- 変更しやすいか（拡張/分離）
- コスト（開発/運用/性能）
- セキュリティ（権限/秘密情報など）

出力：
- 指摘を「重要度：高/中/低」で出す
- 各指摘に「理由」「影響」「直し方」をつける
- 最後に「この設計が失敗しやすい例」を3つ出す
```

**このプロンプトのポイント:**
- 不安な点を明確にすることで、重点的にレビューしてもらえる
- 複数の観点（動作、運用、保守性、コスト、セキュリティ）から評価してもらう
- 重要度を付けてもらうことで、優先順位をつけて改善できる
- 「失敗しやすい例」を知ることで、将来のトラブルを予防できる

### AIを使った学習の全体フロー

1. **公式ドキュメントの理解** → 全体像を素早く把握
2. **概念の確認** → 自分の理解をチェック、仕組みを深掘り
3. **段階的実装** → 最小構成から実務レベルへ
4. **設計レビュー** → 問題点の発見と改善

このように、AIを「要件を丸投げしてコードを生成させる道具」ではなく、「学習を加速し、理解を深めるパートナー」として活用することで、実務で通用する確かな技術力を身につけることができます。

## 7. まとめ ✨

このチャプターでは、Webアプリケーションの重要な機能であるCSVエクスポートを実装しました。

**学んだ重要なポイント:**
- **Webルートとコントローラー**: ファイルダウンロードのようなブラウザ固有の機能は、APIではなくWebルートで実装することを学びました。
- **ストリームダウンロード**: `response()->streamDownload`を使い、動的にCSVデータを生成してユーザーにダウンロードさせる方法を実装しました。
- **実践的な小技**: BOMによる文字化け対策や、`match`式による可読性の高いコード、動的なファイル名生成など、実務で役立つテクニックを学びました。
- **パフォーマンスの考慮**: 大量データを扱う場合は`chunk()`や`cursor()`を使う必要があることを理解しました。

これにて、確認テストで求められる全ての基本機能と応用機能の実装、そしてそれらを解説する復習教材の執筆が完了しました。素晴らしい成果です。お疲れ様でした！
