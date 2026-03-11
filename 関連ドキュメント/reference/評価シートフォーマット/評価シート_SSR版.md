# 評価シート SSR版確認テスト

> スプレッドシートへコピペして使用してください。

| 大項目 | 評価基準 | 評価点 | 可否 | 点数 |
|--------|---------|--------|------|------|
| Laravel単体 | 問い合わせフォーム表示機能において、/ にアクセスした際にフォーム全項目が表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 問い合わせフォーム表示機能において、/ 初期表示時にカテゴリの選択肢がBladeテンプレートでサーバーサイド描画されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 問い合わせフォーム表示機能において、/ 初期表示時にタグの選択肢がBladeテンプレートでサーバーサイド描画されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 入力エラー表示機能において、必須項目を空のまま「確認画面」を押下した際にエラーメッセージが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 確認画面遷移機能において、正しい入力で「確認画面」を押下した際に POST /contacts/confirm で確認ページへ遷移するか。 | 1 | FALSE | 0 |
| Laravel単体 | 確認画面遷移機能において、確認画面にカテゴリ名が文字列で表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 確認画面遷移機能において、確認画面にタグ名が文字列で表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 確認画面遷移機能において、確認画面に住所等の入力値が期待どおり表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 送信成功～完了画面機能において、「送信」押下で POST /contacts が実行されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 送信成功～完了画面機能において、送信成功後にDBへ contacts レコードが作成されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 送信成功～完了画面機能において、タグ選択時にDBへ contact_tag レコードが作成されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 送信成功～完了画面機能において、送信成功後に /thanks へリダイレクトされるか。 | 1 | FALSE | 0 |
| Laravel単体 | 送信成功～完了画面機能において、/thanks に完了メッセージが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | ログイン必須制御機能において、未ログインで /admin にアクセスした際に /login へリダイレクトされるか。 | 1 | FALSE | 0 |
| Laravel単体 | ログイン必須制御機能において、ログイン後に /admin へアクセスできるか。 | 1 | FALSE | 0 |
| Laravel単体 | 検索条件と結果の同期機能において、検索実行時にURLクエリへ検索条件が反映されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 検索条件と結果の同期機能において、検索結果テーブルが指定条件どおりに表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | ページネーション機能において、ページリンク押下で一覧表示が切り替わるか。 | 1 | FALSE | 0 |
| Laravel単体 | ページネーション機能において、ページリンク押下でURLの page パラメータが更新されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 詳細表示機能において、「詳細」押下で GET /admin/contacts/{id} の詳細ページへ遷移するか。 | 1 | FALSE | 0 |
| Laravel単体 | 詳細表示機能において、詳細ページにカテゴリが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 詳細表示機能において、詳細ページにタグが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 詳細表示機能において、詳細ページに住所が表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 削除機能において、削除操作時に確認ダイアログが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 削除機能において、確認後に DELETE /admin/contacts/{id} が実行されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 削除機能において、削除後に /admin へリダイレクトされるか。 | 1 | FALSE | 0 |
| Laravel単体 | 削除機能において、削除後に一覧から該当行が消えるか。 | 1 | FALSE | 0 |
| Laravel単体 | CSVダウンロード機能において、エクスポート実行時に /contacts/export が指定条件で呼ばれるか。 | 1 | FALSE | 0 |
| Laravel単体 | CSVダウンロード機能において、CSVがダウンロードされるか。 | 1 | FALSE | 0 |
| Laravel単体 | CSVダウンロード機能において、ダウンロードしたCSVの列順が仕様どおりか。 | 1 | FALSE | 0 |
| Laravel単体 | CSVダウンロード機能において、ダウンロードしたCSVの値が仕様どおりか。 | 1 | FALSE | 0 |
| Laravel単体 | CSVエクスポート機能において、ダウンロードされたCSVファイルにBOMが付与され、日本語が文字化けしないようになっているか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ登録機能において、タグ名を追加した際に一覧へ即時反映されるか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ更新機能において、編集したタグ名が一覧へ反映されるか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ更新機能において、重複名で更新した際にエラーメッセージが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ更新機能において、重複名で更新した際に更新が適用されないか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ削除機能において、削除したタグが一覧から消えるか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ削除機能において、削除後にDB上で関連（例：contact_tag）に不整合が残らないか。 | 1 | FALSE | 0 |
| Laravel単体 | タグ削除機能において、タグを削除した際に中間テーブル（contact_tag）の関連レコードも自動で削除（CASCADE）されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 管理者登録（Fortify）機能において、/register にアクセスした際に登録画面が表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 管理者登録（Fortify）機能において、有効な情報で登録送信した際にユーザーが作成されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 管理者登録（Fortify）機能において、登録成功後に /admin へ遷移するか。 | 1 | FALSE | 0 |
| Laravel単体 | 管理者登録（Fortify）機能において、必須入力漏れで登録送信した際にエラーメッセージが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | 管理者登録（Fortify）機能において、登録送信後にDB（users）へレコードが追加されるか。 | 1 | FALSE | 0 |
| Laravel単体 | ログイン機能において、正しい資格情報でログインした際に /admin へ遷移するか。 | 1 | FALSE | 0 |
| Laravel単体 | ログイン機能において、誤った資格情報でログインした際にエラーメッセージが表示されるか。 | 1 | FALSE | 0 |
| Laravel単体 | ログアウト機能において、ログアウト操作でセッションが破棄されるか。 | 1 | FALSE | 0 |
| Laravel単体 | ログアウト機能において、ログアウト後に /admin へアクセスできないか。 | 1 | FALSE | 0 |
| マイグレーション | usersテーブルにおいて、id がBIGINT AUTO_INCREMENT + PRIMARY KEY になっているか。 | 1 | FALSE | 0 |
| マイグレーション | usersテーブルにおいて、name / email / password が VARCHAR(255) で、email に UNIQUE 制約があるか。 | 1 | FALSE | 0 |
| マイグレーション | usersテーブルにおいて、email_verified_at が NULL許容の timestamp になっているか。 | 1 | FALSE | 0 |
| マイグレーション | usersテーブルにおいて、remember_token や created_at / updated_at が存在するか。 | 1 | FALSE | 0 |
| マイグレーション | usersテーブルにおいて、マイグレーション (2014_10_12_000000_create_users_table.php) と実DB構造が一致しているか。 | 1 | FALSE | 0 |
| マイグレーション | categoriesテーブルにおいて、id の定義と PRIMARY KEY が正しいか。 | 1 | FALSE | 0 |
| マイグレーション | categoriesテーブルにおいて、content が VARCHAR(255) で NOT NULL になっているか。 | 1 | FALSE | 0 |
| マイグレーション | categoriesテーブルにおいて、timestamps が存在するか。 | 1 | FALSE | 0 |
| マイグレーション | categoriesテーブルにおいて、マイグレーション (2026_02_10_040042_create_categories_table.php) と実DBが一致しているか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、category_id が外部キー (categories.id) で ON DELETE CASCADE になっているか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、first_name / last_name / email / address 等が VARCHAR(255) で NOT NULL になっているか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、gender が TINYINT でコメント通り 1/2/3 を想定しているか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、tel が VARCHAR(11) (10〜11桁) であるか、detail が VARCHAR(120) であるか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、timestamps が存在し、仕様通り detail が120文字に制限されているか。 | 1 | FALSE | 0 |
| マイグレーション | contactsテーブルにおいて、実DBの SHOW CREATE TABLE contacts で外部キーや制約がマイグレーションと一致しているか。 | 1 | FALSE | 0 |
| マイグレーション | tagsテーブルにおいて、name が VARCHAR(50) で UNIQUE 制約があるか。 | 1 | FALSE | 0 |
| マイグレーション | tagsテーブルにおいて、timestamps が存在するか。 | 1 | FALSE | 0 |
| マイグレーション | tagsテーブルにおいて、マイグレーション (2026_02_10_042329_create_tags_table.php) と一致しているか。 | 1 | FALSE | 0 |
| マイグレーション | contact_tagテーブル（中間テーブル）において、contact_id と tag_id が BIGINT で、それぞれ contacts / tags を参照し ON DELETE CASCADE になっているか。 | 1 | FALSE | 0 |
| マイグレーション | contact_tagテーブル（中間テーブル）において、unique(['contact_id','tag_id']) が設定され、同じ組み合わせが重複登録されないようになっているか。 | 1 | FALSE | 0 |
| マイグレーション | contact_tagテーブル（中間テーブル）において、timestamps があるか。 | 1 | FALSE | 0 |
| マイグレーション | contact_tagテーブル（中間テーブル）において、実DBとマイグレーション (2026_02_10_042338_create_contact_tag_table.php) が一致しているか。 | 1 | FALSE | 0 |
| マイグレーション | すべての FK (contacts.category_id, contact_tag.contact_id, contact_tag.tag_id) が想定どおり設定されているか。 | 1 | FALSE | 0 |
| マイグレーション | 実際にカテゴリやタグを削除した際、関連レコードが自動で削除される（cascadeが有効）かを確認。 | 1 | FALSE | 0 |
| マイグレーション | email (users) や name (tags) など UNIQUE 制約が必要な列に適切な index が付いているか。 | 1 | FALSE | 0 |
| マイグレーション | 検索で使用する列（contacts の category_id や created_at など）に必要なインデックスを張っているか（設計方針によるが、評価対象にすると良い）。 | 1 | FALSE | 0 |
| マイグレーション | テーブル仕様書内の型・制約が実DBと合致しているか。 | 1 | FALSE | 0 |
| バリデーション | お問い合わせフォーム表示画面にて、以下のルールでフォームリクエストを用いたバリデーションができていること first_name, last_name: 必須 / string / max:255、gender: 必須 / integer / in:1,2,3、email: 必須 / string / email / max:255、tel: 必須 / string / regex:/^[0-9]{10,11}$/、address: 必須 / string / max:255、building: nullable / string / max:255、category_id: 必須 / integer / exists:categories,id、detail: 必須 / string / max:120 | 1 | FALSE | 0 |
| バリデーション | 入力内容確認画面にて、POST /contacts/confirm でも同一の StoreContactRequest によるバリデーションが適用されていること | 1 | FALSE | 0 |
| バリデーション | お問い合わせ送信画面にて、POST /contacts でも同一の StoreContactRequest によるバリデーションが適用されていること | 1 | FALSE | 0 |
| バリデーション | お問い合わせ送信（タグ・検索条件）にて、以下のルールでバリデーションができていること tag_ids[]: array / integer / exists:tags,id、keyword: nullable / string / max:255、gender: nullable / integer / in:0,1,2,3、category_id: nullable / integer / exists:categories,id、date: nullable / date | 1 | FALSE | 0 |
| バリデーション | タグマスタ管理画面にて、以下のルールでフォームリクエストを用いたバリデーションができていること name: 必須 / string / max:50 / unique | 1 | FALSE | 0 |
| バリデーション | CSVエクスポートにて、以下のルールでフォームリクエストを用いたバリデーションができていること keyword: nullable / string / max:255、gender: nullable / integer / in:0,1,2,3、category_id: nullable / integer / exists:categories,id、date: nullable / date | 1 | FALSE | 0 |
| バリデーション | 管理ユーザー登録画面にて、以下のルールでバリデーションができていること name: 必須 / string / max:255、email: 必須 / email / max:255 / unique、password: Fortify標準（8文字以上・確認用一致） | 1 | FALSE | 0 |
| バリデーション | ログイン画面にて、以下のルールでバリデーションができていること email: 必須 / email / max:255、password: 必須 | 1 | FALSE | 0 |
| バリデーション | お問い合わせ一覧APIにて、以下のルールでフォームリクエストを用いたバリデーションができていること keyword: nullable / string / max:255、gender: nullable / integer / in:1,2,3、category_id: nullable / integer / exists:categories,id、date: nullable / date、per_page: nullable / integer / min:1 / max:100 | 1 | FALSE | 0 |
| バリデーション | お問い合わせ作成・更新APIにて、Web版と同一ルール（first_name〜detail + tag_ids[]）でフォームリクエストを用いたバリデーションができていること | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、正常系でリクエストを送信すると HTTP 200 が返り、JSON の data 配列と meta 情報（current_page, last_page, per_page, total）が含まれるか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、keyword 検索パラメータで正しくフィルタリングされるか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、gender フィルタで正しくフィルタリングされるか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、category_id フィルタで正しくフィルタリングされるか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、date フィルタで正しくフィルタリングされるか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、gender=9 のような不正値で 422 が返り、バリデーションエラーが返るか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts にて、十分な件数を用意し per_page や page パラメータでページング結果が正しいか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts/{id} にて、存在する ID を指定して 200 が返り、data 内にカテゴリ・タグ付きの ContactResource が返るか確認。 | 1 | FALSE | 0 |
| API | GET /api/v1/contacts/{id} にて、存在しない ID を指定すると 404 とエラーメッセージ JSON が返るか確認。 | 1 | FALSE | 0 |
| API | POST /api/v1/contacts にて、正常 payload でリクエストし 201 が返り、contacts と contact_tag にレコードが追加されるか確認。 | 1 | FALSE | 0 |
| API | POST /api/v1/contacts にて、tag_ids を省略または空配列で送信しても 201 が返り、contact_tag にレコードが入らないことを確認。 | 1 | FALSE | 0 |
| API | POST /api/v1/contacts にて、不正データ（tel 9桁等）で送信すると 422 とバリデーションエラーが返るか確認。 | 1 | FALSE | 0 |
| API | PUT /api/v1/contacts/{id} にて、正常 payload で 200 が返り、更新内容がレスポンスに反映されるか確認。 | 1 | FALSE | 0 |
| API | PUT /api/v1/contacts/{id} にて、存在しない ID を指定すると 404 が返るか確認。 | 1 | FALSE | 0 |
| API | PUT /api/v1/contacts/{id} にて、不正データで送信すると 422 とバリデーションエラーが返るか確認。 | 1 | FALSE | 0 |
| API | DELETE /api/v1/contacts/{id} にて、既存 ID でリクエストして 204 が返り、contacts の該当レコードが削除されるか確認。 | 1 | FALSE | 0 |
| API | DELETE /api/v1/contacts/{id} にて、存在しない ID を指定して 404 が返るか確認。 | 1 | FALSE | 0 |
| API | API用コントローラーがWeb用と分離されているか（Api\V1名前空間）。 | 1 | FALSE | 0 |
| API | API Resources（ContactResource）によるJSON整形が使用されているか。 | 1 | FALSE | 0 |
| API | API用のFormRequest（Api\V1名前空間）でバリデーションが実装されているか。 | 1 | FALSE | 0 |
| シーディング | 要件の指示に従って、Userテーブルのダミーデータとして要件で指示されたユーザーを作成することができているか | 1 | FALSE | 0 |
| シーディング | 要件の指示に従って、categoryテーブルのダミーデータ 5件を作成できているか | 1 | FALSE | 0 |
| シーディング | 要件の指示に従って、Tagテーブルのダミーデータを5件作成できているか | 1 | FALSE | 0 |
| シーディング | 要件の指示に従って、contactsテーブルのダミーデータ 20件を作成できるか | 1 | FALSE | 0 |
| シーディング | ContactSeederにて、各Contactに対して既存タグからランダムに1〜3件を attach() で紐付け、contact_tag 中間テーブルにレコードが作成されているか | 1 | FALSE | 0 |
| マイグレーション | テーブル仕様に従った contactsテーブルのマイグレーションファイルを作成できているか | 1 | FALSE | 0 |
| マイグレーション | テーブル仕様に従った categoriesテーブルのマイグレーションファイルを作成できているか | 1 | FALSE | 0 |
| マイグレーション | テーブル仕様に従った usersテーブルのマイグレーションファイルを作成できているか | 1 | FALSE | 0 |
| マイグレーション | テーブル仕様に従った tagsテーブルのマイグレーションファイルを作成できているか | 1 | FALSE | 0 |
| マイグレーション | php artisan migrate を実行し 各テーブルが作成できるか | 1 | FALSE | 0 |
| テスト | CSVエクスポートのバリデーション要件において、正しいフィルタ条件を受け付け、不正な性別や存在しないカテゴリIDを拒否することを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ一覧検索のバリデーションにおいて、キーワード・性別・カテゴリ・日付フィルタが有効であり、不正な性別値を拒否することを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ保存のバリデーションにおいて、全ての必須項目とタグ入力を受け付け、不正な電話番号形式を拒否することを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | タグ新規登録のバリデーションにおいて、タグ名の必須入力、文字数制限、一意性（重複禁止）が維持されていることを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | タグ更新のバリデーションにおいて、自身の名前維持は可能だが、他で使用されているタグ名への変更を拒否することを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | カテゴリモデルのリレーション（カテゴリ関係）において、1つのカテゴリから紐づく複数のお問い合わせ（hasMany）が正しく取得できることを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせモデルのリレーション（お問い合わせ関係）において、1つのお問い合わせが特定のカテゴリに属し、複数のタグと同期（sync）できることを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | タグモデルのリレーション（タグ関係）において、中間テーブルを介して1つのタグが複数のお問い合わせに紐づいていることを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ確認画面（ContactControllerTest）において、正しい入力で POST /contacts/confirm を送信すると確認ページが表示され、バリデーションエラー時にはリダイレクトされることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ送信（ContactControllerTest）において、POST /contacts でDB保存・タグ同期・/thanks リダイレクトが行われ、バリデーションエラー時にはリダイレクトされることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | 管理画面のアクセス制御（AdminControllerTest）において、認証ユーザーのみが /admin を表示でき、未認証時は /login へリダイレクトされることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | 管理画面の検索・ページネーション（AdminControllerTest）において、キーワード・性別・カテゴリ・日付のフィルタが機能し、ページネーションで7件ずつ表示されることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ詳細表示（AdminControllerTest）において、GET /admin/contacts/{id} で詳細ページにカテゴリ付きの連絡先情報が表示されることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ削除（AdminControllerTest）において、DELETE /admin/contacts/{id} でレコードが削除され /admin へリダイレクトされることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | タグ管理（TagControllerTest）において、認証ユーザーによるタグの作成・更新・削除が /admin へリダイレクト付きで行え、未認証時は /login へリダイレクトされることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | ユーザー向け画面表示（ContactPageTest）において、/ でカテゴリ・タグがサーバーサイド描画され、/thanks が正常に表示されることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | CSVダウンロードのエクスポート機能において、ログイン済み管理者がフィルタ条件付きでCSVをDLでき、無指定時は新着順で出力されることを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | API検索バリデーション（Api\V1\IndexContactRequest）において、有効なフィルタの受付と不正な性別値(0含む)・per_page超過を拒否することを検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | API作成バリデーション（Api\V1\StoreContactRequest）において、全必須項目・タグの受付と不正な値の拒否を検証する Unit Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ一覧API（GET /api/v1/contacts）において、JSON一覧取得・検索・ページネーション・バリデーションエラーを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ詳細API（GET /api/v1/contacts/{id}）において、詳細取得と404エラーを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ作成API（POST /api/v1/contacts）において、201レスポンス・DB保存・バリデーションエラーを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ更新API（PUT /api/v1/contacts/{id}）において、200レスポンス・更新反映・404エラーを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| テスト | お問い合わせ削除API（DELETE /api/v1/contacts/{id}）において、204レスポンス・レコード削除・404エラーを検証する Feature Tests が実装されパスしているか。 | 1 | FALSE | 0 |
| コード品質 | お問い合わせ一覧取得において、Eager Loading（withメソッド等）を使用して、カテゴリ取得によるN+1問題が防止されているか。 | 1 | FALSE | 0 |
| コード品質 | 変数名などに、a,xなどの意味のない命名をしない | 1 | FALSE | 0 |
| コード品質 | 変数名などに、ローマ字などの英単語ではないもので命名をしない | 1 | FALSE | 0 |
| コード品質 | モデル名に「アッパーキャメル」を使用できているか | 1 | FALSE | 0 |
| コード品質 | コントローラ名に「アッパーキャメル」を使用できているか | 1 | FALSE | 0 |
| コード品質 | フォームリクエスト名に「アッパーキャメル」を使用できているか | 1 | FALSE | 0 |
| コード品質 | マイグレーションファイル名に「スネークケース」を使用できているか | 1 | FALSE | 0 |
| コード品質 | シーディングファイル名に「アッパーキャメル」を使用できているか | 1 | FALSE | 0 |
| コード品質 | バリデーションは、全てフォームリクエストを使用して実装できているか（認証を除く） | 1 | FALSE | 0 |
| コード品質 | DB操作に関するプログラムは、Eloquent ORMでの記述で統一されているか（コントローラーのみ） | 1 | FALSE | 0 |
| コード品質 | 使用していないクラスやファイルをuseで読み込んでいないか | 1 | FALSE | 0 |
| コード品質 | 必要のないコメントアウトが残っていないか | 1 | FALSE | 0 |
| コード品質 | テーブル名はスネークケース、複数形で命名できているか | 1 | FALSE | 0 |
| コード品質 | カラム名はスネークケースを使用しているか | 1 | FALSE | 0 |
| コード品質 | ER図のカーディナリティの記述に不適切な箇所がないか | 1 | FALSE | 0 |
| コード品質 | インデントや改行が整理できているか | 1 | FALSE | 0 |
| コード品質 | Laravel Pintを用いた整形がされているか（検証: `sail bin pint --test` を実行し「No fixable issues were found」と表示されること） | 1 | FALSE | 0 |
| ドキュメント | README.mdに必要な情報を記載できているか | 2 | FALSE | 0 |
| ドキュメント | README.md に記載されている環境構築方法で環境構築できるか | 2 | FALSE | 0 |

