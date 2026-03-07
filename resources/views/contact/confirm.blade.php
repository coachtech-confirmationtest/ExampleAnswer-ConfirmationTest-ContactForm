<x-guest-layout>
    <div class="bg-white min-h-screen">
        <div class="max-w-3xl mx-auto px-8 py-12">
            <h1 class="text-2xl font-serif text-[#6b5744] text-center mb-10">Confirm</h1>

            <!-- 確認画面 -->
            <form action="/contacts" method="post">
                @csrf

                <!-- お名前 -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">お名前</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $validated['first_name'] }} {{ $validated['last_name'] }}</span>
                    </div>
                </div>

                <!-- 性別 -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">性別</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">
                            @php
                                $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];
                            @endphp
                            {{ $genderLabels[$validated['gender']] ?? '' }}
                        </span>
                    </div>
                </div>

                <!-- メールアドレス -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">メールアドレス</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $validated['email'] }}</span>
                    </div>
                </div>

                <!-- 電話番号 -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">電話番号</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $validated['tel'] }}</span>
                    </div>
                </div>

                <!-- 住所 -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">住所</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $validated['address'] }}</span>
                    </div>
                </div>

                <!-- 建物名 -->
                @if (!empty($validated['building']))
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">建物名</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]">{{ $validated['building'] }}</span>
                        </div>
                    </div>
                @endif

                <!-- お問い合わせの種類 -->
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">お問い合わせの種類</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $category->content }}</span>
                    </div>
                </div>

                <!-- タグ -->
                @isset($tags)
                    @if ($tags->isNotEmpty())
                        <div class="grid grid-cols-3 border-b border-gray-200">
                            <div class="bg-[#baa999] px-6 py-4 flex items-center">
                                <span class="text-sm font-medium text-white">タグ</span>
                            </div>
                            <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                                <span class="text-[#6b5744]">{{ $tags->pluck('name')->join(', ') }}</span>
                            </div>
                        </div>
                    @endif
                @endisset

                <!-- お問い合わせ内容 -->
                <div class="grid grid-cols-3">
                    <div class="bg-[#baa999] px-6 py-4 flex items-start">
                        <span class="text-sm font-medium text-white">お問い合わせ内容</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-start">
                        <span class="text-[#6b5744] whitespace-pre-wrap">{{ $validated['detail'] }}</span>
                    </div>
                </div>

                <!-- hidden fields -->
                <input type="hidden" name="first_name" value="{{ $validated['first_name'] }}">
                <input type="hidden" name="last_name" value="{{ $validated['last_name'] }}">
                <input type="hidden" name="gender" value="{{ $validated['gender'] }}">
                <input type="hidden" name="email" value="{{ $validated['email'] }}">
                <input type="hidden" name="tel" value="{{ $validated['tel'] }}">
                <input type="hidden" name="address" value="{{ $validated['address'] }}">
                <input type="hidden" name="building" value="{{ $validated['building'] ?? '' }}">
                <input type="hidden" name="category_id" value="{{ $validated['category_id'] }}">
                @if (!empty($validated['tag_ids']))
                    @foreach ($validated['tag_ids'] as $tagId)
                        <input type="hidden" name="tag_ids[]" value="{{ $tagId }}">
                    @endforeach
                @endif
                <input type="hidden" name="detail" value="{{ $validated['detail'] }}">

                <!-- ボタン -->
                <div class="flex justify-center gap-4 mt-10">
                    <button type="submit"
                        class="px-16 py-3 bg-[#7d7470] hover:bg-[#6b5f57] border border-transparent rounded font-medium text-white transition">
                        送信
                    </button>
                    <button type="button" onclick="history.back()"
                        class="px-8 py-3 text-[#6b5744] transition">
                        修正
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
