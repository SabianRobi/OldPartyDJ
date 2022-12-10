<footer class="p-4 bg-white shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800">
    <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">
        ©
        <?= date("Y") ?>
        <a href="{{ route('home') }}" class="hover:underline">{{ env('APP_NAME') }}</a>.
        All Rights Reserved.
    </span>
    <ul class="flex flex-wrap items-center mt-3 text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
        <li>
            <a href="#" class="mr-4 hover:underline md:mr-6 ">Go to the top</a>
        </li>
        <li>
            <a href="{{ route('login') }}" class="mr-4 hover:underline md:mr-6">Login</a>
        </li>
        <li>
            <a href="{{ route('register') }}" class="mr-4 hover:underline md:mr-6">Regsiter</a>
        </li>
    </ul>
</footer>