---

## 集計

| 大項目 | 項目数 | 配点合計 |
|--------|--------|---------|
| Laravel単体 | 48 | 48 |
| マイグレーション | 32 | 32 |
| バリデーション | 10 | 10 |
| API | 20 | 20 |
| シーディング | 5 | 5 |
| テスト | 24 | 24 |
| コード品質 | 17 | 17 |
| ドキュメント | 2 | 4 |
| **合計** | **158** | **160** |

## 旧版（API版）からの変更サマリ

| 変更種別 | 内容 |
|---------|------|
| 変更 | API大項目: 旧版の /api/* 21項目を削除し、SSR版の /api/v1/contacts 向け20項目として再構成 |
| 削除 | テスト: API Resource テスト 3項目（CategoryResource, ContactResource, TagResource） |
| 変更 | テスト: API Feature テスト → /api/v1/contacts の CRUD + バリデーション 5項目に再構成 |
| 変更 | Laravel単体: 確認画面 → JS切り替えから POST /contacts/confirm ページ遷移へ |
| 変更 | Laravel単体: 送信 → POST /api/contacts (201) から POST /contacts (redirect) へ |
| 変更 | Laravel単体: 詳細 → モーダル表示から GET /admin/contacts/{id} ページ遷移へ |
| 変更 | Laravel単体: 削除 → DELETE /api/contacts/{id} (204) から DELETE /admin/contacts/{id} (redirect) へ |
| 追加 | テスト: ContactControllerTest (confirm + store の正常系・異常系 4テスト) |
| 追加 | テスト: AdminControllerTest (認証・検索・ページネーション・詳細・削除 6テスト) |
| 追加 | テスト: TagControllerTest (CRUD + 認証 4テスト) |
| 追加 | テスト: ContactPageTest (トップページ描画・タグカテゴリ描画・thanks 3テスト) |
