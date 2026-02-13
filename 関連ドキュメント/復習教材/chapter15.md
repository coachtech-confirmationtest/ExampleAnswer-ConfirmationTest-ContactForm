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

プロジェクトのルートにある`phpunit.xml`ファイルを開き、`<php>`セクションに以下の`<env>`変数を追加または確認してください。

**phpunit.xml**
```xml
<phpunit ...>
    <testsuites>
        ...
    </testsuites>
    <source>
        ...
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
        <!-- <env name="DB_DATABASE" value=":memory:"/> -->
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_DATABASE" value="contact_form_test"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### コード解説
- `<env name="APP_ENV" value="testing"/>`: Laravelに、現在の環境がテスト環境であることを伝えます。
- `<env name="DB_CONNECTION" value="mysql"/>`: テストに使用するデータベースの種類を指定します。
- `<env name="DB_DATABASE" value="contact_form_test"/>`: テスト専用のデータベース名を指定します。このデータベースは事前に作成しておく必要があります。

> **💡 なぜインメモリDB（sqlite, :memory:）を使わないの？**
> インメモリデータベースは高速ですが、MySQLなどの実際の運用環境とは挙動が異なる場合があります。特に、MySQL特有の関数や制約を使用している場合、テストが通っても本番でエラーになる可能性があります。今回は、より本番環境に近い形でテストを行うため、MySQLを使用します。

## 4. Factoryの作成 🏭

テストを実行するには、テストデータが必要です。Factoryは、モデルに対応するダミーデータを簡単に生成するための仕組みです。

### 4.1 CategoryFactory

```bash
php artisan make:factory CategoryFactory --model=Category
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
php artisan make:factory ContactFactory --model=Contact
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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'gender' => $this->faker->numberBetween(1, 3),
            'email' => $this->faker->safeEmail(),
            'tel' => $this->faker->numerify('###########'),
            'address' => $this->faker->address(),
            'building' => $this->faker->optional()->secondaryAddress(),
            'detail' => $this->faker->realText(),
        ];
    }
}
```

### コード解説
- `'category_id' => Category::factory()`: `Contact`モデルは`Category`モデルに属しているため、`Contact`を作成する際に、関連する`Category`も自動で作成するように定義しています。
- `$this->faker->firstName()`: Fakerを使って、リアルな「名」を生成します。
- `$this->faker->numerify('###########')`: ` #` をランダムな数字（0-9）に置き換えます。ここでは11桁の電話番号を生成しています。
- `$this->faker->optional()->secondaryAddress()`: 50%の確率で`null`を、そうでなければ建物の部屋番号などを生成します。`building`カラムが`nullable`な場合に対応できます。

### 4.3 TagFactory

```bash
php artisan make:factory TagFactory --model=Tag
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
            'name' => $this->faker->word(),
        ];
    }
}
```

### コード解説
- `$this->faker->word()`: ランダムな一つの単語を生成します。

## 5. 単体テスト (Unit Tests) の作成 🔬

まずは、アプリケーションの最小単位である「モデル」「リクエスト」「リソース」が正しく動作するかを検証する単体テストから作成します。

### 5.1 Models

モデルのテストでは、主にリレーションシップが正しく定義されているかを確認します。

#### 5.1.1 Categoryモデルのテスト

```bash
php artisan make:test Models/CategoryTest --unit
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
        Contact::factory()->for($category)->count(3)->create();

        $this->assertCount(3, $category->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }
}
```

#### コード解説
- `use RefreshDatabase;`: このトレイトを使用すると、各テストメソッドの実行前にデータベースがマイグレーションされ、実行後にロールバックされます。これにより、他のテストの影響を受けないクリーンな状態でテストを実行できます。
- `test_category_has_many_contacts()`: `Category`モデルが`contacts`リレーション（一対多）を正しく持っているかをテストします。
- `$category = Category::factory()->create();`: テスト対象のカテゴリを1つ作成します。
- `Contact::factory()->for($category)->count(3)->create();`: 作成したカテゴリに属するお問い合わせを3つ作成します。
- `$this->assertCount(3, $category->contacts);`: `$category->contacts`（リレーション経由で取得したお問い合わせのコレクション）の件数が3件であることをアサート（断言）します。
- `$this->assertInstanceOf(Contact::class, $category->contacts->first());`: コレクションの最初の要素が`Contact`クラスのインスタンスであることをアサートし、リレーションが正しいモデルを返していることを確認します。

#### 5.1.2 Contactモデルのテスト

