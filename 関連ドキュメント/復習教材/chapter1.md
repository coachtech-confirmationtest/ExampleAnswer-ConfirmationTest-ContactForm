# Chapter 1: 開発の第一歩 - Laravel Sailでモダンな開発環境を構築する

## 🎯 このセクションで学ぶこと

このチャプターでは、本格的な開発を始める前の準備体操として、Laravelアプリケーションを動かすための「開発環境」を構築します。具体的には、DockerとLaravel Sailという現代的なツールを使い、PHP、MySQL、そしてフロントエンドのビルド環境まで、必要なものがすべて揃った快適な開発キッチンをセットアップします。この手順は、今後の開発の基盤となる非常に重要なステップです。

## 1. はじめに 📖

### なぜ環境構築が重要なのか？

プログラミングはよく料理に例えられます。どんなに素晴らしいレシピ（コード）があっても、食材を切るための包丁や、火にかけるためのコンロがなければ料理は始まりません。環境構築は、まさにこの「キッチンの準備」にあたります。快適で整理されたキッチンがあれば、その後の料理（コーディング）がスムーズに進むように、クリーンで再現性の高い開発環境は、開発効率を飛躍的に向上させます。

### DockerとLaravel Sailのメリット

昔は、自分のPCに直接PHPやMySQLをインストールしていましたが、バージョン違いによるエラーや、PCの環境を汚してしまうといった問題がありました。そこで登場したのが**Docker**です。Dockerは、アプリケーションの実行に必要な環境を「コンテナ」という隔離された箱にまとめる技術です。これにより、「自分のPCでは動いたのに、他の人のPCでは動かない」といった環境差異の問題を解決できます。

そして**Laravel Sail**は、そのDockerをLaravel開発に特化させて、誰でも簡単に扱えるようにした素晴らしいツールです。複雑なDockerのコマンドを覚えなくても、いくつかの簡単な`sail`コマンドを実行するだけで、Laravel開発に必要なサーバーやデータベースを起動・停止できます。この復習教材では、このLaravel Sailを全面的に採用し、モダンで実践的な開発スタイルを学びます。

## 2. 要件の確認 📋

このチャプターで構築する開発環境の具体的なスペックは以下の通りです。これらのツールが連携し、一つの開発環境として機能します。

| コンポーネント | 技術/ツール | バージョン/種類 | 役割 |
| :--- | :--- | :--- | :--- |
| **アプリケーション** | Laravel | `10.x` | PHPフレームワーク。Webアプリケーションの骨格。 |
| **実行環境** | PHP | `8.2` | Laravelを動かすためのプログラミング言語。 |
| **Webサーバー** | Nginx | - | ユーザーからのリクエストを受け付け、PHPに処理を渡す。 |
| **データベース** | MySQL | - | お問い合わせ情報などを永続的に保存する。 |
| **フロントエンド** | Vite | - | JavaScriptやCSSを効率的に管理・ビルドするツール。 |
| | Tailwind CSS | `^3.4.0` | モダンで効率的なCSSフレームワーク。 |
| **開発ツール** | Docker | - | コンテナ技術。環境の隔離と再現性を担保する。 |
| | Laravel Sail | - | DockerをLaravel用にラップしたコマンドラインツール。 |
| | phpMyAdmin | `latest` | ブラウザからMySQLデータベースを視覚的に操作するツール。 |

## 3. 先輩エンジニアの思考プロセス 💭

なぜこのような構成で環境を構築するのでしょうか？経験豊富なエンジニアの思考プロセスを追いながら、その背景にある「理由」を理解しましょう。

### Point 1: 「環境の再現性」を最優先する → Dockerの採用

実務では、複数のエンジニアがチームで開発を進めます。もし、AさんのPCでは動くのに、BさんのPCではエラーが出る、といった状況が頻発すれば、開発は全く進みません。この問題の根本原因は、各々のPC環境（OS、ライブラリのバージョンなど）が微妙に異なることです。Dockerは、この問題を解決するための銀の弾丸です。アプリケーションとそれが依存するライブラリをすべて「コンテナ」という一つの箱にパッケージングすることで、誰のPC上でも、本番サーバー上でも、全く同じ環境を寸分違わず再現できます。これは「私の環境では動きます」という言い訳をなくし、開発者が本質的なコードの課題に集中できるようにするための、現代開発における必須の考え方です。

