<x-app-layout>
    <x-slot name="header">
        <form action="/logout" method="post">
            @csrf
            <button
                class="px-5 py-1.5 border border-[#ddd8d3] text-[#c4bab0] bg-white rounded hover:bg-gray-50 transition lowercase text-sm">logout</button>
        </form>
    </x-slot>

    <div class="min-h-screen bg-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Adminタイトル -->
            <h2 class="text-center text-2xl font-serif text-amber-900 mb-6">Admin</h2>

            <!-- 検索フォーム -->
            <div class="mb-4">
                <form class="flex flex-wrap items-center gap-3" action="/admin" method="get">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="keyword" value="{{ request('keyword') }}"
                            placeholder="名前やメールアドレスを入力してください"
                            class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-gray-700 placeholder-[#c4bab0] focus:outline-none focus:border-amber-500" />
                    </div>
                    <div class="min-w-[100px]">
                        <select name="gender"
                            class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-[#9a938c] focus:outline-none focus:border-amber-500">
                            <option value="0" {{ request('gender') == '0' || !request('gender') ? 'selected' : '' }}>性別</option>
                            <option value="1" {{ request('gender') == '1' ? 'selected' : '' }}>男性</option>
                            <option value="2" {{ request('gender') == '2' ? 'selected' : '' }}>女性</option>
                            <option value="3" {{ request('gender') == '3' ? 'selected' : '' }}>その他</option>
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <select name="category_id"
                            class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-[#9a938c] focus:outline-none focus:border-amber-500">
                            <option value="">お問い合わせの種類</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->content }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[130px]">
                        <input type="date" name="date" value="{{ request('date') }}"
                            placeholder="年/月/日"
                            class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-[#9a938c] focus:outline-none focus:border-amber-500" />
                    </div>
                    <div>
                        <button type="submit" class="px-6 py-2 bg-[#82746a] text-white rounded hover:bg-[#6b5f57]">
                            検索
                        </button>
                    </div>
                    <div>
                        <a href="/admin"
                            class="px-6 py-2 bg-[#e8ddd2] text-[#9a938c] rounded hover:bg-[#ddd2c7] inline-block">
                            リセット
                        </a>
                    </div>
                    <div>
                        <a href="/contacts/export?{{ http_build_query(request()->query()) }}"
                            class="px-6 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 inline-block">
                            エクスポート
                        </a>
                    </div>
                    <!-- ページネーション -->
                    <div class="flex items-center">
                        {{ $contacts->appends(request()->query())->links() }}
                    </div>
                </form>
            </div>

            <!-- テーブル -->
            <div class="bg-white rounded overflow-hidden border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#a89e94]">
                            <th class="px-6 py-3 text-left text-sm font-medium text-white">お名前</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-white">性別</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-white">メールアドレス</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-white">お問い合わせの種類</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-white">タグ</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-white"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($contacts as $contact)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $contact->first_name }} {{ $contact->last_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @php
                                        $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];
                                    @endphp
                                    {{ $genderLabels[$contact->gender] ?? '' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $contact->email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $contact->category->content ?? '' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if(method_exists($contact, 'tags'))
                                        @foreach ($contact->tags as $tag)
                                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1">{{ $tag->name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="/admin/contacts/{{ $contact->id }}"
                                        class="text-amber-600 hover:text-amber-800">詳細</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">データがありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- タグ管理 -->
            @isset($tags)
            <div class="mt-12 bg-white rounded border border-gray-200 p-6">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                    <h3 class="text-lg font-semibold text-[#6b5744]">タグ管理</h3>
                    <p class="text-sm text-gray-500">問い合わせフォームで選択できるタグを追加・編集できます</p>
                </div>

                <!-- タグ追加フォーム -->
                <form action="/admin/tags" method="post" class="bg-[#f9f6f2] rounded px-4 py-4">
                    @csrf
                    <label class="block text-sm text-[#6b5744] mb-2" for="tag-name-input">タグ名</label>
                    <input type="text" id="tag-name-input" name="name" placeholder="例: 新機能の要望"
                        class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-gray-700 placeholder-[#c4bab0] focus:outline-none focus:border-amber-500" />
                    @error('name')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                    <div class="flex items-center gap-3 mt-4">
                        <button type="submit"
                            class="px-6 py-2 bg-[#7d7470] text-white rounded hover:bg-[#6b5f57]">
                            追加
                        </button>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-[#f7f2ed] text-left">
                                <th class="px-6 py-3 text-sm font-medium text-[#6b5744]">タグ名</th>
                                <th class="px-6 py-3 text-sm font-medium text-[#6b5744] text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tags as $tag)
                                <tr class="border-b border-gray-100">
                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        {{ $tag->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right">
                                        <a href="/admin/tags/{{ $tag->id }}/edit"
                                            class="px-3 py-1 text-xs bg-[#7d7470] text-white rounded hover:bg-[#6b5f57] inline-block">
                                            編集
                                        </a>
                                        <form action="/admin/tags/{{ $tag->id }}" method="post" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                                削除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">
                                        タグがありません
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endisset

        </div>
    </div>
</x-app-layout>