```bash
php artisan make:test Models/ContactTest --unit
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

    public function test_contact_belongs_to_a_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->for($category)->create();

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertTrue($contact->category->is($category));
    }

    public function test_contact_belongs_to_many_tags(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $contact->tags()->attach($tags);

        $this->assertCount(2, $contact->tags);
        $this->assertInstanceOf(Tag::class, $contact->tags->first());
    }
}
```

#### コード解説
- `test_contact_belongs_to_a_category()`: `Contact`モデルが`category`リレーション（多対一）を正しく持っているかをテストします。
- `$this->assertTrue($contact->category->is($category));`: 2つのモデルインスタンスが同じ（同じ主キーを持つ同じテーブルのレコード）であるかをアサートします。
- `test_contact_belongs_to_many_tags()`: `Contact`モデルが`tags`リレーション（多対多）を正しく持っているかをテストします。
- `$contact->tags()->attach($tags);`: `Contact`に複数の`Tag`を紐付けます。

#### 5.1.3 Tagモデルのテスト

```bash
php artisan make:test Models/TagTest --unit
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
        $contacts = Contact::factory()->count(3)->create();
        $tag->contacts()->attach($contacts);

        $this->assertCount(3, $tag->contacts);
        $this->assertInstanceOf(Contact::class, $tag->contacts->first());
    }
}
```

#### コード解説
- `test_tag_belongs_to_many_contacts()`: `Tag`モデルが`contacts`リレーション（多対多）を正しく持っているかをテストします。

### 5.2 Requests

リクエストクラスのテストでは、バリデーションルールが意図通りに機能するかを確認します。

#### 5.2.1 StoreContactRequestのテスト

```bash
php artisan make:test Requests/StoreContactRequestTest --unit
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

        return Validator::make($data, $request->rules());
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
php artisan make:test Requests/IndexContactRequestTest --unit
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

    private function makeValidator(array $data)
    {
        $request = new IndexContactRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_rules_accept_valid_payload(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'keyword' => 'search term',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2024-01-01',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_are_all_optional(): void
    {
        $validator = $this->makeValidator([]);

        $this->assertTrue($validator->passes());
    }
}
```

#### コード解説
- `test_rules_accept_valid_payload()`: 検索条件として有効なデータがバリデーションを通過することをテストします。
- `test_rules_are_all_optional()`: `IndexContactRequest`のルールは全て`nullable`（任意）なので、空のデータでもバリデーションを通過することをテストします。

#### 5.2.3 ExportContactRequestのテスト

```bash
php artisan make:test ExportContactRequestTest --unit
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
php artisan make:test Requests/StoreTagRequestTest --unit
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
- `test_rules_reject_duplicate_name()`: `name`に既に存在するタグ名が指定された場合にバリデーションが失敗すること（`unique:tags,name`）をテストします。

#### 5.2.5 UpdateTagRequestのテスト

```bash
php artisan make:test Requests/UpdateTagRequestTest --unit
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

### 5.3 Resources

APIリソースのテストでは、モデルが意図した通りのJSON構造に変換されるかを確認します。

#### 5.3.1 CategoryResourceのテスト

```bash
php artisan make:test Resources/CategoryResourceTest --unit
```

作成された`tests/Unit/Resources/CategoryResourceTest.php`を以下のように編集します。

**tests/Unit/Resources/CategoryResourceTest.php**
```php
<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_resource_structure(): void
    {
        $category = Category::factory()->create(["content" => "Delivery Issues"]);

        $resource = (new CategoryResource($category))->toArray(new Request());

        $this->assertSame($category->id, $resource["id"]);
        $this->assertSame("Delivery Issues", $resource["content"]);
    }
}
```

#### コード解説
- `$resource = (new CategoryResource($category))->toArray(new Request());`: `CategoryResource`のインスタンスを作成し、`toArray()`メソッドを呼び出して、変換後の配列を取得します。
- `$this->assertSame($category->id, $resource["id"]);`: 変換後の配列に、期待される`id`と`content`が含まれていることを`assertSame`で厳密に比較・アサートします。

#### 5.3.2 ContactResourceのテスト

```bash
php artisan make:test Resources/ContactResourceTest --unit
```

作成された`tests/Unit/Resources/ContactResourceTest.php`を以下のように編集します。

**tests/Unit/Resources/ContactResourceTest.php**
```php
<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ContactResource;
use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_resource_contains_expected_fields(): void
    {
        $category = Category::factory()->create(["content" => "Support"]);
        $contact = Contact::factory()->for($category)->create([
            "first_name" => "Saya",
            "last_name" => "Tanaka",
            "building" => "Blue Tower",
        ]);
        $contact->setRelation("category", $category);

        $resource = (new ContactResource($contact))->toArray(new Request());

        $this->assertSame($contact->id, $resource["id"]);
        $this->assertSame("Saya", $resource["first_name"]);
        $this->assertSame("Tanaka", $resource["last_name"]);
        $this->assertSame("Blue Tower", $resource["building"]);
        $this->assertSame("Support", $resource["category"]["content"]);
    }
}
```

