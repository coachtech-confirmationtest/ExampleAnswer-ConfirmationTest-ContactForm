# Chapter 4: アプリケーションの門番 - Fortifyで認証機能を実装する

## 🎯 このセクションで学ぶこと

このチャプターでは、アプリケーションに「認証」という門番を設置します。具体的には、Laravelの公式パッケージである**Laravel Fortify**を導入し、ユーザー登録、ログイン、ログアウトといった、Webアプリケーションに不可欠な認証機能を、安全かつ効率的に実装する方法を学びます。これにより、管理者ページのような特定のユーザーだけがアクセスできる領域を作成できるようになります。

## 1. はじめに 📖

### 認証とは？「あなたは誰？」を確認する仕組み

これまでのチャプターで、データベースの準備と、それと対話するためのモデルを作成しました。しかし、現状では誰でもアプリケーションのすべての機能にアクセスできてしまいます。お問い合わせの管理画面が悪意のある第三者に閲覧されたり、操作されたりしては大変です。

そこで必要になるのが**認証 (Authentication)**です。認証とは、システムを利用しようとしているユーザーが「誰であるか」を確認し、本人であることを証明するプロセスです。身近な例で言えば、スマートフォンのロックを解除するためにパスコードを入力したり、銀行のATMでお金を引き出すために暗証番号を入力したりする行為がこれにあたります。

Webアプリケーションにおける認証は、一般的にメールアドレスとパスワードの組み合わせで行われます。この仕組みをゼロから自分で作るのは非常に大変で、パスワードの安全な保管（ハッシュ化）や、セッション管理、不正なログイン試行への対策など、考慮すべきセキュリティ事項が山ほどあります。幸いなことに、Laravelにはこの複雑な認証機能を簡単に追加するための素晴らしいパッケージが用意されています。その一つが、今回利用する**Laravel Fortify**です。

Fortifyは、認証機能のバックエンドロジック（登録処理、ログイン処理など）だけを提供してくれるヘッドレスなパッケージです。UI（見た目）は提供しないため、今回のようにフロントエンドのBladeファイルが別途提供されている場合に最適です。私たちはFortifyが提供する部品を組み合わせるだけで、堅牢な認証システムを素早く構築できます。

## 2. 要件の確認 📋

このアプリケーションにおける認証機能の具体的な要件を確認しましょう。

| 機能 | 要件 |
| :--- | :--- |
| **ユーザー登録** | 新しいユーザーが、名前、メールアドレス、パスワードを設定してアカウントを作成できる。 |
| **ログイン** | 登録済みのユーザーが、メールアドレスとパスワードを使ってシステムにログインできる。 |
| **ログアウト** | ログイン中のユーザーが、安全にセッションを終了し、ログアウトできる。 |
| **アクセス制御** | `/admin` から始まるURL（管理画面）は、ログインしているユーザーしかアクセスできないようにする。未ログインのユーザーがアクセスしようとした場合は、ログインページに強制的にリダイレクトさせる。 |

## 3. 先輩エンジニアの思考プロセス 💭

なぜ認証機能を自作せず、Fortifyのようなパッケージを使うのでしょうか？その背景にあるプロフェッショナルの判断基準を学びましょう。

### Point 1: 「車輪の再発明」はしない。特にセキュリティは専門家に任せる

認証機能は、多くのWebアプリケーションで必要とされる共通の機能です。これをプロジェクトのたびにゼロから作るのは「車輪の再発明」であり、多大な時間と労力の無駄です。特に、認証はアプリケーションのセキュリティの根幹をなす部分であり、少しでも実装に不備があれば、ユーザーの個人情報漏洩などの重大な事故に繋がりかねません。Laravelの公式パッケージであるFortifyは、世界中の多くのエンジニアによって使われ、テストされ、改善され続けています。自作の認証機能よりもはるかに信頼性が高く、安全です。セキュリティに関わる機能は、実績のある専門的なライブラリに任せるのが、プロとして当然の判断です。

### Point 2: バックエンドとフロントエンドの「分離」を意識する

