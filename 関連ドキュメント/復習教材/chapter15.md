# Chapter 15: 鉄壁の品質保証 - Laravelの自動テストをマスターする 🛡️

## 1. はじめに 📖

おめでとうございます！ついに最終チャプターです。このチャプターでは、これまで作り上げてきた「お問い合わせ管理システム」の品質を保証するための「自動テスト」を実装します。自動テストは、一度書けば何度でも同じ検証を瞬時に実行してくれる、品質保証の強力な武器です。デグレード（機能改修によって既存の機能が壊れること）を防ぎ、自信を持ってアプリケーションをリリースするために不可欠なスキルです。

このチャプターを終える頃には、あなたはLaravelのテスト機能を使いこなし、堅牢なアプリケーションを構築する術を身につけているでしょう。

## 2. テストの全体像 🗺️

Laravelのテストは、大きく分けて2種類あります。

- **単体テスト (Unit Tests)**: モデルやリクエストクラスなど、アプリケーションの比較的小さな「部品（ユニット）」が、それぞれ単体で正しく動作するかを検証します。
- **機能テスト (Feature Tests)**: 複数の部品が連携して、一つの「機能」として正しく動作するかを検証します。実際にHTTPリクエストを送信し、ユーザーの操作をシミュレートします。

このチャプターでは、両方のテストをバランス良く実装していきます。

## 3. テストの準備 🛠️

テストを実行する前に、テスト専用のデータベース設定を行います。これにより、開発用のデータベースを汚すことなく、安全にテストを実行できます。

プロジェクトのルートにある`phpunit.xml`ファイルを開き、`<php>`セクション内の`DB_CONNECTION`と`DB_DATABASE`の行を変更してください。

**phpunit.xml（`<php>`セクション内の変更箇所）**
```xml
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>  <!-- 追加 -->
        <env name="DB_DATABASE" value=":memory:"/>  <!-- 変更: "testing" → ":memory:" -->
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
```

> **変更のポイント**: デフォルトでは `DB_DATABASE` が `"testing"` になっていますが、これを `":memory:"` に変更します。また、`DB_CONNECTION` の行はデフォルトでは存在しないため、新規に追加してください。

### コード解説
- `<env name="APP_ENV" value="testing"/>`: Laravelに、現在の環境がテスト環境であることを伝えます。
- `<env name="DB_CONNECTION" value="sqlite"/>`: テストに使用するデータベースの種類をSQLiteに指定します。
- `<env name="DB_DATABASE" value=":memory:"/>`: インメモリデータベースを使用します。ファイルではなくメモリ上に一時的にデータベースを構築するため、ディスクI/Oが発生せずテストが非常に高速に実行できます。テスト終了後はデータが自動的に破棄されるため、開発用データベースを汚す心配もありません。

> **💡 インメモリデータベースとは？**
>
> `DB_CONNECTION` を `sqlite` に、`DB_DATABASE` を `:memory:` に設定することで、テスト実行時にインメモリデータベースが使用されます。これは、実際のファイルではなく、コンピュータのメモリ上に一時的にデータベースを構築する方式です。テスト用のMySQLデータベースを別途作成する必要がなく、セットアップが簡単です。

### デフォルトのExampleTestの削除

Laravelはプロジェクト作成時に `tests/Unit/ExampleTest.php` と `tests/Feature/ExampleTest.php` というサンプルテストファイルを自動生成します。これらはあくまでテスト環境の動作確認用のスキャフォールドであり、プロジェクト固有のテストを作成する段階では不要です。以下のコマンドで削除してください。

```bash
rm tests/Unit/ExampleTest.php tests/Feature/ExampleTest.php
```

## 4. Factoryの作成 🏭

テストを実行するには、テストデータが必要です。Factoryは、モデルに対応するダミーデータを簡単に生成するための仕組みです。

### 4.1 CategoryFactory

```bash
sail artisan make:factory CategoryFactory --model=Category
```

作成された`database/factories/CategoryFactory.php`を以下のように編集します。

