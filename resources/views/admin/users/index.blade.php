<x-layouts.app :title="__('Users')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
        <x-flash-status />
        @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name or email') }}" aria-label="{{ __('Search users') }}"
                   class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="suspended" value="1" @checked(request()->boolean('suspended')) onchange="this.form.submit()"> {{ __('Suspended only') }}</label>
        </form>

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($users as $user)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4" x-data="{ open: false }">
                    <div>
                        <p class="font-medium">{{ $user->name }} <span class="text-zinc-500">&lt;{{ $user->email }}&gt;</span></p>
                        <p class="text-zinc-500">
                            {{ $user->roles->pluck('name')->implode(', ') }}
                            @if ($user->sellerProfile) · <a href="{{ route('admin.sellers.show', $user->sellerProfile) }}" class="underline">{{ '@'.$user->sellerProfile->username }}</a> ({{ $user->sellerProfile->status->value }}) @endif
                            · {{ __('joined :date', ['date' => $user->created_at->toFormattedDateString()]) }}
                        </p>
                        @if ($user->isSuspended())
                            <p class="text-red-600">{{ __('Suspended :date: :reason', ['date' => $user->suspended_at->toFormattedDateString(), 'reason' => $user->suspension_reason]) }}</p>
                        @endif
                    </div>

                    @unless ($user->isAdmin())
                        @if ($user->isSuspended())
                            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">
                                @csrf
                                <flux:button size="sm" type="submit">{{ __('Reactivate') }}</flux:button>
                            </form>
                        @else
                            <div>
                                <flux:button size="sm" variant="danger" x-on:click="open = !open">{{ __('Suspend') }}</flux:button>
                                <form x-show="open" x-cloak method="POST" action="{{ route('admin.users.suspend', $user) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="reason" required maxlength="255" placeholder="{{ __('Reason') }}" aria-label="{{ __('Reason') }}" class="rounded-lg border border-zinc-300 px-2 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                                    <button type="submit" class="text-red-600 underline">{{ __('Confirm') }}</button>
                                </form>
                            </div>
                        @endif
                    @endunless
                </div>
            @empty
                <p class="p-6 text-center text-zinc-500">{{ __('No users.') }}</p>
            @endforelse
        </div>

        {{ $users->links() }}
    </div>
</x-layouts.app>
