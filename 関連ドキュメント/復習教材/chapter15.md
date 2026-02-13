
# Chapter 15: 鉄壁の品質保証 - Laravelの自動テストをマスターする

## 1. はじめに 📖

お疲れ様です！これまで、お問い合わせ管理システムの全機能を実装してきました。しかし、プロのエンジニアの仕事は「作って終わり」ではありません。作成したアプリケーションが仕様通りに正しく動作することを保証し、将来の機能追加や変更によって既存の機能が壊れていないことを確認し続ける「品質保証」が極めて重要になります。

このチャプターでは、その品質保証の根幹をなす「自動テスト」の実装方法を学びます。Laravelに標準で搭載されているテストフレームワーク「PHPUnit」を使い、これまで実装してきた全ての機能が正しく動作することをコードで証明していきます。

このチャプターを終える頃には、あなたは単に機能を作れるだけでなく、その品質を自ら保証できる、市場価値の高いエンジニアへと成長していることでしょう。

## 2. テストの全体像 🗺️

Laravelのテストは、大きく分けて2種類あります。

- **単体テスト (Unit Test)**: アプリケーションの非常に小さな、独立した一部分をテストします。例えば、モデルのリレーション定義が正しいか、リクエストクラスのバリデーションルールが意図通りか、といった個別のコンポーネントを対象とします。部品単体の品質を保証するイメージです。
- **機能テスト (Feature Test)**: 複数のコンポーネントが連携した、より大きな機能単位をテストします。例えば、ユーザーがフォームを送信してからデータベースに保存されるまでの一連の流れや、APIエンドポイントが正しいレスポンスを返すか、といったユーザーの操作に近い視点でテストします。製品としての機能が正しく動くかを保証するイメージです。

これらのテストは、`tests`ディレクトリ配下に、それぞれ`Unit`と`Feature`というサブディレクトリを作成して管理するのが一般的です。

## 3. テストの準備 🛠️

### 3.1 テスト用データベースの設定

テストを実行する際、開発用のデータベースを汚さずに、テスト専用のクリーンなデータベース環境を使用するのがベストプラクティスです。

Laravelでは、`phpunit.xml`ファイルでテスト環境の設定を行います。ファイルを開いて、`<php>`セクションを確認してください。

**phpunit.xml**
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_DRIVER" value="array"/>
    <!-- <env name="DB_CONNECTION" value="sqlite"/> -->
    <!-- <env name="DB_DATABASE" value=":memory:"/> -->
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

#### コードリーディング
- `<env name="APP_ENV" value="testing"/>`: テスト実行時には、アプリケーションの環境(`APP_ENV`)が`testing`になります。これにより、`config/app.php`などで`testing`環境専用の設定を読み込むことができます。
- `DB_CONNECTION`と`DB_DATABASE`: デフォルトではコメントアウトされています。この状態だと、`.env`ファイルで設定されたデータベースが使用されます。テスト実行時だけ異なるデータベース（例えばSQLiteのインメモリデータベース）を使いたい場合は、ここのコメントを外して設定します。

今回は、テスト実行のたびにデータベースを初期化してくれる`RefreshDatabase`トレイトを使用するため、開発用データベースをそのまま使っても問題ありませんが、高速なインメモリデータベースである`SQLite`を使用することも一般的です。

今回は、模範解答の構成に合わせ、`.env`ファイルの設定（MySQL）をそのまま利用して進めます。

## 4. Factoryの作成 🏭

テストを実行する際には、前提条件となるテストデータ（例: ユーザー、カテゴリ、お問い合わせ情報など）が必要になります。これらのデータを毎回手動で作成するのは非常に手間がかかります。そこで活躍するのが「Factory」です。

Factoryは、モデルに対応するダミーデータを簡単に、かつ大量に生成するための仕組みです。これから作成するテストコードの様々な場面で利用します。

### 4.1 CategoryFactoryの作成

まず、カテゴリのFactoryを作成します。

```bash
php artisan make:factory CategoryFactory --model=Category
```

作成された`database/factories/CategoryFactory.php`を以下のように編集します。

