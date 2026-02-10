# 確認テスト要件分析

## 基本機能（Basic Branch）

### 1. データベース構造

#### テーブル構成
- **users**: ユーザー情報（Laravel Fortifyによる認証）
- **categories**: お問い合わせの種類
  - id, content, timestamps
- **contacts**: お問い合わせ情報
  - id, category_id, first_name, last_name, gender, email, tel, address, building, detail, timestamps
  - gender: 1=男性, 2=女性, 3=その他
  - tel: 10〜11桁、ハイフンなし

### 2. 機能要件

#### 2.1 お問い合わせフォーム（フロントエンド）
- **URL**: `/`
- **機能**:
  - 入力フォーム表示
  - 確認画面表示（JavaScriptで切り替え）
  - バリデーション
  - データ送信

#### 2.2 お問い合わせ完了画面
- **URL**: `/thanks`
- **機能**: 送信完了メッセージの表示

#### 2.3 管理画面（認証必須）
- **URL**: `/admin`
- **機能**:
  - お問い合わせ一覧表示（ページネーション: 7件/ページ）
  - 検索機能（キーワード、性別、カテゴリー、日付）
  - 詳細表示（モーダル）
  - 削除機能

#### 2.4 認証機能
- Laravel Fortifyを使用
- ログイン、ログアウト、ユーザー登録

### 3. API エンドポイント

#### 3.1 カテゴリー関連
- `GET /api/categories`: カテゴリー一覧取得

#### 3.2 お問い合わせ関連
- `GET /api/contacts`: お問い合わせ一覧取得（検索・ページネーション対応）
- `POST /api/contacts`: お問い合わせ登録
- `GET /api/contacts/{contact}`: お問い合わせ詳細取得
- `DELETE /api/contacts/{contact}`: お問い合わせ削除

### 4. バリデーションルール

#### お問い合わせ登録（StoreContactRequest）
- first_name: 必須、文字列、最大255文字
- last_name: 必須、文字列、最大255文字
- gender: 必須、整数、1,2,3のいずれか
- email: 必須、文字列、メール形式、最大255文字
- tel: 必須、文字列、正規表現（10〜11桁の数字）
- address: 必須、文字列、最大255文字
- building: 任意、文字列、最大255文字
- category_id: 必須、categoriesテーブルに存在するID
- detail: 必須、文字列、最大120文字

#### お問い合わせ検索（IndexContactRequest）
- keyword: 任意、文字列、最大255文字
- gender: 任意、整数、0,1,2,3のいずれか
- category_id: 任意、整数、categoriesテーブルに存在するID
- date: 任意、日付形式

### 5. リソース（APIレスポンス整形）

#### ContactResource
- id, category, first_name, last_name, gender, email, tel, address, building, detail

#### CategoryResource
- id, content

### 6. フロントエンド実装

#### JavaScript構成
- `resources/js/admin/`: 管理画面用
  - category-select-loader.js: カテゴリー選択肢の読み込み
  - contact-detail-modal.js: 詳細モーダル
  - contact-list-renderer.js: 一覧表示
  - gender-helper.js: 性別表示のヘルパー
  - index.js: メインスクリプト
  - search-form-handler.js: 検索フォーム処理
  - url-params-manager.js: URLパラメータ管理
- `resources/js/api/`: API通信
  - base.js: 基本設定
  - categories.js: カテゴリーAPI
  - contacts.js: お問い合わせAPI
- `resources/js/contact/`: お問い合わせフォーム用

#### Bladeテンプレート（提供済み）
- contact/index.blade.php: お問い合わせフォーム
- contact/_form.blade.php: フォーム部品
- contact/thanks.blade.php: 完了画面
- admin/index.blade.php: 管理画面
- auth/login.blade.php: ログイン画面
- auth/register.blade.php: ユーザー登録画面
- layouts/app.blade.php: 認証後レイアウト
- layouts/guest.blade.php: ゲストレイアウト

---

## 応用機能（Advanced Branch）

### 1. 追加データベース構造

#### 追加テーブル
- **tags**: タグ情報
  - id, name (最大50文字、ユニーク), timestamps
- **contact_tag**: 中間テーブル（多対多リレーション）
  - id, contact_id, tag_id, timestamps
  - ユニーク制約: (contact_id, tag_id)

### 2. 追加機能要件

#### 2.1 タグ管理機能
- タグの作成、更新、削除
- お問い合わせへのタグ付け（多対多リレーション）

### 3. 追加API エンドポイント

#### タグ関連
- `GET /api/tags`: タグ一覧取得
- `POST /api/tags`: タグ登録
- `PUT /api/tags/{tag}`: タグ更新
- `DELETE /api/tags/{tag}`: タグ削除

### 4. 追加バリデーションルール

#### タグ登録（StoreTagRequest）
- name: 必須、文字列、最大50文字、ユニーク

#### タグ更新（UpdateTagRequest）
- name: 必須、文字列、最大50文字、ユニーク（自分自身を除く）

### 5. モデルリレーション変更

#### Contact モデル
```php
public function tags()
{
    return $this->belongsToMany(Tag::class)->withTimestamps();
}
```

#### Tag モデル
```php
public function contacts()
{
    return $this->belongsToMany(Contact::class)->withTimestamps();
}
```

### 6. その他の変更点

- FormRequestの追加（バリデーションロジックの分離）
- Resourceの追加（APIレスポンスの整形）
- エクスポート機能の追加（ExportContactRequest）

---

## 学習者が実装すべき内容

### 基本機能
1. マイグレーションファイルの作成
2. モデルの作成とリレーション定義
3. FormRequestの作成（バリデーション）
4. APIコントローラーの実装
5. Resourceの作成
6. ルーティングの設定
7. 認証機能の設定（Laravel Fortify）
8. JavaScriptによるフロントエンド実装

### 応用機能
1. タグ機能のマイグレーション作成
2. 多対多リレーションの実装
3. タグAPIの実装
4. タグ管理機能の追加
5. エクスポート機能の実装

---

## 提供されるもの

1. Bladeファイル（完成版）
2. 要件書
3. 環境構築手順

## 学習者が作成するもの

1. バックエンドのビジネスロジック
   - マイグレーション
   - モデル
   - コントローラー
   - FormRequest
   - Resource
   - ルーティング
2. フロントエンドのJavaScript
   - API通信
   - DOM操作
   - バリデーション
