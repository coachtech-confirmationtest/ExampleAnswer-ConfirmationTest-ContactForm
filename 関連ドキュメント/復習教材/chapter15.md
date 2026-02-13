# Chapter 15: 鉄壁の品質保証 - Laravelの自動テストをマスターする

## 1. はじめに 📖

お疲れ様です！これまで、お問い合わせ管理システムの全機能を実装してきました。しかし、プロのエンジニアの仕事は「作って終わり」ではありません。作成したアプリケーションが仕様通りに正しく動作することを保証し、将来の機能追加や変更によって既存の機能が壊れていないことを確認し続ける「品質保証」が極めて重要になります。

このチャプターでは、その品質保証の根幹をなす「自動テスト」の実装方法を学びます。Laravelに標準で搭載されているテストフレームワーク「PHPUnit」を使い、これまで実装してきた全ての機能が正しく動作することをコードで証明していきます。

このチャプターを終える頃には、あなたは単に機能を作れるだけでなく、その品質を自ら保証できる、市場価値の高いエンジニアへと成長していることでしょう。

## 2. テストの全体像 🗺️

Laravelのテストは、大きく分けて2種類あります。

- **単体テスト (Unit Test)**: アプリケーションの非常に小さな、独立した一部分をテストします。例えば、モデルのリレーション定義が正しいか、リクエストクラスのバリデーションルールが意図通りか、といった個別のコンポーネントを対象とします。
- **機能テスト (Feature Test)**: 複数のコンポーネントが連携した、より大きな機能単位をテストします。例えば、ユーザーがフォームを送信してからデータベースに保存されるまでの一連の流れや、APIエンドポイントが正しいレスポンスを返すか、といったユーザーの操作に近い視点でテストします。

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

デフォルトでは、`DB_CONNECTION`と`DB_DATABASE`がコメントアウトされています。この状態だと、`.env`ファイルで設定されたデータベースが使用されます。

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
        $category = Category::factory()->create(['content' => 'Delivery Issues']);

        $resource = (new CategoryResource($category))->toArray(new Request());

        $this->assertSame($category->id, $resource['id']);
        $this->assertSame('Delivery Issues', $resource['content']);
    }
}
```

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
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Saya',
            'last_name' => 'Tanaka',
            'building' => 'Blue Tower',
        ]);
        $contact->setRelation('category', $category);

        $resource = (new ContactResource($contact))->toArray(new Request());

        $this->assertSame($contact->id, $resource['id']);
        $this->assertSame('Saya', $resource['first_name']);
        $this->assertSame('Tanaka', $resource['last_name']);
        $this->assertSame('Blue Tower', $resource['building']);
        $this->assertSame('Support', $resource['category']['content']);
    }
}
```

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
        $tag = Tag::factory()->create(['name' => 'important']);

        $resource = (new TagResource($tag))->toArray(new Request());

        $this->assertSame($tag->id, $resource['id']);
        $this->assertSame('important', $resource['name']);
    }
}
```

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
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
    }

    public function test_admin_page_is_inaccessible_for_guests(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_admin_page_is_accessible_for_logged_in_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }
}
```

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

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }
}
```

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
            'first_name' => 'Taro',
            'gender' => 1,
        ]);

        Contact::factory()->create(['gender' => 2]);

        $response = $this->actingAs($user)->get('/admin/contacts/export?gender=1&category_id=' . $category->id);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        $this->assertStringContainsString('Taro', $content);
        $this->assertStringNotContainsString('Jiro', $content);
    }
}
```

### 6.2 APIエンドポイント

APIのテストでは、JSON形式のリクエストを送信し、JSONレスポンスとデータベースの状態を検証します。

#### 6.2.1 Category APIのテスト

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

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment([
            'id' => $categories->first()->id,
            'content' => $categories->first()->content,
        ]);
    }
}
```

#### 6.2.2 Contact APIのテスト

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
        $category = Category::factory()->create(['content' => 'Delivery']);
        $otherCategory = Category::factory()->create(['content' => 'Other']);

        $matching = Contact::factory()->for($category)->create([
            'first_name' => 'Ken',
            'last_name' => 'Ito',
            'gender' => 1,
            'email' => 'ken@example.com',
            'created_at' => Carbon::parse('2024-02-01 09:00:00'),
        ]);
        Contact::factory()->for($otherCategory)->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 2,
            'email' => 'jane@example.com',
            'created_at' => Carbon::parse('2024-02-02 09:00:00'),
        ]);

        $tag = Tag::factory()->create();
        $matching->tags()->attach($tag);

        $response = $this->getJson('/api/contacts?keyword=Ken&gender=1&category_id=' . $category->id . '&date=2024-02-01');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
        $response->assertJsonPath('data.0.category.id', $category->id);
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_store_persists_contact_and_attaches_tags(): void
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

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertCreated();
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

    public function test_show_returns_single_contact(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Mika',
            'last_name' => 'Suzuki',
        ]);

        $response = $this->getJson('/api/contacts/' . $contact->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.category.id', $category->id);
    }

    public function test_destroy_removes_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson('/api/contacts/' . $contact->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
```

#### 6.2.3 Tag APIのテスト

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

        $response = $this->getJson('/api/tags');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $tags->first()->id,
            'name' => $tags->first()->name,
        ]);
    }

    public function test_store_creates_tag(): void
    {
        $response = $this->postJson('/api/tags', ['name' => 'priority']);

        $response->assertCreated();
        $this->assertDatabaseHas('tags', ['name' => 'priority']);
    }

    public function test_update_modifies_tag_name(): void
    {
        $tag = Tag::factory()->create(['name' => 'initial']);

        $response = $this->putJson('/api/tags/' . $tag->id, ['name' => 'updated']);

        $response->assertNoContent();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated']);
    }

    public function test_destroy_deletes_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson('/api/tags/' . $tag->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
```

## 7. テストの実行 ✅

全てのテストコードを書き終えたら、いよいよ実行です。以下のコマンドをターミナルで実行してください。

```bash
php artisan test
```

このコマンドは、`tests`ディレクトリ配下にある全てのテストを自動で探し出し、実行します。テストが全て成功すれば、緑色の文字で成功メッセージが表示されます。もし失敗したテストがあれば、赤色の文字でどのテストのどの部分で失敗したのかが詳細に表示されるので、それを元にデバッグを行います。

特定のファイルだけをテストしたい場合は、以下のようにパスを指定します。

```bash
php artisan test tests/Feature/Api/ContactControllerTest.php
```

## 8. まとめ ✨

お疲れ様でした！このチャプターでは、アプリケーションの品質を保証するための自動テストを一通り実装しました。Factoryによるテストデータの準備から、単体テスト、機能テストの作成、そして実行まで、テスト駆動開発の基本的なサイクルを体験しました。

自動テストは、一度書いてしまえば、あなたの代わりに何度でもアプリケーションの健全性をチェックしてくれる非常に強力な味方です。これにより、自信を持って機能追加やリファクタリングに臨むことができるようになります。

これにて、本確認テストの全カリキュラムは終了です。ここまでの道のりは決して平坦ではなかったと思いますが、最後までやり遂げたあなたは、間違いなく本物のエンジニアとしての大きな一歩を踏み出しました。本当にお疲れ様でした！