### Point 2: 「楽をしたい」は正義である → Laravel Sailの活用

Dockerは非常に強力ですが、設定ファイル（`docker-compose.yml`）の記述やコマンドが複雑で、初学者にとっては少し敷居が高いのも事実です。しかし、Laravelチームは「開発者がもっと創造的な作業に集中できるように」という思想のもと、この複雑さを隠蔽してくれるLaravel Sailを提供してくれました。Sailは、Laravel開発に最適化されたDocker環境を、`sail up`や`sail artisan`といったシンプルなコマンドで操作できるようにしたものです。これは、いわば「Laravel専用の全自動キッチンシステム」のようなもの。私たちは、キッチンの配管や電気工事（Dockerの詳細設定）を気にすることなく、すぐに料理（コーディング）を始めることができるのです。この「楽をするための工夫」こそが、生産性を高める鍵となります。

### Point 3: フロントエンド開発の効率を最大化する → ViteとTailwind CSS

現代のWebアプリケーションでは、JavaScriptを使った動的な表現が不可欠です。しかし、多数のJavaScriptやCSSファイルをそのままブラウザに読み込ませると、ページの表示速度が遅くなる原因になります。そこで**Vite**のようなビルドツールが登場します。Viteは、開発中は変更したファイルだけを高速にブラウザに反映させ、本番用にビルドする際には、すべてのファイルを最適化し、一つにまとめてくれます。また、**Tailwind CSS**は、「ユーティリティファースト」という考え方に基づいたCSSフレームワークです。`class="text-blue-500 font-bold"`のように、あらかじめ用意されたクラスを組み合わせるだけで、デザインを迅速に構築できます。これにより、CSSファイルとHTMLファイルを何度も行き来する手間が省け、開発効率が劇的に向上します。

### Point 4: 「データは目で見て確認する」が鉄則 → phpMyAdminの導入

コードを書いて機能を実装したとき、「本当にデータがデータベースに正しく保存されているか？」を直接確認したくなる場面は頻繁にあります。コマンドライン（黒い画面）からデータベースに接続して確認することもできますが、もっと直感的に、ブラウザ上で表形式でデータを確認できた方が圧倒的に効率的です。**phpMyAdmin**は、まさにそのためのツールです。データベースのテーブル構造を見たり、中のデータを直接編集したり、SQLを試したりすることができます。これは、開発中のデバッグ作業において、強力な味方となります。目に見えないデータを可視化することは、バグの早期発見に繋がる重要な習慣です。

## 4. 実装 🚀

それでは、実際にコマンドを実行して環境を構築していきましょう。指定された手順通りに、一つずつ丁寧に進めてください。コマンドはコピー＆ペーストで実行するのが確実です。

### 4.1. Laravelプロジェクトの作成 (Laravel 10.x)

まずは、このプロジェクトの土台となるLaravelの骨組みを作成します。

```bash
# Laravel 10.x を指定してプロジェクトを作成
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer create-project laravel/laravel:^10.0 contact-form-app
```

### 4.2. Laravel Sailのインストール

次に、Docker操作を簡単にするためのツール「Laravel Sail」をプロジェクトに導入します。

```bash
# プロジェクトディレクトリに移動
cd contact-form-app

# Laravel Sailをインストール
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer require laravel/sail --dev

# Sailの設定ファイルをパブリッシュ（MySQLを選択）
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    php artisan sail:install --with=mysql
```

### 4.3. .env ファイルの設定

`.env` ファイルを開き、データベース接続情報が以下と一致していることを確認します。

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=contactform
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4.4. フロントエンドとツールのセットアップ

ここでは、コンテナを起動し、開発を効率化するための周辺ツールを導入します。

#### Sailの起動とエイリアス設定

まず、Dockerコンテナを起動し、以降のコマンドを短く打てるように設定します。

**1. Sailの起動**

```bash
./vendor/bin/sail up -d
```

**2. エイリアスの設定**

