{{--
    Record history (Aura\Base\Traits\RecordsActivity) on the record page, newest first: the actor's
    24px avatar, one sentence ("Enes Emini moved Stage from Qualified to Proposal", values as the page
    shows them), the time on the right (under the sentence on phones), a 1px line joining the avatars.
    Eight rows, then "Show all". The oldest row is the record's creation; a record from before the
    history existed gets it from its own created_at and author.
--}}
@props(['model', 'limit' => 8])

@php
    $max = 100;
    $rows = $model->activities()->with('user')->limit($max)->get();

    if ($rows->count() < $max && ! $rows->contains('event', 'created') && $model->created_at) {
        $created = new \Aura\Base\Models\Activity(['event' => 'created', 'user_id' => $model->user_id ?? null, 'created_at' => $model->created_at]);
        $created->setRelation('user', ($model->user_id ?? null) ? app(config('aura.resources.user'))->newQuery()->find($model->user_id) : null);
        $rows->push($created);
    }

    $resourceName = \Illuminate\Support\Str::lower(__($model->singularName()));
    $collapsed = $rows->count() > $limit;
    $now = now();
@endphp

@if ($rows->isNotEmpty())
<section {{ $attributes->merge(['class' => 'aura-activity']) }} x-data="{ all: false }" data-record-activity>
    <div class="flex items-center mt-2 mb-4 min-h-8">
        <h2 class="aura-section-title">{{ __('Activity') }}</h2>
    </div>

    <ol class="aura-activity-list" role="list">
        @foreach ($rows as $index => $row)
            @php
                $fieldName = $row->field ? __($model->fieldBySlug($row->field)['name'] ?? $row->field) : null;
                $old = $row->field ? \Aura\Base\Models\Activity::displayValue($model, $row->field, $row->old) : '';
                $new = $row->field ? \Aura\Base\Models\Activity::displayValue($model, $row->field, $row->new) : '';
                $isStatus = $row->field && $model->fieldClassBySlug($row->field) instanceof \Aura\Base\Fields\Status;
                $at = $row->created_at;
                $time = ! $at ? '' : ($at->gt($now->copy()->subDays(7)) ? $at->diffForHumans() : $at->isoFormat($at->year === $now->year ? 'MMM D' : 'll'));
            @endphp
            <li @class(['aura-activity-item', 'aura-activity-item-tail' => $collapsed && $index === $limit - 1])
                @if ($collapsed && $index === $limit - 1) :class="{ 'aura-activity-item-last': ! all }" @endif
                @if ($collapsed && $index >= $limit) x-show="all" x-cloak @endif
                data-activity-event="{{ $row->event }}">
                <span class="aura-activity-avatar">
                    @if ($row->user)
                        <x-aura::avatar :record="$row->user" size="sm" />
                    @else
                        <x-aura::avatar :name="__('Someone')" shape="circle" size="sm" />
                    @endif
                </span>
                <div class="aura-activity-body">
                    <p class="aura-activity-text">
                        <span class="aura-activity-strong">{{ $row->user?->name ?? __('Someone') }}</span>
                        @switch($row->event)
                            @case('created')
                                {{ __('created this :resource', ['resource' => $resourceName]) }}
                                @break
                            @case('deleted')
                                {{ __('deleted this :resource', ['resource' => $resourceName]) }}
                                @break
                            @default
                                @if ($old === '')
                                    {{ __('set') }} <span class="aura-activity-strong">{{ $fieldName }}</span> {{ __('to') }} <span class="aura-activity-value">{!! $new !!}</span>
                                @elseif ($new === '')
                                    {{ __('cleared') }} <span class="aura-activity-strong">{{ $fieldName }}</span>
                                @else
                                    {{ $isStatus ? __('moved') : __('changed') }} <span class="aura-activity-strong">{{ $fieldName }}</span>
                                    {{ __('from') }} <span class="aura-activity-value">{!! $old !!}</span>
                                    {{ __('to') }} <span class="aura-activity-value">{!! $new !!}</span>
                                @endif
                        @endswitch
                    </p>
                    @if ($at)
                        <time class="aura-activity-time" datetime="{{ $at->toIso8601String() }}" title="{{ $at->isoFormat('LLLL') }}">{{ $time }}</time>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @if ($collapsed)
        <button type="button" class="aura-activity-more" x-show="! all" x-on:click="all = true">
            {{ __('Show all') }} <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $rows->count() }}</span>
        </button>
    @endif
</section>
@endif
