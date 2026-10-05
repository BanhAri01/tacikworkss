<x-layouts.app :title="__('Sellers')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Sellers') }}</flux:heading>

        <x-flash-status />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex gap-1 text-sm">
                @foreach (\App\Enums\SellerStatus::cases() as $tab)
                    <a href="{{ route('admin.sellers.index', ['status' => $tab->value]) }}"
                       @class([
                           'rounded-lg px-3 py-1.5 font-medium',
                           'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === $status,
                           'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $tab !== $status,
                       ])>{{ $tab->label() }}</a>
                @endforeach
            </nav>

            <form method="GET" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status->value }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Username, name or email') }}" aria-label="{{ __('Search sellers') }}"
                       class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm sm:w-64 dark:border-zinc-700 dark:bg-zinc-800">
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">{{ __('Seller') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Country') }}</th>
                        <th class="px-4 py-3">{{ __('Applied') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($profiles as $profile)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $profile->display_name }}</p>
                                <p class="text-zinc-500">{{ '@'.$profile->username }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $profile->user->email }}</td>
                            <td class="px-4 py-3">{{ $profile->country }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $profile->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.sellers.show', $profile) }}" class="font-medium underline">{{ __('Review') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('No sellers here.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $profiles->links() }}
    </div>
</x-layouts.app>
