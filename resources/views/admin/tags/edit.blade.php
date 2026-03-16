<x-app-layout>
    <x-slot name="header">
        <form action="/logout" method="post">
            @csrf
            <button
                class="px-5 py-1.5 border border-[#ddd8d3] text-[#c4bab0] bg-white rounded hover:bg-gray-50 transition lowercase text-sm">logout</button>
        </form>
    </x-slot>

    <div class="min-h-screen bg-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">
            <h2 class="text-center text-2xl font-serif text-amber-900 mb-6">タグ編集</h2>

            <form action="/admin/tags/{{ $tag->id }}" method="post" class="bg-[#f9f6f2] rounded px-6 py-6">
                @csrf
                @method('PUT')
                <label class="block text-sm text-[#6b5744] mb-2" for="tag-name-input">タグ名</label>
                <input type="text" id="tag-name-input" name="name" value="{{ old('name', $tag->name) }}"
                    class="w-full px-4 py-2 bg-white border border-[#ddd8d3] rounded text-gray-700 placeholder-[#c4bab0] focus:outline-none focus:border-amber-500" />
                @error('name')
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
                <div class="flex items-center gap-3 mt-4">
                    <a href="/admin"
                        class="px-6 py-2 bg-[#e8ddd2] text-[#9a938c] rounded hover:bg-[#ddd2c7] inline-block">
                        戻る
                    </a>
                    <button type="submit"
                        class="px-6 py-2 bg-[#7d7470] text-white rounded hover:bg-[#6b5f57]">
                        更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
