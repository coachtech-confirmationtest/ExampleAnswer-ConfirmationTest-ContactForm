<x-guest-layout>
    <div class="bg-white min-h-screen">
        <div class="max-w-3xl mx-auto px-8 py-12">
            <h1 class="text-2xl font-serif text-[#6b5744] text-center mb-10" id="page-title">Contact</h1>

            <!-- 入力フォーム -->
            <form id="contact-form" method="post">
                @csrf
                <div id="form-section">
                    @include('contact._form')

                    <!-- 確認画面ボタン -->
                    <div class="flex justify-center mt-10">
                        <button type="button"
                            class="px-16 py-3 bg-[#7d7470] hover:bg-[#6b5f57] border border-transparent rounded font-medium text-white transition"
                            id="confirm-btn">
                            確認画面
                        </button>
                    </div>
                </div>

                <!-- 確認画面 -->
                <div id="confirm-section" class="hidden">
                    <!-- お名前 -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">お名前</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-name"></span>
                        </div>
                    </div>

                    <!-- 性別 -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">性別</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-gender"></span>
                        </div>
                    </div>

                    <!-- メールアドレス -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">メールアドレス</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-email"></span>
                        </div>
                    </div>

                    <!-- 電話番号 -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">電話番号</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-tel"></span>
                        </div>
                    </div>

                    <!-- 住所 -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">住所</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-address"></span>
                        </div>
                    </div>

                    <!-- 建物名 -->
                    <div class="grid grid-cols-3 border-b border-gray-200" id="confirm-building-row">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">建物名</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-building"></span>
                        </div>
                    </div>

                    <!-- お問い合わせの種類 -->
                    <div class="grid grid-cols-3 border-b border-gray-200">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">お問い合わせの種類</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-category"></span>
                        </div>
                    </div>

                    <!-- タグ -->
                    <div class="grid grid-cols-3 border-b border-gray-200" id="confirm-tags-row">
                        <div class="bg-[#baa999] px-6 py-4 flex items-center">
                            <span class="text-sm font-medium text-white">タグ</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-center">
                            <span class="text-[#6b5744]" id="confirm-tags"></span>
                        </div>
                    </div>

                    <!-- お問い合わせ内容 -->
                    <div class="grid grid-cols-3">
                        <div class="bg-[#baa999] px-6 py-4 flex items-start">
                            <span class="text-sm font-medium text-white">お問い合わせ内容</span>
                        </div>
                        <div class="col-span-2 bg-white px-6 py-4 flex items-start">
                            <span class="text-[#6b5744] whitespace-pre-wrap" id="confirm-detail"></span>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-center gap-4 mt-10">
                        <button type="button"
                            class="px-16 py-3 bg-[#7d7470] hover:bg-[#6b5f57] border border-transparent rounded font-medium text-white transition"
                            id="submit-btn">
                            送信
                        </button>
                        <button type="button"
                            class="px-8 py-3 text-[#6b5744] transition" id="back-btn">
                            修正
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/contact/index.js'])
    @endpush
</x-guest-layout>