**database/factories/CategoryFactory.php**
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->words(3, true),
        ];
    }
}
```

### コード解説
- `definition()`: このFactoryを使ってモデルが作成される際の、デフォルトのデータ構造を定義します。
- `$this->faker->words(3, true)`: PHPのダミーデータ生成ライブラリ「Faker」を使って、ランダムな3つの単語を文字列として生成します。

### 4.2 ContactFactory

```bash
sail artisan make:factory ContactFactory --model=Contact
```

作成された`database/factories/ContactFactory.php`を以下のように編集します。

**database/factories/ContactFactory.php**
```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => $this->faker->lastName(),
            'last_name' => $this->faker->firstName(),
            'gender' => $this->faker->numberBetween(1, 3),
            'email' => $this->faker->unique()->safeEmail(),
            'tel' => $this->faker->numerify('0##########'),
            'address' => $this->faker->streetAddress(),
            'building' => $this->faker->optional()->secondaryAddress(),
            'detail' => $this->faker->text(60),
        ];
    }
}
```

### コード解説
- `'category_id' => Category::factory()`: `Contact`モデルは`Category`モデルに属しているため、`Contact`を作成する際に、関連する`Category`も自動で作成するように定義しています。
- `$this->faker->lastName()`: Fakerを使って、リアルな「姓」を生成します。このプロジェクトでは`first_name`カラムに姓を格納するため、Fakerの`lastName`を使います。同様に`last_name`カラムには`firstName`で「名」を格納します。
- `$this->faker->numerify('0##########')`: ` #` をランダムな数字（0-9）に置き換えます。先頭の`0`を固定し、残り10桁をランダムにして11桁の電話番号を生成しています。
- `$this->faker->optional()->secondaryAddress()`: 50%の確率で`null`を、そうでなければ建物の部屋番号などを生成します。`building`カラムが`nullable`な場合に対応できます。

### 4.3 TagFactory

```bash
sail artisan make:factory TagFactory --model=Tag
```

作成された`database/factories/TagFactory.php`を以下のように編集します。

