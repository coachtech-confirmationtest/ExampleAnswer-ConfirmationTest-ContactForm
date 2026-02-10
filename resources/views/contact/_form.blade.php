<!-- お名前 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            お名前
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <div class="flex gap-4">
            <input type="text" name="first_name" placeholder="例: 山田" value="{{ old('first_name') }}"
                class="flex-1 px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
            <input type="text" name="last_name" placeholder="例: 太郎" value="{{ old('last_name') }}"
                class="flex-1 px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
        </div>
        @error('first_name')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
        @error('last_name')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- 性別 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            性別
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <div class="flex gap-8 py-3">
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="gender" value="1" {{ old('gender', '1') == '1' ? 'checked' : '' }}
                    class="w-4 h-4 text-[#6b5744] border-gray-300 focus:ring-[#6b5744]" />
                <span class="ml-2 text-gray-700">男性</span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="gender" value="2" {{ old('gender', '1') == '2' ? 'checked' : '' }}
                    class="w-4 h-4 text-[#6b5744] border-gray-300 focus:ring-[#6b5744]" />
                <span class="ml-2 text-gray-700">女性</span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="radio" name="gender" value="3" {{ old('gender', '1') == '3' ? 'checked' : '' }}
                    class="w-4 h-4 text-[#6b5744] border-gray-300 focus:ring-[#6b5744]" />
                <span class="ml-2 text-gray-700">その他</span>
            </label>
        </div>
        @error('gender')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- メールアドレス -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            メールアドレス
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <input type="email" name="email" placeholder="例: test@example.com" value="{{ old('email') }}"
            class="w-full px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
        @error('email')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- 電話番号 -->
@php
    $telParts = explode('-', old('tel', ''));
    $tel1 = old('tel1', $telParts[0] ?? '');
    $tel2 = old('tel2', $telParts[1] ?? '');
    $tel3 = old('tel3', $telParts[2] ?? '');
@endphp
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            電話番号
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <div class="flex items-center gap-2">
            <input type="tel" name="tel1" id="tel1" placeholder="080" value="{{ $tel1 }}"
                maxlength="4"
                class="w-28 px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
            <span class="text-gray-500">-</span>
            <input type="tel" name="tel2" id="tel2" placeholder="1234" value="{{ $tel2 }}"
                maxlength="4"
                class="w-28 px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
            <span class="text-gray-500">-</span>
            <input type="tel" name="tel3" id="tel3" placeholder="5678" value="{{ $tel3 }}"
                maxlength="4"
                class="w-28 px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
        </div>
        <input type="hidden" name="tel" id="tel" value="{{ old('tel') }}">
        @error('tel')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- 住所 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            住所
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <input type="text" name="address" placeholder="例: 東京都渋谷区千駄ヶ谷1-2-3" value="{{ old('address') }}"
            class="w-full px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
        @error('address')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- 建物名 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            建物名
        </label>
    </div>
    <div class="col-span-2">
        <input type="text" name="building" placeholder="例: 千駄ヶ谷マンション305" value="{{ old('building') }}"
            class="w-full px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300" />
        @error('building')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- お問い合わせの種類 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            お問い合わせの種類
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <div class="relative">
            <select name="category_id" id="category-select" data-old-value="{{ old('category_id') }}"
                class="w-full px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-300 appearance-none cursor-pointer">
                <option value="" disabled {{ old('category_id') == '' ? 'selected' : '' }}>選択してください</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        @error('category_id')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- タグ -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-center">
        <label class="text-sm text-[#6b5744]">
            タグ
        </label>
    </div>
    <div class="col-span-2">
        <div id="tag-checkboxes" class="flex flex-wrap gap-4 py-3">
            <!-- タグはJavaScriptで動的に読み込まれます -->
        </div>
    </div>
</div>

<!-- お問い合わせ内容 -->
<div class="grid grid-cols-3 gap-8 mb-4">
    <div class="col-span-1 flex items-start pt-3">
        <label class="text-sm text-[#6b5744]">
            お問い合わせ内容
            <span class="text-red-500 ml-1">※</span>
        </label>
    </div>
    <div class="col-span-2">
        <textarea name="detail" placeholder="お問い合わせ内容をご記載ください" rows="6"
            class="w-full px-4 py-3 bg-[#f5f5f5] border-0 text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300 resize-none">{{ old('detail') }}</textarea>
        @error('detail')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>
</div>