#### コード解説
- `$contact->setRelation("category", $category);`: `ContactResource`は`category`リレーションを読み込むため、テストで明示的にリレーションをセットしています。
- `$this->assertSame("Support", $resource["category"]["content"]);`: ネストされたリソース（`category`）の内容も正しく変換されていることを確認します。

#### 5.3.3 TagResourceのテスト

```bash
php artisan make:test Resources/TagResourceTest --unit
```

作成された`tests/Unit/Resources/TagResourceTest.php`を以下のように編集します。

**tests/Unit/Resources/TagResourceTest.php**
```php
<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_resource_structure(): void
    {
        $tag = Tag::factory()->create(["name" => "important"]);

        $resource = (new TagResource($tag))->toArray(new Request());

        $this->assertSame($tag->id, $resource["id"]);
        $this->assertSame("important", $resource["name"]);
    }
}
```

#### コード解説
- `CategoryResource`のテストと同様に、`Tag`モデルが期待通りのJSON構造に変換されることを確認しています。

## 6. 機能テスト (Feature Tests) の作成 🚀

いよいよ、ユーザーの操作を模した機能テストを実装していきます。ここでは、実際にHTTPリクエストを送信し、返ってきたレスポンスやデータベースの状態を検証します。

### 6.1 Webページ関連

#### 6.1.1 お問い合わせページのテスト

```bash
php artisan make:test ContactPageTest
```

作成された`tests/Feature/ContactPageTest.php`を以下のように編集します。

**tests/Feature/ContactPageTest.php**
```php
<?php

namespace Tests\Feature;

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
- `test_contact_thanks_page_is_accessible()`: サンクスページ(`/thanks`)が正常に表示されることをテストします。

#### 6.1.2 管理画面コントローラーのテスト

```bash
php artisan make:test AdminControllerTest
```

作成された`tests/Feature/AdminControllerTest.php`を以下のように編集します。

**tests/Feature/AdminControllerTest.php**
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/admin");

        $response->assertOk();
        $response->assertViewIs("admin.index");
    }
}
```

#### コード解説
- `actingAs($user)`: 指定したユーザーとしてログインした状態でリクエストを送信します。
- `test_authenticated_user_can_view_admin_dashboard()`: 認証済みのユーザーが`/admin`にアクセスし、`admin.index`ビューが返されることをテストしています。

#### 6.1.3 CSVエクスポート機能のテスト