**database/factories/TagFactory.php**
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
```

### コード解説
- `$this->faker->unique()->words(2, true)`: ランダムな2つの単語を文字列として生成します。`unique()`を付けることで、複数のタグを生成した際に名前が重複しないようにしています。

## 5. 単体テスト (Unit Tests) の作成 🔬

まずは、アプリケーションの最小単位である「モデル」「リクエスト」が正しく動作するかを検証する単体テストから作成します。

### 5.1 Models

モデルのテストでは、主にリレーションシップが正しく定義されているかを確認します。

#### 5.1.1 Categoryモデルのテスト

```bash
sail artisan make:test Models/CategoryTest --unit
```

作成された`tests/Unit/Models/CategoryTest.php`を以下のように編集します。

**tests/Unit/Models/CategoryTest.php**
```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_contacts(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(2)->for($category)->create();

        $this->assertCount(2, $category->fresh()->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }
}
```

#### コード解説
- `use RefreshDatabase;`: このトレイトを使用すると、各テストメソッドの実行前にデータベースがマイグレーションされ、実行後にロールバックされます。これにより、他のテストの影響を受けないクリーンな状態でテストを実行できます。
- `test_category_has_many_contacts()`: `Category`モデルが`contacts`リレーション（一対多）を正しく持っているかをテストします。
- `$category = Category::factory()->create();`: テスト対象のカテゴリを1つ作成します。
- `Contact::factory()->count(2)->for($category)->create();`: 作成したカテゴリに属するお問い合わせを2つ作成します。
- `$this->assertCount(2, $category->fresh()->contacts);`: `fresh()`でデータベースからモデルを再取得し、`contacts`リレーション経由で取得したお問い合わせのコレクションの件数が2件であることをアサート（断言）します。
- `$this->assertInstanceOf(Contact::class, $category->contacts->first());`: コレクションの最初の要素が`Contact`クラスのインスタンスであることをアサートし、リレーションが正しいモデルを返していることを確認します。

#### 5.1.2 Contactモデルのテスト

```bash
sail artisan make:test Models/ContactTest --unit
```

作成された`tests/Unit/Models/ContactTest.php`を以下のように編集します。

**tests/Unit/Models/ContactTest.php**
```php
<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->for($category)->create();

        $this->assertTrue($contact->category->is($category));
    }

    public function test_contact_belongs_to_many_tags(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $contact->tags()->attach($tags->pluck('id'));

        $contact->load('tags');

        $this->assertCount(2, $contact->tags);
        $this->assertTrue($contact->tags->pluck('id')->contains($tags->first()->id));
    }
}
```

#### コード解説
- `test_contact_belongs_to_category()`: `Contact`モデルが`category`リレーション（多対一）を正しく持っているかをテストします。
- `$this->assertTrue($contact->category->is($category));`: 2つのモデルインスタンスが同じ（同じ主キーを持つ同じテーブルのレコード）であるかをアサートします。
- `test_contact_belongs_to_many_tags()`: `Contact`モデルが`tags`リレーション（多対多）を正しく持っているかをテストします。
- `$contact->tags()->attach($tags->pluck('id'));`: `Contact`に複数の`Tag`のIDを紐付けます。`pluck('id')`でIDの配列を取得しています。
- `$contact->load('tags');`: リレーションを明示的に再読み込みし、最新の状態を取得します。
- `$this->assertTrue($contact->tags->pluck('id')->contains($tags->first()->id));`: タグのIDコレクションに、紐付けたタグのIDが含まれていることを確認します。

#### 5.1.3 Tagモデルのテスト

```bash
sail artisan make:test Models/TagTest --unit
```

作成された`tests/Unit/Models/TagTest.php`を以下のように編集します。

**tests/Unit/Models/TagTest.php**
```php
<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();

        $tag->contacts()->attach($contacts->pluck('id')->toArray());

        $tag->load('contacts');

        $this->assertCount(2, $tag->contacts);
        $this->assertTrue($tag->contacts->pluck('id')->contains($contacts->first()->id));
    }
}
```

#### コード解説
- `test_tag_belongs_to_many_contacts()`: `Tag`モデルが`contacts`リレーション（多対多）を正しく持っているかをテストします。

### 5.2 Requests

リクエストクラスのテストでは、バリデーションルールが意図通りに機能するかを確認します。

#### 5.2.1 StoreContactRequestのテスト

```bash
sail artisan make:test Requests/StoreContactRequestTest --unit
```

作成された`tests/Unit/Requests/StoreContactRequestTest.php`を以下のように編集します。

**tests/Unit/Requests/StoreContactRequestTest.php**
```php
<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new StoreContactRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function basePayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Hanako',
            'last_name' => 'Sato',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '0312345678',
            'address' => 'Tokyo',
            'building' => 'Skytree',
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ',
        ], $overrides);
    }

    public function test_rules_accept_valid_payload_with_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validator = $this->validator($this->basePayload($category, [
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]));

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_invalid_phone_number(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validator($this->basePayload($category, [
            'tel' => '123-456',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->messages());
    }
}
```

#### コード解説
- `validator()`: テスト対象の`StoreContactRequest`のインスタンスを作成し、その`rules()`メソッドを使ってバリデータを作成するヘルパーメソッドです。
- `basePayload()`: テストの基本となる正常なデータ（ペイロード）を生成するヘルパーメソッドです。`$overrides`で一部のデータを上書きできます。
- `test_rules_accept_valid_payload_with_tags()`: 正常なデータがバリデーションを通過すること（`passes()`）をテストします。
- `test_rules_reject_invalid_phone_number()`: 不正な電話番号のデータがバリデーションに失敗すること（`fails()`）と、`tel`フィールドにエラーメッセージが存在すること（`assertArrayHasKey()`）をテストします。

#### 5.2.2 IndexContactRequestのテスト

```bash
sail artisan make:test Requests/IndexContactRequestTest --unit
```

作成された`tests/Unit/Requests/IndexContactRequestTest.php`を以下のように編集します。

**tests/Unit/Requests/IndexContactRequestTest.php**
```php
<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new IndexContactRequest();
        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_rules_accept_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validator([
            'keyword' => 'Yamada',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2024-02-01',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_invalid_gender(): void
    {
        $validator = $this->validator([
            'gender' => 9,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->messages());
    }
}
```

#### コード解説
- `test_rules_accept_valid_filters()`: 検索条件として有効なデータがバリデーションを通過することをテストします。
- `test_rules_reject_invalid_gender()`: 不正な性別値（`9`）がバリデーションに失敗し、`gender`フィールドにエラーが発生することをテストします。`IndexContactRequest`の`gender`ルールには`in:1,2,3`が含まれているため、範囲外の値は拒否されます。

#### 5.2.3 ExportContactRequestのテスト

```bash
sail artisan make:test ExportContactRequestTest --unit
```

作成された`tests/Unit/ExportContactRequestTest.php`を以下のように編集します。

**tests/Unit/ExportContactRequestTest.php**
```php
<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeValidator(array $data)
    {
        $request = new ExportContactRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_rules_accept_valid_payload(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'keyword' => 'delivery',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2024-02-01',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_gender_rule_rejects_invalid_value(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'gender' => 5,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->messages());
    }

    public function test_category_rule_requires_existing_identifier(): void
    {
        Category::factory()->create();

        $validator = $this->makeValidator([
            'category_id' => 999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->messages());
    }
}
```

#### コード解説
- `test_gender_rule_rejects_invalid_value()`: `gender`に不正な値（`in:1,2,3`に含まれない値）が指定された場合にバリデーションが失敗することをテストします。
- `test_category_rule_requires_existing_identifier()`: `category_id`に存在しないIDが指定された場合にバリデーションが失敗すること（`exists:categories,id`）をテストします。

#### 5.2.4 StoreTagRequestのテスト

```bash
sail artisan make:test Requests/StoreTagRequestTest --unit
```

作成された`tests/Unit/Requests/StoreTagRequestTest.php`を以下のように編集します。

**tests/Unit/Requests/StoreTagRequestTest.php**
```php
<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new StoreTagRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_rules_accept_valid_name(): void
    {
        $validator = $this->validator(['name' => 'new-tag']);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_empty_name(): void
    {
        $validator = $this->validator(['name' => '']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_rules_reject_name_exceeding_max_length(): void
    {
        $validator = $this->validator(['name' => str_repeat('a', 51)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_rules_reject_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'duplicate']);

        $validator = $this->validator(['name' => 'duplicate']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }
}
```

#### コード解説
- `test_rules_reject_empty_name()`: `name`が空の場合にバリデーションが失敗すること（`required`）をテストします。
- `test_rules_reject_name_exceeding_max_length()`: `name`が50文字を超える場合にバリデーションが失敗すること（`max:50`）をテストします。
- `test_rules_reject_duplicate_name()`: `name`に既に存在するタグ名が指定された場合にバリデーションが失敗すること（`unique:tags,name`）をテストします。

#### 5.2.5 UpdateTagRequestのテスト

```bash
sail artisan make:test Requests/UpdateTagRequestTest --unit
```

作成された`tests/Unit/Requests/UpdateTagRequestTest.php`を以下のように編集します。

**tests/Unit/Requests/UpdateTagRequestTest.php**
```php
<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_allow_current_name_but_reject_duplicates(): void
    {
        $existing = Tag::factory()->create(['name' => 'existing']);
        $target = Tag::factory()->create(['name' => 'current']);

        $request = new class($target) extends UpdateTagRequest
        {
            public function __construct(private Tag $boundTag)
            {
            }

            public function route($param = null, $default = null)
            {
                if ($param === 'tag') {
                    return $this->boundTag;
                }

                return $default;
            }
        };

        $currentValidator = Validator::make(['name' => 'current'], $request->rules());
        $this->assertTrue($currentValidator->passes());

        $duplicateValidator = Validator::make(['name' => 'existing'], $request->rules());
        $this->assertTrue($duplicateValidator->fails());
        $this->assertArrayHasKey('name', $duplicateValidator->errors()->messages());
    }
}
```

#### コード解説
- このテストは少し複雑です。`UpdateTagRequest`の`unique`ルールは`unique:tags,name,{tag}`のように、更新対象のIDを除外する必要があります。これを単体テストで再現するために、無名クラスを使って`UpdateTagRequest`を拡張し、`route()`メソッドをオーバーライドして、テスト対象の`$target`モデルを注入しています。
- `$currentValidator`: 更新対象自身の名前（`current`）を指定した場合は、バリデーションを通過することをテストします。
- `$duplicateValidator`: 別の既存タグの名前（`existing`）を指定した場合は、バリデーションに失敗することをテストします。

## 6. 機能テスト (Feature Tests) の作成 🚀

いよいよ、ユーザーの操作を模した機能テストを実装していきます。ここでは、実際にHTTPリクエストを送信し、返ってきたレスポンスやデータベースの状態を検証します。

### 6.1 Webページ関連

#### 6.1.1 お問い合わせページのテスト

```bash
sail artisan make:test ContactPageTest
```

作成された`tests/Feature/ContactPageTest.php`を以下のように編集します。

**tests/Feature/ContactPageTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_index_page_is_accessible(): void
    {
        $response = $this->get("/");

        $response->assertOk();
        $response->assertViewIs("contact.index");
        $response->assertViewHas("categories");
        $response->assertViewHas("tags");
    }

    public function test_contact_index_page_displays_categories_and_tags(): void
    {
        $category = Category::factory()->create(["content" => "Delivery"]);
        $tag = Tag::factory()->create(["name" => "urgent"]);

        $response = $this->get("/");

        $response->assertOk();
        $response->assertSee("Delivery");
        $response->assertSee("urgent");
    }

    public function test_contact_thanks_page_is_accessible(): void
    {
        $response = $this->get("/thanks");

        $response->assertOk();
        $response->assertViewIs("contact.thanks");
    }
}
```

#### コード解説
- `$this->get("/")`: アプリケーションのルートURL（`/`）に対してGETリクエストを送信します。
- `$response->assertOk()`: レスポンスのHTTPステータスコードが200 OKであることをアサートします。
- `$response->assertViewIs("contact.index")`: レスポンスとして`contact.index`ビューが返されたことをアサートします。
- `$response->assertViewHas("categories")`: ビューに`categories`という変数が渡されていることをアサートします。コントローラーからビューへのデータの受け渡しが正しく行われているかを確認できます。
- `$response->assertViewHas("tags")`: 同様に、`tags`変数がビューに渡されていることをアサートします。
- `test_contact_index_page_displays_categories_and_tags()`: 実際にカテゴリとタグを作成し、ページのHTMLにそれらの名前が含まれていることを`assertSee`で確認します。これにより、データがビューに渡されるだけでなく、実際に画面に表示されることを保証します。
- `test_contact_thanks_page_is_accessible()`: サンクスページ(`/thanks`)が正常に表示されることをテストします。

#### 6.1.2 CSVエクスポート機能のテスト

```bash
sail artisan make:test ContactExportTest
```

作成された`tests/Feature/ContactExportTest.php`を以下のように編集します。

**tests/Feature/ContactExportTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_export_filtered_contacts(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create(["content" => "Delivery"]);
        $categoryB = Category::factory()->create(["content" => "Exchange"]);

        Contact::factory()->for($categoryA)->create([
            "first_name" => "John",
            "last_name" => "Smith",
            "gender" => 1,
            "email" => "john@example.com",
            "created_at" => Carbon::parse("2024-02-10 10:00:00"),
        ]);

        Contact::factory()->for($categoryB)->create([
            "first_name" => "Alice",
            "last_name" => "Jones",
            "gender" => 2,
            "email" => "alice@example.com",
            "created_at" => Carbon::parse("2024-02-11 10:00:00"),
        ]);

        $response = $this->actingAs($user)->get("/contacts/export?keyword=Smith&gender=1&category_id=" . $categoryA->id . "&date=2024-02-10");

        $response->assertOk();
        $response->assertHeader("Content-Type", "text/csv; charset=UTF-8");

        $content = $response->streamedContent();

        $this->assertStringContainsString("Smith John", $content);
        $this->assertStringContainsString($categoryA->content, $content);
        $this->assertStringNotContainsString("Jones Alice", $content);
    }

    public function test_export_without_filters_returns_all_contacts_in_latest_order(): void
    {
        $user = User::factory()->create();

        $older = Contact::factory()->create([
            "first_name" => "Eve",
            "last_name" => "Adams",
            "created_at" => Carbon::parse("2024-02-01 08:00:00"),
        ]);

        $newer = Contact::factory()->create([
            "first_name" => "Mark",
            "last_name" => "Brown",
            "created_at" => Carbon::parse("2024-02-02 08:00:00"),
        ]);

        $response = $this->actingAs($user)->get("/contacts/export");

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString("Adams Eve", $content);
        $this->assertStringContainsString("Brown Mark", $content);

        $lines = array_values(array_filter(explode("\n", trim($content))));
        $firstLine = ltrim($lines[0] ?? "", "\xEF\xBB\xBF");

        $this->assertStringContainsString("Brown Mark", $firstLine);
        $this->assertStringContainsString("Adams Eve", $lines[1] ?? "");
    }
}
```

#### コード解説
- `test_authenticated_user_can_export_filtered_contacts()`: 検索条件でフィルタリングされた結果が正しくエクスポートされるかをテストします。
- `$response->assertHeader("Content-Type", "text/csv; charset=UTF-8");`: レスポンスヘッダーがCSV形式であることを確認します。
- `$content = $response->streamedContent();`: ストリーム形式で返されるレスポンスの内容を取得します。
- `$this->assertStringContainsString(...)`: CSVの内容に、条件に一致するデータが含まれていることを確認します。
- `$this->assertStringNotContainsString(...)`: CSVの内容に、条件に一致しないデータが含まれていないことを確認します。
- `test_export_without_filters_returns_all_contacts_in_latest_order()`: フィルタを指定しない場合に、全てのデータが最新順でエクスポートされるかをテストします。
- `explode("\n", trim($content))`: CSVの内容を改行で分割し、各行を配列として取得します。
- `ltrim($lines[0] ?? "", "\xEF\xBB\xBF")`: 1行目の先頭にある可能性のあるBOM（バイトオーダーマーク）を除去します。
- 1行目に最新のデータ（`Brown Mark`）が、2行目に古いデータ（`Adams Eve`）が含まれていることを確認し、ソート順を検証します。

### 6.2 Webルートの機能テスト

このセクションでは、Webルートを通じたコントローラーの動作を検証します。お問い合わせフォームの確認画面・保存処理、管理画面のCRUD操作、タグ管理など、ユーザーが実際に行う操作を再現してテストします。

#### 6.2.1 お問い合わせコントローラーのテスト

```bash
sail artisan make:test ContactControllerTest
```

作成された`tests/Feature/ContactControllerTest.php`を以下のように編集します。

**tests/Feature/ContactControllerTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_displays_validated_data(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => 'Tokyo',
            'building' => 'Sunshine 60',
            'category_id' => $category->id,
            'detail' => 'テスト内容',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('Taro');
        $response->assertSee('Yamada');
        $response->assertSee('taro@example.com');
        $response->assertSee('Support');
    }

    public function test_confirm_validation_error_redirects_back(): void
    {
        $response = $this->post('/contacts/confirm', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'tel', 'address', 'category_id', 'detail']);
    }

    public function test_store_persists_contact_and_redirects_to_thanks(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '0312345678',
            'address' => 'Tokyo',
            'building' => 'Sunshine 60',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->post('/contacts', $payload);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where('email', 'taro@example.com')->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_store_validation_error_redirects_back(): void
    {
        $response = $this->post('/contacts', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'tel', 'address', 'category_id', 'detail']);
    }
}
```

#### コード解説
- `test_confirm_displays_validated_data()`: 確認画面へのPOSTリクエストが成功し、`contact.confirm`ビューが表示されることをテストします。
  - `$this->post('/contacts/confirm', $payload)`: 確認画面のURLにPOSTリクエストを送信します。
  - `$response->assertViewIs('contact.confirm')`: 確認画面のビューが返されたことを確認します。
  - `$response->assertSee('Taro')`, `assertSee('Yamada')` 等: 送信したデータが確認画面に表示されていることを確認します。
- `test_confirm_validation_error_redirects_back()`: 空のデータで確認画面にPOSTした場合、バリデーションエラーで元の画面にリダイレクトされることをテストします。
  - `$response->assertSessionHasErrors([...])`: セッションに特定のフィールドのバリデーションエラーが存在することをアサートします。
- `test_store_persists_contact_and_redirects_to_thanks()`: お問い合わせデータの保存が成功し、サンクスページにリダイレクトされることをテストします。
  - `$this->post('/contacts', $payload)`: お問い合わせ保存のURLにPOSTリクエストを送信します。
  - `$response->assertRedirect('/thanks')`: サンクスページへリダイレクトされることを確認します。
  - `$this->assertDatabaseHas('contacts', ...)`: データベースにお問い合わせデータが保存されたことを確認します。
  - `$this->assertDatabaseHas('contact_tag', ...)`: 中間テーブルにタグの紐付けが保存されたことを確認します。
- `test_store_validation_error_redirects_back()`: 空のデータで保存を試みた場合、バリデーションエラーでリダイレクトされることをテストします。

#### 6.2.2 管理画面コントローラーのテスト

```bash
sail artisan make:test AdminControllerTest
```

作成された`tests/Feature/AdminControllerTest.php`を以下のように編集します。

**tests/Feature/AdminControllerTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_index_displays_contacts_with_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => 'Delivery']);

        $matching = Contact::factory()->for($category)->create([
            'first_name' => 'Ken',
            'last_name' => 'Ito',
            'gender' => 1,
            'email' => 'ken@example.com',
            'created_at' => Carbon::parse('2024-02-01 09:00:00'),
        ]);

        Contact::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 2,
            'email' => 'jane@example.com',
            'created_at' => Carbon::parse('2024-02-02 09:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/admin?keyword=Ken&gender=1&category_id=' . $category->id . '&date=2024-02-01');

        $response->assertOk();
        $response->assertSee('Ken');
        $response->assertDontSee('Jane');
    }

    public function test_index_paginates_results(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('contacts');
        $this->assertEquals(7, $response->viewData('contacts')->count());
    }

    public function test_show_displays_contact_detail(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Mika',
            'last_name' => 'Suzuki',
        ]);

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id);

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertSee('Mika');
        $response->assertSee('Suzuki');
        $response->assertSee('Support');
    }

    public function test_destroy_removes_contact_and_redirects(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/contacts/' . $contact->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
```

#### コード解説
- `test_authenticated_user_can_view_admin_dashboard()`: 認証済みのユーザーが`/admin`にアクセスし、`admin.index`ビューが返されることをテストしています。
  - `$this->actingAs($user)->get('/admin')`: `actingAs()`で指定したユーザーとしてログインした状態でリクエストを送信します。
- `test_unauthenticated_user_is_redirected_to_login()`: 未認証のユーザーが`/admin`にアクセスした場合、ログインページにリダイレクトされることをテストします。
  - `$response->assertRedirect("/login")`: `/login`へのリダイレクトが発生したことをアサートします。認証ミドルウェアが正しく機能していることを確認できます。
- `test_index_displays_contacts_with_filter()`: キーワード・性別・カテゴリ・日付の全フィルタを指定して管理画面にアクセスし、検索結果が正しく表示されることをテストします。
  - `$response->assertSee('Ken')`: レスポンスのHTML内に指定した文字列が含まれていることをアサートします。
  - `$response->assertDontSee('Jane')`: フィルタ条件に合致しないデータが表示されていないことを確認します。
- `test_index_paginates_results()`: 10件のデータを作成し、ページネーションで1ページ目に7件が表示されることをテストします。
  - `$this->assertEquals(7, $response->viewData('contacts')->count())`: ビューに渡されたコレクションの件数を検証します。
- `test_show_displays_contact_detail()`: 個別のお問い合わせ詳細画面が正しく表示されることをテストします。`/admin/contacts/{id}`のURLでアクセスします。
  - `$response->assertViewIs('admin.show')`: 正しいビューが返されることを確認します。
  - `$response->assertSee('Support')`: カテゴリ名が画面に表示されていることを確認します。
- `test_destroy_removes_contact_and_redirects()`: お問い合わせの削除が成功し、管理画面にリダイレクトされることをテストします。
  - `$this->actingAs($user)->delete('/admin/contacts/' . $contact->id)`: DELETEリクエストを送信してお問い合わせを削除します。
  - `$response->assertRedirect('/admin')`: 削除後に管理画面にリダイレクトされることを確認します。
  - `$this->assertDatabaseMissing('contacts', ...)`: データベースからお問い合わせデータが正しく削除されたことを確認します。

#### 6.2.3 タグコントローラーのテスト

```bash
sail artisan make:test TagControllerTest
```

作成された`tests/Feature/TagControllerTest.php`を以下のように編集します。

**tests/Feature/TagControllerTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => 'priority']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => 'priority']);
    }

    public function test_authenticated_user_can_update_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'initial']);

        $response = $this->actingAs($user)->put('/admin/tags/' . $tag->id, ['name' => 'updated']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated']);
    }

    public function test_authenticated_user_can_delete_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/tags/' . $tag->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_unauthenticated_user_cannot_create_tag(): void
    {
        $response = $this->post('/admin/tags', ['name' => 'priority']);

        $response->assertRedirect('/login');
    }
}
```

#### コード解説
- `test_authenticated_user_can_create_tag()`: 認証済みのユーザーがタグを作成できることをテストします。
  - `$this->actingAs($user)->post('/admin/tags', ['name' => 'priority'])`: ログイン状態でPOSTリクエストを送信し、タグを作成します。
  - `$response->assertRedirect('/admin')`: 作成後に管理画面にリダイレクトされることを確認します。
  - `$this->assertDatabaseHas('tags', ['name' => 'priority'])`: データベースに新しいタグが保存されたことを確認します。
- `test_authenticated_user_can_update_tag()`: 認証済みのユーザーがタグ名を更新できることをテストします。
  - `$this->actingAs($user)->put('/admin/tags/' . $tag->id, ['name' => 'updated'])`: PUTリクエストでタグを更新します。
  - `$this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated'])`: データベースのタグ名が正しく更新されたことを確認します。
- `test_authenticated_user_can_delete_tag()`: 認証済みのユーザーがタグを削除できることをテストします。
  - `$this->actingAs($user)->delete('/admin/tags/' . $tag->id)`: DELETEリクエストでタグを削除します。
  - `$this->assertDatabaseMissing('tags', ['id' => $tag->id])`: データベースからタグが削除されたことを確認します。
- `test_unauthenticated_user_cannot_create_tag()`: 未認証のユーザーがタグを作成しようとした場合、ログインページにリダイレクトされることをテストします。
  - `$response->assertRedirect('/login')`: 認証ミドルウェアによりログインページにリダイレクトされることを確認します。

> **💡 SSRアプリケーションにおけるテストのポイント**
>
> 従来のLaravel SSR（サーバーサイドレンダリング）アプリケーションでは、APIとは異なるテストパターンが重要になります。
>
> | パターン | 使い方 | 検証内容 |
> |---|---|---|
> | `$this->post('/contacts/confirm', $payload)` | フォーム送信のシミュレート | `assertOk()` + `assertViewIs()` で確認画面の表示を検証 |
> | `$this->post('/contacts', $payload)` | データ保存のシミュレート | `assertRedirect('/thanks')` でリダイレクト先を検証 |
> | `$this->actingAs($user)->get('/admin')` | 認証付きページアクセス | `assertViewHas('contacts')` でビューへのデータ受け渡しを検証 |
> | `$this->actingAs($user)->delete(...)` | 認証付き削除操作 | `assertRedirect('/admin')` + `assertDatabaseMissing()` で削除とリダイレクトを検証 |
> | `$this->get('/admin')` (未認証) | 認証ガードの検証 | `assertRedirect('/login')` で未認証時のリダイレクトを検証 |
>
> APIテスト（`getJson`, `postJson`, `assertCreated`, `assertNoContent`等）とは異なり、SSRテストでは **ビューの表示** (`assertViewIs`, `assertViewHas`, `assertSee`) と **リダイレクト** (`assertRedirect`) が主要なアサーションとなります。

## 7. テストの実行 🏁

全てのテストコードを書き終えたら、いよいよ実行です。以下のコマンドをターミナルで実行してください。

```bash
sail artisan test
```

このコマンドは、`tests`ディレクトリ配下の全てのテストを自動で検出し、実行します。

実行結果が以下のように、全て「PASS」となれば成功です！

```
   PASS  Tests\Unit\ExportContactRequestTest
   ✓ rules accept valid payload
   ✓ gender rule rejects invalid value
   ✓ category rule requires existing identifier

   PASS  Tests\Unit\Models\CategoryTest
   ✓ category has many contacts

   PASS  Tests\Unit\Models\ContactTest
   ✓ contact belongs to category
   ✓ contact belongs to many tags

   PASS  Tests\Unit\Models\TagTest
   ✓ tag belongs to many contacts

   PASS  Tests\Unit\Requests\IndexContactRequestTest
   ✓ rules accept valid filters
   ✓ rules reject invalid gender

   PASS  Tests\Unit\Requests\StoreContactRequestTest
   ✓ rules accept valid payload with tags
   ✓ rules reject invalid phone number

   PASS  Tests\Unit\Requests\StoreTagRequestTest
   ✓ rules accept valid name
   ✓ rules reject empty name
   ✓ rules reject name exceeding max length
   ✓ rules reject duplicate name

   PASS  Tests\Unit\Requests\UpdateTagRequestTest
   ✓ rules allow current name but reject duplicates

   PASS  Tests\Feature\AdminControllerTest
   ✓ authenticated user can view admin dashboard
   ✓ unauthenticated user is redirected to login
   ✓ index displays contacts with filter
   ✓ index paginates results
   ✓ show displays contact detail
   ✓ destroy removes contact and redirects

   PASS  Tests\Feature\ContactControllerTest
   ✓ confirm displays validated data
   ✓ confirm validation error redirects back
   ✓ store persists contact and redirects to thanks
   ✓ store validation error redirects back

   PASS  Tests\Feature\ContactExportTest
   ✓ authenticated user can export filtered contacts
   ✓ export without filters returns all contacts in latest order

   PASS  Tests\Feature\ContactPageTest
   ✓ contact index page is accessible
   ✓ contact index page displays categories and tags
   ✓ contact thanks page is accessible

   PASS  Tests\Feature\TagControllerTest
   ✓ authenticated user can create tag
   ✓ authenticated user can update tag
   ✓ authenticated user can delete tag
   ✓ unauthenticated user cannot create tag

  Tests:  35 passed
  Time:   1.50s
```

もし失敗したテスト（FAIL）があれば、エラーメッセージをよく読んで、テストコードまたはアプリケーションコードのどちらに問題があるのかを特定し、修正してください。

> **💡 `deprecated` の警告が表示される場合**
>
> PHP 8.5環境では、テスト結果に `DEPR`（deprecated）や `Constant PDO::MYSQL_ATTR_SSL_CA is deprecated` という警告が表示されることがあります。これはテストコードの問題ではなく、Laravelのデフォルト設定ファイル（`config/database.php`）がPHP 8.5で非推奨となった定数を使用しているために発生します。テスト自体は正常にパスしているため、無視して問題ありません。

## 8. まとめ ✨

お疲れ様でした！このチャプターでは、Laravelの自動テスト機能を網羅的に学び、アプリケーションの品質をコードで保証する方法を習得しました。

- **Factory**でテストデータを効率的に生成し、
- **単体テスト**でモデル、リクエストといった個々の部品の動作を保証し、
- **機能テスト**でユーザーの操作に基づいた一連の機能が正しく連携して動作することを証明しました。

自動テストは、一度書けば何度でも同じ検証を瞬時に実行してくれます。これにより、機能追加やリファクタリングを行った際に、意図せず既存の機能を壊してしまう「デグレード」を恐れることなく、自信を持って開発を進めることができます。

これで、あなたはお問い合わせ管理システムの全ての機能を実装し、その品質を保証するテストコードまで書き上げました。これは、プロのWebアプリケーションエンジニアとしての非常に重要なスキルセットです。

この経験を糧に、ぜひ次のステップへと進んでください。本当にお疲れ様でした！