Laravelは、認証機能を手軽に導入できるスターターキットとして、**Breeze**や**Jetstream**も提供しています。これらは、認証のバックエンドロジックだけでなく、Tailwind CSSで美しくデザインされたUI（ログイン画面や登録画面のBladeファイル）もセットで提供してくれます。非常に便利な一方で、UIのカスタマイズ性が低いという側面もあります。今回のプロジェクトのように、UI（Bladeファイル）が別途提供されている場合や、フロントエンドをVue.jsやReactのようなJavaScriptフレームワークで完全に分離して開発する場合には、UIを含まない**Fortify**が最適です。Fortifyは認証の「処理」だけを担当してくれるため、フロントエンドの技術選定やデザインに一切影響を与えません。このように、バックエンドとフロントエンドの役割を明確に分離することは、モダンなWeb開発における重要な考え方です。

### Point 3: 「ミドルウェア」という関所の概念を理解する

「ログインしているユーザーしかアクセスできない」というアクセス制御は、どうやって実現するのでしょうか？ここで登場するのが**ミドルウェア**です。ミドルウェアは、ユーザーからのリクエストがコントローラーのアクションに到達する「前」に実行される処理で、アプリケーションの「関所」のような役割を果たします。Laravelには、ユーザーがログインしているかどうかをチェックする`auth`というミドルウェアが標準で用意されています。ルート定義で特定のURLにこの`auth`ミドルウェアを指定しておけば、リクエストがそのURLに到達した際に、まず`auth`ミドルウェアが「このユーザーはログインしているか？」をチェックします。もしログインしていなければ、コントローラーの処理に進ませることなく、ログインページにリダイレクトさせます。このように、ミドルウェアを使うことで、アクセス制御のロジックをコントローラーから分離し、一元管理することができます。

### Point 4: パッケージの導入は「設定ファイルの公開」から始まる

`composer`でFortifyをインストールしただけでは、まだ何も起こりません。パッケージの機能を有効にし、自分のアプリケーションに合わせてカスタマイズするためには、まず`vendor:publish`というコマンドを実行する必要があります。このコマンドは、パッケージ内に隠されている設定ファイル（`config/fortify.php`など）や、認証処理の本体である「アクション」クラスなどを、自分のアプリケーションのディレクトリ（`config/`や`app/`）にコピー（公開）します。公開されたこれらのファイルを編集することで、私たちはFortifyの挙動を細かくコントロールできるようになります。例えば、ユーザー登録機能を無効にしたり、ログイン後のリダイレクト先を変更したりといったカスタマイズが可能です。外部パッケージを導入する際は、まず`vendor:publish`で設定ファイルを公開し、それを読むことから始めるのが基本のステップです。

## 4. 実装 🚀

### 4.1. Laravel Fortifyのインストール

まず、Composerを使ってFortifyパッケージをプロジェクトに追加します。

```bash
sail composer require laravel/fortify
```

### 4.2. Fortifyのリソース公開

次に、`vendor:publish`コマンドを実行して、Fortifyの設定ファイルやアクションクラスなどをアプリケーション内に公開します。

```bash
sail artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

このコマンドにより、以下のファイルが主に作成されます。
- `config/fortify.php` （Fortifyの動作を設定するファイル）
- `app/Providers/FortifyServiceProvider.php` （Fortifyのサービスプロバイダ）
- `app/Actions/Fortify/` ディレクトリ （ユーザー作成やパスワード更新などのロジック本体）


### 4.3. ログイン後のリダイレクト先を変更する

Fortifyのデフォルトでは、ログイン後のリダイレクト先は`/home`になっています。これを管理画面のトップページである`/admin`に変更します。この設定は2つのファイルで行う必要があります。

#### 4.3.1. `RouteServiceProvider`の変更

`app/Providers/RouteServiceProvider.php`を開き、`HOME`定数の値を`/admin`に変更します。これは、Laravelアプリケーション全体の「ホーム」の場所を定義するものです。

```php
// app/Providers/RouteServiceProvider.php

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin'; // '/home' から '/admin' に変更

    // ...
}
```

#### 4.3.2. `fortify.php`の変更

次に、Fortify自体の設定ファイルである`config/fortify.php`を開き、`home`の値を変更します。これにより、Fortifyの認証プロセスが完了した後のリダイレクト先が明確に指定されます。

```php
// config/fortify.php

