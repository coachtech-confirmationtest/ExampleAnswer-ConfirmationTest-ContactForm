# 復習教材 チャプター構成（最終版）

## 基本機能（Chapter 0-10）

### Chapter 0: 要件の確認
確認テストで実装すべき機能の全体像を把握する。

### Chapter 1: 環境構築
Laravel Sailを使用した開発環境の構築。

### Chapter 2: データベース設計とマイグレーション
categoriesテーブルとcontactsテーブルのマイグレーション作成。

### Chapter 3: モデルとリレーションの実装
CategoryモデルとContactモデルの作成、リレーション定義。

### Chapter 4: FormRequestによるバリデーション
StoreContactRequest、IndexContactRequestの実装。

### Chapter 5: シーダーの実装
CategorySeeder、ContactSeeder、UserSeederの作成。

### Chapter 6: API Resourceによるレスポンス整形
ContactResource、CategoryResourceの実装。

### Chapter 7: APIコントローラー（カテゴリー）
CategoryControllerの実装。

### Chapter 8: APIコントローラー（お問い合わせCRUD）
ContactControllerのindex、store、show、destroyメソッドの実装。

### Chapter 9: ルーティングの設定
routes/web.phpとroutes/api.phpの設定。

### Chapter 10: Laravel Fortifyによる認証機能
Fortifyのインストールと設定、ユーザー認証機能の実装。

---

## 応用機能（Chapter 11-15）

### Chapter 11: タグ機能のマイグレーション
tagsテーブルとcontact_tag中間テーブルのマイグレーション作成。

### Chapter 12: タグ機能のモデルとリレーション
Tagモデルの作成と、ContactモデルとTagモデルの多対多リレーション定義。

### Chapter 13: タグAPIの実装
TagControllerのCRUD実装、StoreTagRequest、UpdateTagRequest、TagResourceの作成。

### Chapter 14: お問い合わせ登録時のタグ紐付け機能
ContactControllerのstoreメソッドとindexメソッドにタグ機能を追加、StoreContactRequestの更新。

### Chapter 15: CSVエクスポート機能の実装
ContactControllerのexportメソッド実装、ExportContactRequestの作成。
