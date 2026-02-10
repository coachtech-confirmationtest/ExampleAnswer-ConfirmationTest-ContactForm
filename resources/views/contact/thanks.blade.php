<x-guest-layout>
    <div class="relative min-h-screen flex items-center justify-center overflow-x-hidden bg-white">
        <!-- 背景の大きな "Thank you" テキスト -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none px-4">
            <h1
                class="text-[clamp(80px,15vw,250px)] font-serif text-amber-300 opacity-30 select-none whitespace-nowrap text-center max-w-full">
                Thank you
            </h1>
        </div>

        <!-- メインコンテンツ -->
        <div class="relative z-10 text-center py-12">
            <h2 class="text-2xl md:text-3xl font-serif text-amber-900 mb-12">
                お問い合わせありがとうございました
            </h2>
            <div class="flex justify-center">
                <a href="/"
                    class="px-8 py-3 bg-amber-900 text-white rounded-md hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors uppercase tracking-wide">
                    HOME
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