**database/factories/CategoryFactory.php**
```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->words(3, true),
        ];
    }
}
```

#### コード解説
- `protected $model = Category::class;`: このFactoryが`App\Models\Category`モデルに対応することを定義しています。
- `definition()`: このメソッド内に、生成するダミーデータの定義を記述します。
- `$this->faker->words(3, true)`: Laravelに組み込まれているダミーデータ生成ライブラリ「Faker」を使って、ランダムな3つの単語を文字列として生成します。

### 4.2 ContactFactoryの作成

次にお問い合わせのFactoryです。カテゴリとのリレーションもここで定義します。

```bash
php artisan make:factory ContactFactory --model=Contact
```

作成された`database/factories/ContactFactory.php`を以下のように編集します。

**database/factories/ContactFactory.php**
```php
<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
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

#### コード解説
- `'category_id' => Category::factory()`: `Contact`を作成する際に、関連する`Category`も同時に作成します。`CategoryFactory`が呼び出され、作成された`Category`のIDが`category_id`に自動的に設定されます。
- `$this->faker->...`: Fakerを使って、氏名、性別、メールアドレスなど、リアルなダミーデータを生成しています。
- `unique()`: `email`がデータベース内で一意になるようにします。
- `optional()`: `building`カラムのように、`null`を許容するカラムに対して、50%の確率で`null`を、50%の確率でダミーデータを生成します。

### 4.3 TagFactoryの作成

最後にタグのFactoryを作成します。

```bash
php artisan make:factory TagFactory --model=Tag
```

作成された`database/factories/TagFactory.php`を以下のように編集します。

**database/factories/TagFactory.php**
```php
<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
```

#### コード解説
- `'name' => $this->faker->unique()->words(2, true)`: `Tag`モデルの`name`カラムに、ユニークな2つの単語からなる文字列を生成します。

これで、テストデータを自在に生成する準備が整いました。

## 5. 単体テスト (Unit Tests) の作成 🔬

ここからは、アプリケーションの各部品が正しく機能するかを個別にテストしていきます。

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
        Contact::factory()->count(2)->for($category)->create();

        $this->assertCount(2, $category->fresh()->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }
}
```

#### コード解説
- `use RefreshDatabase;`: このトレイトを使用すると、各テストメソッドの実行前にデータベースがマイグレーションされ、実行後にはロールバックされます。これにより、各テストが独立したクリーンな状態で実行されることが保証されます。
- `test_category_has_many_contacts()`: テストメソッド名は`test_`で始めるのが規約です。メソッド名で「何をテストしているか」を明確に表現します。
- `$category = Category::factory()->create();`: `CategoryFactory`を使って、テスト用のカテゴリを1つ作成します。
- `Contact::factory()->count(2)->for($category)->create();`: `ContactFactory`を使って、先ほど作成した`$category`に紐づくお問い合わせを2つ作成します。
- `$this->assertCount(2, $category->fresh()->contacts);`: `$category`に紐づく`contacts`の数が2件であることをアサート（表明）します。`fresh()`メソッドでデータベースから最新の状態を取得しています。
- `$this->assertInstanceOf(Contact::class, $category->contacts->first());`: `$category`の`contacts`リレーションの最初の要素が、`App\Models\Contact`クラスのインスタンスであることをアサートします。これにより、`hasMany`リレーションが正しく設定されていることを確認できます。

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
- `test_contact_belongs_to_category()`: `Contact`モデルの`belongsTo`リレーションをテストします。
- `$this->assertTrue($contact->category->is($category));`: `$contact`の`category`リレーションが、期待される`$category`インスタンスと同一であることをアサートします。
- `test_contact_belongs_to_many_tags()`: `Contact`モデルの`belongsToMany`リレーションをテストします。
- `$contact->tags()->attach($tags->pluck('id'));`: `attach`メソッドを使って、`$contact`に複数の`$tags`を紐付けます。
- `$this->assertCount(2, $contact->tags);`: 紐付けられたタグの数が2件であることを確認します。

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
        $contacts = Contact::factory()->count(2)->create();

        $tag->contacts()->attach($contacts->pluck('id')->toArray());
        $tag->load('contacts');

        $this->assertCount(2, $tag->contacts);
        $this->assertTrue($tag->contacts->pluck('id')->contains($contacts->first()->id));
    }
}
```

#### コード解説
- `test_tag_belongs_to_many_contacts()`: `Tag`モデルの`belongsToMany`リレーションをテストします。`Contact`モデルのテストと同様に、`attach`メソッドでリレーションを構築し、その結果をアサートしています。

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

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_is_accessible(): void
    {
        $response = $this->get("/");

        $response->assertOk();
        $response->assertViewIs("contact.index");
    }

    public function test_admin_page_is_inaccessible_for_guests(): void
    {
        $response = $this->get("/admin");

        $response->assertRedirect("/login");
    }

    public function test_admin_page_is_accessible_for_logged_in_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/admin");

        $response->assertOk();
    }
}
```