```bash
echo "alias sail=\"[ -f sail ] && bash sail || bash vendor/bin/sail\"" >> ~/.zshrc
source ~/.zshrc
```

#### フロントエンドのセットアップ (Vite & Tailwind CSS)

**1. NPM依存パッケージのインストール**

```bash
sail npm install
```

**2. Tailwind CSSのインストール**

```bash
sail npm install -D tailwindcss@^3.4.0 postcss autoprefixer
sail npm install alpinejs
```

**3. 設定ファイルの生成**

```bash
sail npx tailwindcss init -p
```

**4. Tailwind CSSのテンプレートパス設定**

`tailwind.config.js` を開き、TailwindがCSSを適用するテンプレートファイル（Bladeファイルなど）のパスを指定します。

```javascript
/** @type {import("tailwindcss").Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

**5. bladeファイルの挿入**

本プロジェクトのresourseファイルを[coachtech-prepared-blade-list/Preparedblade-ConfirmationTest-ContactForm](https://github.com/coachtech-prepared-blade-list/Preparedblade-ConfirmationTest-ContactForm) リポジトリのmainブランチにあるresourseファイルと入れ替えてください。
openコマンドを利用してcloneしてきたファイルをGUIで移動する、もしくはmvコマンドを活用して入れ替えるのが最も早い方法です。

**6. Vite開発サーバーの起動**

```bash
# 新しいターミナルを開いて実行
sail npm run dev
```

#### phpMyAdminの追加

`compose.yaml` を開き、`mysql` サービスの後に以下の設定を追加してください。

```yaml
    phpmyadmin:
        image: 'phpmyadmin:latest'
        ports:
            - '${FORWARD_PHPMYADMIN_PORT:-8080}:80'
        environment:
            PMA_HOST: mysql
            PMA_USER: '${DB_USERNAME}'
            PMA_PASSWORD: '${DB_PASSWORD}'
        networks:
            - sail
        depends_on:
            - mysql
```

#### Sailの再起動

```bash
sail up -d
```

#### アプリケーションキーの生成

```bash
sail artisan key:generate
```

## 5. コードの詳細解説 🔍

### `docker run` コマンドの解説

この一見複雑なコマンドも、一つ一つのオプションに分解すれば理解できます。

| 部分 | 説明 |
|:---|:---|
| `docker run` | 新しいコンテナを起動するコマンド。Dockerの最も基本的なコマンドです。 |
| `--rm` | コンテナ停止時に自動的にコンテナを削除します。一時的なコマンド実行に便利です。 |
| `-u "$(id -u):$(id -g)"` | 現在のユーザーのIDとグループIDでコンテナを実行します。これにより、コンテナ内で作成されたファイルの所有者が現在のユーザーになり、パーミッションの問題を防ぎます。 |
| `-v "$(pwd):/var/www/html"` | 現在のディレクトリをコンテナの`/var/www/html`にマウントします。ローカルのファイルをコンテナ内で直接編集できるようになります。 |
| `-w /var/www/html` | コンテナ内の作業ディレクトリを指定します。この後のコマンドが、このディレクトリで実行されます。 |
| `laravelsail/php82-composer:latest` | 使用するDockerイメージを指定します。PHP 8.2とComposerがプリインストールされたLaravel Sail公式イメージです。 |
| `composer create-project ...` | Composerを使ってLaravelプロジェクトを作成するコマンドです。`laravel/laravel:^10.0`でバージョン10を指定しています。 |

### `sail:install` コマンドの解説

| 部分 | 説明 |
|:---|:---|
| `php artisan sail:install` | Laravel SailをプロジェクトにセットアップするArtisanコマンドです。`compose.yaml`というDockerの設定ファイルを生成します。 |
| `--with=mysql` | 使用するサービスとしてMySQLを選択するオプションです。他にも`redis`や`pgsql`などを選択できます。 |

### `compose.yaml` の解説

`compose.yaml`は、Dockerコンテナの構成を定義する「設計図」です。`phpmyadmin`の追加部分は以下を意味します。

- **image**: `phpmyadmin:latest` - 使用するDockerイメージを指定します。
- **ports**: `8080:80` - ローカルPCの8080番ポートを、コンテナの80番ポートに接続します。これにより`http://localhost:8080`でアクセスできます。
- **environment**: コンテナ内で使用する環境変数を設定します。`PMA_HOST: mysql`で、phpMyAdminが接続すべきデータベースサーバーが`mysql`という名前のコンテナであることを伝えています。
- **networks**: `sail` - このコンテナが`sail`という名前のDockerネットワークに接続することを示します。同じネットワーク内のコンテナは、互いに名前で通信できます。
- **depends_on**: `mysql` - `mysql`コンテナが起動してから、`phpmyadmin`コンテナを起動するように依存関係を定義します。

