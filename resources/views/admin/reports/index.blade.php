<x-layouts.app :title="__('Reports')">
    <div class="flex max-w-4xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Reports') }}</flux:heading>
        <x-flash-status />

        <nav class="flex gap-1 text-sm">
            @foreach (['open' => __('Open'), 'resolved' => __('Taken down'), 'dismissed' => __('Dismissed')] as $value => $label)
                <a href="{{ route('admin.reports.index', ['status' => $value]) }}" @class([
                    'rounded-lg px-3 py-1.5 font-medium',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $status === $value,
                    'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $status !== $value,
                ])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="space-y-4">
            @forelse ($reports as $report)
                <article class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                    <p class="font-medium">
                        @if ($report->product)
                            <a href="{{ route('admin.products.show', $report->product->id) }}" class="underline">{{ $report->product->title }}</a>
                            <span class="text-zinc-500">· {{ '@'.$report->product->seller->sellerProfile?->username }}</span>
                        @endif
                    </p>
                    <p class="mt-1 text-zinc-500">{{ __(\App\Models\Report::REASONS[$report->reason] ?? $report->reason) }} · {{ __('by :who', ['who' => $report->reporter?->email ?? __('deleted user')]) }} · {{ $report->created_at->diffForHumans() }}</p>
                    <p class="mt-2 whitespace-pre-line">{{ $report->details }}</p>
                    @if ($report->original_url)
                        <p class="mt-1">{{ __('Original') }}: <a href="{{ $report->original_url }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all underline">{{ $report->original_url }}</a></p>
                    @endif

                    @if ($report->status === 'open')
                        <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="mt-3 flex flex-col gap-2">
                            @csrf @method('PATCH')
                            <textarea name="admin_note" rows="2" placeholder="{{ __('Note (sent to seller on takedown)') }}" aria-label="{{ __('Note') }}" class="rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                            <div class="flex gap-2">
                                <flux:button size="sm" type="submit" name="action" value="takedown" variant="danger">{{ __('Take product down') }}</flux:button>
                                <flux:button size="sm" type="submit" name="action" value="dismiss">{{ __('Dismiss') }}</flux:button>
                            </div>
                        </form>
                    @elseif ($report->admin_note)
                        <p class="mt-2 text-zinc-500">{{ __('Note') }}: {{ $report->admin_note }}</p>
                    @endif
                </article>
            @empty
                <p class="text-zinc-500">{{ __('Nothing here.') }}</p>
            @endforelse
        </div>

        {{ $reports->links() }}
    </div>
</x-layouts.app>
