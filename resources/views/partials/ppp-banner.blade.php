<aside id="pppBanner" class="w-full bg-red-500 border-b border-t border-red-700">
    <div class="max-w-3xl mx-auto py-12 px-6">
        <form method="post" action="{!! $url !!}" class="text-red-100 text-lg leading-normal">
            <input type="hidden" name="_token" value="[csrf-token]">
            <p>I see you are visiting from <span class="font-semibold">{{ $country }}</span> where <b>BaseLaravel</b> might be more expensive. I want to make sure this course is <b>affordable for everyone</b> so you can write less complex, more readable Laravel applications.</p>
            <p class="mt-4">I support <a href="https://en.wikipedia.org/wiki/Purchasing_power_parity" rel="external" target="_blank" class="underline hover:text-red-200">Purchasing Power Parity</a> which allows you to buy <b>BaseLaravel</b> at a <button type="submit" class="underline hover:text-red-200">{{ $percent_off }}% discount</button>.</p>
        </form>
    </div>
</aside>