## 6. How to: この実装にたどり着くための調べ方 🧐

環境構築はエラーが起きやすく、自力で解決する力が試される最初の関門です。ここでは、どのように情報を探し、問題を解決していくかの思考法を学びます。

### 1. 公式ドキュメントを起点にする

何かわからないことがあれば、まずは公式ドキュメントを参照するのが鉄則です。情報が最も正確で、最新だからです。

- **検索キーワード**: `laravel sail documentation`, `laravel 10 sail`

Laravelの公式サイトには、Sailを使った環境構築の手順が詳細に書かれています。今回の手順も、基本的には公式ドキュメントに基づいています。

### 2. エラーメッセージをそのまま検索する

`sail up`が失敗した、コマンドが通らないなど、エラーが発生したら、表示されたエラーメッセージをコピーしてそのままGoogleで検索するのが最も効果的です。

- **検索キーワード例**: `laravel sail "Cannot connect to the Docker daemon"`, `sail npm install error`

多くの場合、同じ問題に直面した開発者が世界中にいて、その解決策が技術ブログやQ&Aサイト（Stack Overflowなど）で見つかります。

### 3. AIに質問して理解を深める

具体的なエラー解決だけでなく、概念の理解を深めるためにもAIは非常に役立ちます。要件を丸投げするのではなく、段階的に質問していくのがコツです。

#### プロンプト例1: Dockerの概念を理解する

```
プログラミング初学者です。Dockerについて学習しています。

# 質問
- Dockerコンテナと仮想マシン（VM）の違いを、料理に例えて分かりやすく教えてください。
- 「コンテナは環境差異をなくす」と言われますが、それは具体的にどういう仕組みで実現しているのですか？
```

#### プロンプト例2: コマンドのオプションの意味を調べる

```
Laravel Sailの環境構築で、以下のコマンドを実行しました。

`docker run --rm -v "$(pwd):/var/www/html" laravelsail/php82-composer:latest composer install`

# 質問
- `--rm` オプションと `-v` オプションには、それぞれどのような意味がありますか？
- もしこれらのオプションを付けずに実行した場合、どのような問題が発生する可能性がありますか？
```

#### プロンプトの考え方とポイント

- **初学者の立場を伝える**: 自分のレベルを伝えることで、AIはより平易な言葉で説明してくれます。
- **具体的な疑問点を提示する**: 「Dockerについて教えて」のような漠然とした質問ではなく、「コンテナとVMの違い」「`--rm`オプションの意味」のように、知りたいことを具体的に絞って質問します。
- **「もし〜しなかったら？」と質問する**: ある手順やオプションの重要性を理解するために、「それをやらなかった場合に何が起こるか」を質問するのは非常に有効な学習方法です。

## 7. まとめ ✨

お疲れ様でした！このチャプターでは、Laravel Sailを使ってモダンな開発環境を構築しました。これで、快適にコーディングを進めるための土台が整いました。

- **DockerとSailの力**: 環境差異をなくし、誰でも同じ環境を簡単に再現できることの重要性を学びました。
- **フロントエンドのモダンな構成**: ViteとTailwind CSSを使って、効率的で高速なフロントエンド開発の準備をしました。
- **開発支援ツールの導入**: phpMyAdminを追加し、データベースを視覚的に確認できる環境を整えました。

これでようやく、アプリケーションの心臓部であるバックエンドのコードを書いていく準備ができました。次のチャプターでは、データベースの設計図である「マイグレーション」を作成し、アプリケーションがデータを保存するためのテーブルを作っていきます。
