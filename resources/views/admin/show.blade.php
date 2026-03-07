<x-app-layout>
    <x-slot name="header">
        <form action="/logout" method="post">
            @csrf
            <button
                class="px-5 py-1.5 border border-[#ddd8d3] text-[#c4bab0] bg-white rounded hover:bg-gray-50 transition lowercase text-sm">logout</button>
        </form>
    </x-slot>

    <div class="min-h-screen bg-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-center text-2xl font-serif text-amber-900 mb-6">お問い合わせ詳細</h2>

            @php
                $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];
            @endphp

            <!-- 詳細表示 -->
            <div class="border border-gray-200 rounded overflow-hidden">
                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">お名前</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $contact->first_name }} {{ $contact->last_name }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">性別</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $genderLabels[$contact->gender] ?? '' }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">メールアドレス</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $contact->email }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">電話番号</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $contact->tel }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">住所</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $contact->address }}</span>
                    </div>
                </div>

                @if ($contact->building)
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">建物名</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]">{{ $contact->building }}</span>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-3 border-b border-gray-200">
                    <div class="bg-[#baa999] px-6 py-4 flex items-center">
                        <span class="text-sm font-medium text-white">お問い合わせの種類</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                        <span class="text-[#6b5744]">{{ $contact->category->content ?? '' }}</span>
                    </div>
                </div>

                @if(method_exists($contact, 'tags') && $contact->tags->isNotEmpty())
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">タグ</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]">{{ $contact->tags->pluck('name')->join(', ') }}</span>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-3">
                    <div class="bg-[#baa999] px-6 py-4 flex items-start">
                        <span class="text-sm font-medium text-white">お問い合わせ内容</span>
                    </div>
                    <div class="col-span-2 bg-white px-6 py-4 flex items-start">
                        <span class="text-[#6b5744] whitespace-pre-wrap">{{ $contact->detail }}</span>
                    </div>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex justify-center gap-4 mt-8">
                <a href="/admin"
                    class="px-8 py-3 bg-[#7d7470] hover:bg-[#6b5f57] border border-transparent rounded font-medium text-white transition">
                    一覧に戻る
                </a>
                <form action="/admin/contacts/{{ $contact->id }}" method="post">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-8 py-3 bg-red-500 hover:bg-red-600 border border-transparent rounded font-medium text-white transition">
                        削除
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
