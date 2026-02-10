# 環境構築手順（確認テスト用）

この手順は絶対のものです。他の環境構築手順に変更しないように細心の注意を払ってください。

## 2. 環境構築手順

### 2.1. Laravelプロジェクトの作成 (Laravel 10.x)

以下のDockerコマンドを実行して、Laravel 10.xを明示的に指定してプロジェクトを作成します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer create-project laravel/laravel:^10.0 contact-form-app
```

### 2.2. Laravel Sailのインストール

プロジェクト作成後、contact-form-app ディレクトリに移動し、Laravel Sailをインストールします。

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

### 2.3. フロントエンドのセットアップ (Vite & Tailwind CSS)

#### NPM依存パッケージのインストール

```bash
sail npm install
```

#### Tailwind CSSのインストール

```bash
sail npm install -D tailwindcss@^3.4.0 postcss autoprefixer
```

#### 設定ファイルの生成

```bash
sail npx tailwindcss init -p
```

#### Tailwind CSSのテンプレートパス設定

tailwind.config.js を開き、contentプロパティを以下のように設定します。

```javascript
/** @type {import('tailwindcss').Config} */
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

#### CSSファイルにTailwindディレクティブを追加

resources/css/app.css の中身を以下の3行に置き換えます。

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

#### Vite開発サーバーの起動

新しいターミナルを開いて実行します。このコマンドは開発中、常に実行したままにしてください。

```bash
sail npm run dev
```

### 2.4. .env ファイルとphpMyAdminの設定

#### .env ファイルの確認

.env ファイルを開き、データベース接続情報が以下と一致していることを確認します。

```ini
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

#### phpMyAdminの追加

compose.yaml を開き、mysql サービスの後に以下の設定を追加します。

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

### 2.5. Sailの起動とエイリアス設定

#### Sailの起動

```bash
./vendor/bin/sail up -d
```

#### エイリアス設定

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc
# または bash の場合
# echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.bashrc

# シェルを再起動
exec $SHELL
```
