# お問い合わせフォーム確認テスト - 復習教材の最終構成

## 基本機能（Chapter 0-9）

- **Chapter 0**: 要件の確認
- **Chapter 1**: 環境構築
- **Chapter 2**: データベース設計とマイグレーション
- **Chapter 3**: モデルとリレーション
- **Chapter 4**: FormRequestによるバリデーション（Laravel Fortifyによる認証機能を含む）
- **Chapter 5**: シーダーの実装
- **Chapter 6**: API Resourceによるレスポンス整形
- **Chapter 7**: APIコントローラー（カテゴリー）
- **Chapter 8**: APIコントローラー（お問い合わせCRUD）
- **Chapter 9**: ルーティングの設定

## 応用機能（Chapter 10-14）

- **Chapter 10**: タグ機能のマイグレーション
  - 多対多リレーションの概念
  - tagsテーブルとcontact_tag中間テーブルの設計
  - 外部キー制約とカスケード削除

- **Chapter 11**: タグ機能のモデルとリレーション
  - Tagモデルの作成
  - belongsToManyリレーションの定義
  - withTimestamps()の重要性

- **Chapter 12**: タグAPIの実装
  - リソースコントローラーによるCRUD API
  - FormRequestによるバリデーション分離
  - 更新時のユニーク制約（Rule::unique()->ignore()）
  - API Resourceによるレスポンス整形

- **Chapter 13**: お問い合わせとタグの紐付け
  - データベーストランザクションの必要性
  - attach()メソッドによる多対多の関連付け
  - 配列のバリデーション（tags.*）
  - Eager LoadingとN+1問題の回避

- **Chapter 14**: CSVエクスポート機能の実装
  - ストリームダウンロードによるメモリ効率化
  - BOMによる文字化け対策
  - chunk()メソッドによる大量データ処理
  - 検索ロジックの共通化（DRY原則）

## 変更履歴

### 2026年2月10日
- 重複していたChapter 10（Laravel Fortifyによる認証機能）を削除
  - 理由: Chapter 4で既に同内容が実装済みであったため
- Chapter 11-15をChapter 10-14に繰り上げ
- 最終的な構成: Chapter 0-9（基本機能）+ Chapter 10-14（応用機能）= 全15チャプター