#### コード解説
- `$this->get("/")`: アプリケーションのルートURL（`/`）に対してGETリクエストを送信します。
- `$response->assertOk()`: レスポンスのHTTPステータスコードが200 OKであることをアサートします。
- `$response->assertViewIs("contact.index")`: レスポンスとして`contact.index`ビューが返されたことをアサートします。
- `test_admin_page_is_inaccessible_for_guests()`: 未ログインのユーザー（ゲスト）が`/admin`にアクセスした場合、`/login`にリダイレクトされることを`assertRedirect()`でテストします。
- `test_admin_page_is_accessible_for_logged_in_users()`: `actingAs($user)`で指定したユーザーとしてログインした状態でリクエストを送信します。ログイン済みユーザーは`/admin`にアクセスできること（200 OK）をテストします。

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

    public function test_index_is_accessible_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/admin");

        $response->assertOk();
        $response->assertViewIs("admin.index");
    }
}
```

#### コード解説
- `ContactPageTest`と同様に、認証済みのユーザーが`/admin`にアクセスし、`admin.index`ビューが返されることをテストしています。

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_downloads_csv_with_filtered_contacts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $matchingContact = Contact::factory()->for($category)->create([
            "first_name" => "Taro",
            "gender" => 1,
        ]);

        Contact::factory()->create(["gender" => 2]);

        $response = $this->actingAs($user)->get("/admin/contacts/export?gender=1&category_id=" . $category->id);

        $response->assertOk();
        $response->assertHeader("Content-Type", "text/csv; charset=UTF-8");

        $content = $response->streamedContent();

        $this->assertStringContainsString(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        $this->assertStringContainsString("Taro", $content);
        $this->assertStringNotContainsString("Jiro", $content);
    }
}
```

#### コード解説
- `$matchingContact`: 検索条件に一致するテストデータを作成します。
- `Contact::factory()->create(["gender" => 2]);`: 検索条件に一致しないテストデータも作成します。
- `$response = $this->actingAs($user)->get("/admin/contacts/export?gender=1&category_id=" . $category->id);`: クエリパラメータで検索条件を指定してエクスポートエンドポイントにリクエストを送信します。
- `$response->assertHeader("Content-Type", "text/csv; charset=UTF-8");`: レスポンスヘッダーがCSV形式であることを確認します。
- `$content = $response->streamedContent();`: ストリーム形式で返されるレスポンスの内容を取得します。
- `$this->assertStringContainsString(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);`: レスポンスの先頭にBOM（バイトオーダーマーク）が付与されているかを確認し、文字化けを防ぐ対策がされていることをテストします。
- `$this->assertStringContainsString("Taro", $content);`: レスポンスに、条件に一致するデータ（`Taro`）が含まれていることを確認します。
- `$this->assertStringNotContainsString("Jiro", $content);`: レスポンスに、条件に一致しないデータ（`Jiro`）が含まれていないことを確認します。

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
        Category::factory()->count(3)->create();

        $response = $this->getJson("/api/categories");

        $response->assertOk();
        $response->assertJsonCount(3, "data");
    }
}
```

#### コード解説
- `$this->getJson("/api/categories")`: 指定したAPIエンドポイントにGETリクエストを送信し、JSON形式のレスポンスを期待します。
- `$response->assertJsonCount(3, "data");`: レスポンスJSONの`data`キー配下の要素が3つであることをアサートします。

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_contacts(): void
    {
        Contact::factory()->count(20)->create();

        $response = $this->getJson("/api/contacts");

        $response->assertOk();
        $response->assertJsonCount(10, "data");
        $response->assertJsonPath("meta.total", 20);
    }

    public function test_store_creates_new_contact_with_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            "first_name" => "Taro",
            "last_name" => "Yamada",
            "gender" => 1,
            "email" => "taro@example.com",
            "tel" => "09012345678",
            "address" => "Tokyo",
            "category_id" => $category->id,
            "detail" => "Test inquiry",
            "tag_ids" => $tags->pluck("id")->toArray(),
        ];

        $response = $this->postJson("/api/contacts", $payload);

        $response->assertCreated();
        $this->assertDatabaseHas("contacts", ["email" => "taro@example.com"]);
        $this->assertDatabaseCount("contact_tag", 2);
    }

    public function test_show_returns_a_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonPath("data.id", $contact->id);
    }

    public function test_destroy_deletes_a_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing("contacts", ["id" => $contact->id]);
    }
}
```