```bash
php artisan make:test ContactExportTest
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
        $firstLine = ltrim($lines[0] ?? ", "\xEF\xBB\xBF");

        $this->assertStringContainsString("Brown Mark", $firstLine);
        $this->assertStringContainsString("Adams Eve", $lines[1] ?? ");
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
- `ltrim($lines[0] ?? ", "\xEF\xBB\xBF")`: 1行目の先頭にある可能性のあるBOM（バイトオーダーマーク）を除去します。
- 1行目に最新のデータ（`Brown Mark`）が、2行目に古いデータ（`Adams Eve`）が含まれていることを確認し、ソート順を検証します。

### 6.2 APIエンドポイント

#### 6.2.1 カテゴリAPIのテスト

```bash
php artisan make:test Api/CategoryControllerTest
```

作成された`tests/Feature/Api/CategoryControllerTest.php`を以下のように編集します。

**tests/Feature/Api/CategoryControllerTest.php**
```php
<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_categories(): void
    {
        $categories = Category::factory()->count(3)->create();

        $response = $this->getJson("/api/categories");

        $response->assertOk();
        $response->assertJsonCount(3, "data");
        $response->assertJsonFragment([
            "id" => $categories->first()->id,
            "content" => $categories->first()->content,
        ]);
    }
}
```

#### コード解説
- `$this->getJson("/api/categories")`: 指定したAPIエンドポイントにGETリクエストを送信し、JSON形式のレスポンスを期待します。
- `$response->assertJsonCount(3, "data");`: レスポンスJSONの`data`キー配下の要素が3つであることをアサートします。
- `$response->assertJsonFragment([...])`: レスポンスJSONの中に、指定したキーと値のペアを持つ断片が含まれていることをアサートします。

#### 6.2.2 お問い合わせAPIのテスト

```bash
php artisan make:test Api/ContactControllerTest
```

作成された`tests/Feature/Api/ContactControllerTest.php`を以下のように編集します。

**tests/Feature/Api/ContactControllerTest.php**
```php
<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_applies_all_available_filters(): void
    {
        $category = Category::factory()->create(["content" => "Delivery"]);
        $otherCategory = Category::factory()->create(["content" => "Other"]);

        $matching = Contact::factory()->for($category)->create([
            "first_name" => "Ken",
            "last_name" => "Ito",
            "gender" => 1,
            "email" => "ken@example.com",
            "created_at" => Carbon::parse("2024-02-01 09:00:00"),
        ]);

        Contact::factory()->for($otherCategory)->create([
            "first_name" => "Jane",
            "last_name" => "Smith",
            "gender" => 2,
            "email" => "jane@example.com",
            "created_at" => Carbon::parse("2024-02-02 09:00:00"),
        ]);

        $tag = Tag::factory()->create();
        $matching->tags()->attach($tag);

        $response = $this->getJson("/api/contacts?keyword=Ken&gender=1&category_id=" . $category->id . "&date=2024-02-01");

        $response->assertOk();
        $response->assertJsonCount(1, "data");
        $response->assertJsonPath("data.0.id", $matching->id);
        $response->assertJsonPath("data.0.category.id", $category->id);
        $response->assertJsonPath("meta.total", 1);
    }

    public function test_store_persists_contact_and_attaches_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            "first_name" => "Taro",
            "last_name" => "Yamada",
            "gender" => 1,
            "email" => "taro@example.com",
            "tel" => "0312345678",
            "address" => "Tokyo",
            "building" => "Sunshine 60",
            "category_id" => $category->id,
            "detail" => "お問い合わせ内容です",
            "tag_ids" => $tags->pluck("id")->toArray(),
        ];

        $response = $this->postJson("/api/contacts", $payload);

        $response->assertCreated();
        $this->assertDatabaseHas("contacts", [
            "email" => "taro@example.com",
            "category_id" => $category->id,
        ]);

        $contact = Contact::where("email", "taro@example.com")->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas("contact_tag", [
                "contact_id" => $contact->id,
                "tag_id" => $tag->id,
            ]);
        }
    }

    public function test_show_returns_single_contact(): void
    {
        $category = Category::factory()->create(["content" => "Support"]);
        $contact = Contact::factory()->for($category)->create([
            "first_name" => "Mika",
            "last_name" => "Suzuki",
        ]);

        $response = $this->getJson("/api/contacts/" . $contact->id);

        $response->assertOk();
        $response->assertJsonPath("data.id", $contact->id);
        $response->assertJsonPath("data.category.id", $category->id);
    }

    public function test_destroy_removes_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/contacts/" . $contact->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing("contacts", [
            "id" => $contact->id,
        ]);
    }
}
```

#### コード解説
- `test_index_applies_all_available_filters()`: `index`アクションが、全ての検索フィルタ（キーワード、性別、カテゴリ、日付）を正しく適用して結果を返すことをテストします。
- `$response->assertJsonPath("data.0.id", $matching->id)`: JSONレスポンスの特定のパス（`data`配列の0番目の要素の`id`）が、期待した値（`$matching->id`）であることをアサートします。
- `test_store_persists_contact_and_attaches_tags()`: `store`アクションが、お問い合わせデータを作成し、関連するタグを中間テーブルに正しく保存することをテストします。
- `$this->assertDatabaseHas("contacts", ...)`: `contacts`テーブルに指定したデータが存在することを確認します。
- `foreach ($tags as $tag) { ... }`: ループを使って、全てのタグが正しく紐付けられたかを`contact_tag`テーブルで確認します。
- `test_show_returns_single_contact()`: `show`アクションが、指定したIDのお問い合わせデータを正しく返すことをテストします。
- `test_destroy_removes_contact()`: `destroy`アクションが、指定したIDのお問い合わせデータをデータベースから削除することをテストします。
- `$response->assertNoContent()`: レスポンスのステータスコードが204 No Contentであることをアサートします。
- `$this->assertDatabaseMissing("contacts", ...)`: `contacts`テーブルから指定したデータが削除されたことを確認します。

#### 6.2.3 タグAPIのテスト

```bash
php artisan make:test Api/TagControllerTest
```

作成された`tests/Feature/Api/TagControllerTest.php`を以下のように編集します。

**tests/Feature/Api/TagControllerTest.php**
```php
<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->getJson("/api/tags");

        $response->assertOk();
        $response->assertJsonCount(2, "data");
        $response->assertJsonFragment([
            "id" => $tags->first()->id,
            "name" => $tags->first()->name,
        ]);
    }

    public function test_store_creates_tag(): void
    {
        $response = $this->postJson("/api/tags", ["name" => "priority"]);

        $response->assertCreated();
        $this->assertDatabaseHas("tags", ["name" => "priority"]);
    }

    public function test_update_modifies_tag_name(): void
    {
        $tag = Tag::factory()->create(["name" => "initial"]);

        $response = $this->putJson("/api/tags/" . $tag->id, ["name" => "updated"]);

        $response->assertNoContent();
        $this->assertDatabaseHas("tags", ["id" => $tag->id, "name" => "updated"]);
    }

    public function test_destroy_deletes_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/" . $tag->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing("tags", ["id" => $tag->id]);
    }
}
```

#### コード解説
- `test_store_creates_tag()`: `store`アクションが、新しいタグをデータベースに保存することをテストします。
- `$response->assertCreated()`: レスポンスのステータスコードが201 Createdであることをアサートします。
- `test_update_modifies_tag_name()`: `update`アクションが、指定したタグの名前を更新することをテストします。
- `$this->putJson(...)`: PUTリクエストを送信します。
- `test_destroy_deletes_tag()`: `destroy`アクションが、指定したタグをデータベースから削除することをテストします。

## 7. テストの実行 🏁

全てのテストコードを書き終えたら、いよいよ実行です。以下のコマンドをターミナルで実行してください。

```bash
php artisan test
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
   ✓ contact belongs to a category
   ✓ contact belongs to many tags

   PASS  Tests\Unit\Models\TagTest
   ✓ tag belongs to many contacts

   PASS  Tests\Unit\Requests\IndexContactRequestTest
   ✓ rules accept valid payload
   ✓ rules are all optional

   PASS  Tests\Unit\Requests\StoreContactRequestTest
   ✓ rules accept valid payload with tags
   ✓ rules reject invalid phone number

   PASS  Tests\Unit\Requests\StoreTagRequestTest
   ✓ rules accept valid name
   ✓ rules reject duplicate name

   PASS  Tests\Unit\Requests\UpdateTagRequestTest
   ✓ rules allow current name but reject duplicates

   PASS  Tests\Unit\Resources\CategoryResourceTest
   ✓ category resource structure

   PASS  Tests\Unit\Resources\ContactResourceTest
   ✓ contact resource contains expected fields

   PASS  Tests\Unit\Resources\TagResourceTest
   ✓ tag resource structure

   PASS  Tests\Feature\AdminControllerTest
   ✓ authenticated user can view admin dashboard

   PASS  Tests\Feature\Api\CategoryControllerTest
   ✓ index returns all categories

   PASS  Tests\Feature\Api\ContactControllerTest
   ✓ index applies all available filters
   ✓ store persists contact and attaches tags
   ✓ show returns single contact
   ✓ destroy removes contact

   PASS  Tests\Feature\Api\TagControllerTest
   ✓ index returns all tags
   ✓ store creates tag
   ✓ update modifies tag name
   ✓ destroy deletes tag

   PASS  Tests\Feature\ContactExportTest
   ✓ authenticated user can export filtered contacts
   ✓ export without filters returns all contacts in latest order

   PASS  Tests\Feature\ContactPageTest
   ✓ contact index page is accessible
   ✓ contact thanks page is accessible

  Tests:  28 passed
  Time:   1.50s
```

もし失敗したテスト（FAIL）があれば、エラーメッセージをよく読んで、テストコードまたはアプリケーションコードのどちらに問題があるのかを特定し、修正してください。

## 8. まとめ ✨

お疲れ様でした！このチャプターでは、Laravelの自動テスト機能を網羅的に学び、アプリケーションの品質をコードで保証する方法を習得しました。

- **Factory**でテストデータを効率的に生成し、
- **単体テスト**でモデル、リクエスト、リソースといった個々の部品の動作を保証し、
- **機能テスト**でユーザーの操作に基づいた一連の機能が正しく連携して動作することを証明しました。

自動テストは、一度書けば何度でも同じ検証を瞬時に実行してくれます。これにより、機能追加やリファクタリングを行った際に、意図せず既存の機能を壊してしまう「デグレード」を恐れることなく、自信を持って開発を進めることができます。

これで、あなたはお問い合わせ管理システムの全ての機能を実装し、その品質を保証するテストコードまで書き上げました。これは、プロのWebアプリケーションエンジニアとしての非常に重要なスキルセットです。

この経験を糧に、ぜひ次のステップへと進んでください。本当にお疲れ様でした！
