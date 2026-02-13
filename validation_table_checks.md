# バリデーション評価

1. **お問い合わせ送信のバリデーション整合性**
   - `app/Http/Requests/StoreContactRequest.php` を開き、ルールが仕様（姓/名: string max255、tel: 正規表現10-11桁、detail: max120など）と一致するかをコード上で確認する。
   - テスト用のHTTPリクエスト（FeatureテストやPostman）で各ルールに反する値を送信し、422と適切な `errors` が返るかをチェックする。

...（続く）