#### コード解説
- `test_index_returns_paginated_contacts()`: `index`アクションがページネーションされた結果を返すことをテストします。デフォルトでは1ページあたり10件なので、`assertJsonCount(10, "data")`で確認し、`meta.total`で総数が20件であることを確認します。
- `test_store_creates_new_contact_with_tags()`: `store`アクションをテストします。
- `$this->postJson("/api/contacts", $payload)`: `$payload`をリクエストボディとしてPOSTリクエストを送信します。
- `$response->assertCreated()`: レスポンスのステータスコードが201 Createdであることをアサートします。
- `$this->assertDatabaseHas("contacts", ...)`: `contacts`テーブルに指定したデータが存在することを確認します。
- `$this->assertDatabaseCount("contact_tag", 2)`: 中間テーブル`contact_tag`にレコードが2件作成されたことを確認し、多対多リレーションが正しく保存されたことをテストします。
- `test_destroy_deletes_a_contact()`: `destroy`アクションをテストします。
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
        Tag::factory()->count(5)->create();

        $response = $this->getJson("/api/tags");

        $response->assertOk();
        $response->assertJsonCount(5, "data");
    }

    public function test_store_creates_new_tag(): void
    {
        $response = $this->postJson("/api/tags", ["name" => "new-tag"]);

        $response->assertCreated();
        $this->assertDatabaseHas("tags", ["name" => "new-tag"]);
    }

    public function test_update_modifies_a_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/tags/{$tag->id}", ["name" => "updated-name"]);

        $response->assertNoContent();
        $this->assertDatabaseHas("tags", ["id" => $tag->id, "name" => "updated-name"]);
    }

    public function test_destroy_deletes_a_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing("tags", ["id" => $tag->id]);
    }
}
```

#### コード解説
- `test_update_modifies_a_tag()`: `update`アクションをテストします。
- `$this->putJson(...)`: PUTリクエストを送信します。
- `$response->assertNoContent()`: 更新や削除が成功した場合、レスポンスボディは不要なため、204 No Contentが返されることをテストします。

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
   ✓ contact belongs to category
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
   ✓ index is accessible for authenticated users

   PASS  Tests\Feature\Api\CategoryControllerTest
   ✓ index returns all categories

   PASS  Tests\Feature\Api\ContactControllerTest
   ✓ index returns paginated contacts
   ✓ store creates new contact with tags
   ✓ show returns a contact
   ✓ destroy deletes a contact

   PASS  Tests\Feature\Api\TagControllerTest
   ✓ index returns all tags
   ✓ store creates new tag
   ✓ update modifies a tag
   ✓ destroy deletes a tag

   PASS  Tests\Feature\ContactExportTest
   ✓ export downloads csv with filtered contacts

   PASS  Tests\Feature\ContactPageTest
   ✓ contact page is accessible
   ✓ admin page is inaccessible for guests
   ✓ admin page is accessible for logged in users

  Tests:  32 passed
  Time:   1.33s
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
