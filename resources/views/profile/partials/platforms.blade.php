<section>
    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Platform management</h2>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-3">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Platform
                    </th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                    <th scope="row" class="pl-2 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        Spotify
                    </th>
                    <td class="py-2 {{ $isSpotifyConnected ? 'text-lime-500' : 'text-red-700' }}">
                        {{ $isSpotifyConnected ? 'Connected' : 'Disconnected' }}
                    </td>
                    <td class="py-2">
                        @unless ($isSpotifyConnected)
                            <form action="{{ route('spotifyLogin') }}" method="POST">
                                @csrf
                                <button type="submit">Connect</button>
                            </form>
                        @else
                            <form action="{{ route('spotifyDisconnect') }}" method="POST">
                                @csrf
                                @method('delete')
                                <button type="submit">Disconnect</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