return [
    // ...

    'home' => '/admin', // '/home' から '/admin' に変更

    // ...
];
```

この2つの設定を変更することで、ログイン後のリダイレクト先が一貫して`/admin`になります。

### 4.4. Fortifyのサービスプロバイダ登録

Fortifyをアプリケーションに認識させるため、`config/app.php`を開き、`providers`配列に`FortifyServiceProvider`を追加します。

```php
// config/app.php

    /*
     * Application Service Providers...
     */
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    // App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\FortifyServiceProvider::class, // この行を追加
```

### 4.5. 認証ビューの設定

Fortifyはバックエンドのロジックしか提供しないため、ログイン画面や登録画面をどのBladeファイルで表示するかを教えてあげる必要があります。

`app/Providers/FortifyServiceProvider.php`を開き、`boot`メソッドに以下のコードを追加します。今回はBladeファイルが提供されているため、そのパスを指定します。

```php
// app/Providers/FortifyServiceProvider.php

use Laravel\Fortify\Fortify;

// ... bootメソッド内
public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // ログインビューの設定を追加
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 登録ビューの設定を追加
        Fortify::registerView(function () {
            return view('auth.register');
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
```

### 4.6. ユーザーモデルの更新

Fortifyは、`users`テーブルに`two_factor_secret`などのカラムが存在することを期待します。これらのカラムを追加するためのマイグレーションはFortifyに含まれていますが、既存の`users`テーブルのマイグレーションを更新する方法がシンプルです。

`database/migrations/..._create_users_table.php`を開き、以下のようにカラムを追加します。

```php
// database/migrations/..._create_users_table.php

Schema::create("users", function (Blueprint $table) {
    $table->id();
    $table->string("name");
    $table->string("email")->unique();
    $table->timestamp("email_verified_at")->nullable();
    $table->string("password");
    $table->rememberToken();
    $table->timestamps();
});
```

**注意:** もし`sail artisan migrate`を既に実行している場合は、一度すべてのテーブルをリセットする必要があります。

```bash
# データベースをリフレッシュ（全テーブルを削除して再作成）
sail artisan migrate:fresh
```

### 4.7. ルートの保護

最後に、管理画面（`/admin`）へのルートを`auth`ミドルウェアで保護します。`routes/web.php`を開き、以下のようにルートをグループ化します。

```php
// routes/web.php

use App\Http\Controllers\AdminController;

// ... 他のuse文

Route::middleware("auth")->group(function () {
    Route::get("/admin", [AdminController::class, "index"]);
    // 今後追加する管理画面のルートもこの中に追加する
});
```

これで実装は完了です。`/register`にアクセスしてユーザー登録を行い、`/login`でログインした後、`/admin`にアクセスできること、ログアウト後に`/admin`にアクセスすると`/login`にリダイレクトされることを確認してください。

## 5. コードの詳細解説 🔍

### `composer require laravel/fortify`
- **何をしているか**: Composerに、このプロジェクトが`laravel/fortify`というパッケージに依存していることを伝えます。Composerは`packagist.org`というリポジトリからパッケージをダウンロードし、`vendor`ディレクトリに配置します。
- **なぜそう書くか**: `require`は新しいパッケージをプロジェクトに追加するためのComposerの標準的なコマンドです。`laravel/fortify`は、パッケージの作者（`laravel`）とパッケージ名（`fortify`）をスラッシュで区切ったものです。

### `sail artisan vendor:publish --provider="..."`
- **何をしているか**: `Laravel\Fortify\FortifyServiceProvider`というサービスプロバイダに関連付けられた「公開可能なリソース（設定ファイルなど）」を、`vendor`ディレクトリからアプリケーションの適切なディレクトリ（`config`や`app`）にコピーします。
- **なぜそう書くか**: `--provider`オプションでどのサービスプロバイダのリソースを公開するかを正確に指定します。これにより、他のパッケージの設定ファイルと混ざることなく、目的のファイルだけを安全にコピーできます。

### `config/app.php` へのサービスプロバイダ登録
- **何をしているか**: `config/app.php`の`providers`配列に`App\Providers\FortifyServiceProvider::class`を追加することで、Laravelの起動時にそのサービスプロバイダが読み込まれるようになります。
- **なぜそう書くか**: サービスプロバイダは、アプリケーションの様々な機能を「起動」し、サービスコンテナに登録する役割を持ちます。ここに登録することで、Fortifyの機能がアプリケーション全体で利用可能になります。

### `app/Providers/FortifyServiceProvider.php` のビュー設定
- **何をしているか**: `Fortify::loginView(...)`や`Fortify::registerView(...)`を使い、Fortifyに「ログインが必要な場面になったら、このビューを表示してください」と教えています。
- **なぜそう書くか**: FortifyはUIを持たないため、開発者がどのビューを使うかを明示的に指定する必要があります。`view('auth.login')`のように、`resources/views`からのパスをドット記法で指定するのがLaravelの標準です。

### `routes/web.php` のルート保護
- **何をしているか**: `Route::middleware('auth')->group(...)`を使い、クロージャ内に定義された全てのルートを`auth`ミドルウェアで保護しています。
- **なぜそう書くか**: `auth`ミドルウェアは、リクエストがコントローラーに到達する前にユーザーがログインしているかをチェックします。`group`を使うことで、管理画面のルートが増えても、一つひとつに`middleware('auth')`を書く必要がなくなり、コードの可読性と保守性が向上します。

## 6. How to: この実装にたどり着くための調べ方 🗺️

実務で「認証機能」というタスクを与えられた時、どのようにAIを活用して学習・実装を進めるか、4つのステップで見ていきましょう。

### Step 1: 公式ドキュメントを読みやすくまとめる

まずは、Laravelの認証機能の全体像を掴むために、AIに公式ドキュメントの要約を依頼します。

> **プロンプト例**
> 
> 以下はLaravelの公式ドキュメントの一部です。 これを「認証機能を実装できるように」分かりやすくまとめてください。
> 
> 出力してほしい内容：
> - 重要ポイント（10行以内）
> - 用語の説明（重要なものだけ：Fortify, Breeze, Jetstream, Middleware）
> - できること / できないこと（各スターターキットの境界をはっきり）
> - よくある落とし穴（回避策つき）
> - 最小で動かすための手順（コードはまだ不要）
> 
> --- ここから ---
> (ここにLaravel公式サイトの「セキュリティ：認証」のページのテキストをコピー＆ペーストする)
> --- ここまで ---

- **プロンプトの考え方**: 新しい技術を学ぶ時、公式ドキュメントは最も正確ですが、情報量が多くて圧倒されがちです。そこで、AIに「実装」というゴールを明確に伝え、情報を整理・要約してもらうことで、学習の地図を手に入れます。特に「できること/できないこと」で各ツールの境界をはっきりさせることが、技術選定の精度を上げます。

### Step 2: 「なぜそうなる？」をはっきりさせる（理解を固める）

全体像を掴んだら、次は「ミドルウェア」のような重要概念の理解を深めます。

> **プロンプト例**
> 
> Laravelのミドルウェアについて、私の理解はこうです：
> 「ユーザーからのリクエストがコントローラーに届く前に実行されるチェック機能。関所みたいなもの。」
> 
> お願い：
> 1) 正しいかチェックして、間違いがあれば「反例」で教えてください
> 2) 仕組みを「入力→中で起きること→出力」で説明してください
> 3) どこまでがミドルウェアの範囲か（境界）を教えてください
> 4) よくある勘違いを3つ教えてください
> 5) 理解チェック問題を3問ください（答えつき）

- **プロンプトの考え方**: 自分の言葉で理解を説明し、それをAIに添削してもらうことで、知識の解像度を飛躍的に高めることができます。「反例」や「勘違い」を教えてもらうことで、表面的な理解から一歩踏み込めます。最後に問題を解くことで、知識が本当に定着したかを確認できます。

### Step 3: 実装に落とす（指定フォーマット：手順→解説→例→解説）

概念を理解したら、いよいよ実装です。AIにガイド役を頼み、段階的に進めます。

> **プロンプト例**
> 
> 目的は「LaravelにFortifyを使って認証機能を実装する」ことです。
> 制約は「UIは既存のBladeファイルを使う」「ログイン後のリダイレクト先は/admin」です。
> 前提知識は「Laravelの基本的なルーティングとコントローラーは理解している」です。
> 
> 次の順番で出力してください：
> 
> A. 実装の手順・方針
> - まず全体の方針（なぜそのやり方か）
> - 手順を1〜Nで（各手順に「できたらOK」の条件も書く）
> 
> B. 関連技術の解説
> - 必要な関連知識を3〜7個（ServiceProvider, vendor:publishなど）
> - 各項目は「一言で説明 → この実装で何に使う → 注意点」
> 
> C. 実装例
> - まず最小で動く例
> - 次に実務向けの拡張例（今回は不要）
> 
> D. コードの解説
> - 重要な部分だけ「何をしてるか」「なぜそう書くか」
> - よくあるバグと対策

- **プロンプトの考え方**: 目的、制約、前提知識を最初に伝えることで、AIはあなたに最適な回答を生成しやすくなります。出力形式を細かく指定することで、単なるコードの羅列ではなく、「手順→理論→実践→解説」という構造化された学習コンテンツをAIに作らせることができます。「できたらOK」の条件を加えさせることで、各ステップのゴールが明確になり、着実に実装を進められます。

### Step 4: 設計レビュー（指摘をもらう）

実装が完了したら、その設計が妥当かAIにレビューしてもらいます。

> **プロンプト例**
> 
> 以下の設計をレビューしてください。
> 
> - 目的：管理画面へのアクセスをログインユーザーのみに制限する
> - 設計案：`routes/web.php`で、`Route::middleware('auth')->group(...)`を使い、`/admin`を含む全ての管理画面ルートを囲む
> - 不安な点：将来、管理者権限(admin)と一般ユーザー権限(user)でアクセスできる範囲を分けたくなった時に、この設計で対応できるか不安です。
> 
> 見てほしい観点：
> - 正しく動くか（抜け漏れ）
> - 変更しやすいか（拡張性）
> - セキュリティ
> 
> 出力：
> - 指摘を「重要度：高/中/低」で出す
> - 各指摘に「理由」「影響」「直し方」をつける
> - 最後に「この設計が失敗しやすい例」を3つ出す

- **プロンプトの考え方**: 自分の設計案と「不安な点」を具体的に伝えることで、AIから的確なフィードバックを引き出します。レビューの観点を指定することで、多角的な視点からのチェックを促します。将来の拡張性（今回は権限分離）について質問することで、目先のタスクだけでなく、長期的な運用を見据えた設計のヒントを得ることができます。

## 7. 提供されているBladeファイルの確認 📄

今回のプロジェクトでは、認証画面のBladeファイルが`coachtech-prepared-blade-list`リポジトリに予め用意されています。具体的には以下のファイルです。

- `resources/views/auth/login.blade.php`: ログインフォーム
- `resources/views/auth/register.blade.php`: 新規登録フォーム

これらのファイルには、Fortifyが期待するフォームのアクションURL（`/login`, `/register`）や、入力フィールドの`name`属性（`email`, `password`など）が正しく設定されています。`FortifyServiceProvider`でこれらのビューを指定したことで、Fortifyのバックエンドロジックと提供されたフロントエンドがうまく連携して動作するのです。

## 8. まとめ ✨

このチャプターでは、Laravel Fortifyを使って、堅牢な認証機能をアプリケーションに組み込みました。

- **認証の重要性**: アプリケーションの安全性を保つために、認証が不可欠であることを学びました。
- **Fortifyの役割**: 認証のバックエンドロジックを提供し、UIと分離されていることで高い柔軟性を持つことを理解しました。
- **ミドルウェアによるアクセス制御**: `auth`ミドルウェアを使い、特定のルートをログインユーザーのみに制限する方法を実践しました。

アプリケーションに「門番」を設置できたことで、安心して管理機能の開発に進むことができます。しかし、ユーザーからの入力はまだ信用できません。次のチャプターでは、ユーザーからのリクエストが妥当なものであるかを検証する「門番その２」、**FormRequestによるバリデーション**を実装していきます。
