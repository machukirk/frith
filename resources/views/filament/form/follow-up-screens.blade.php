{{--
    The screens that appear between step five and step six.

    Read-only on purpose. Each one's heading and line underneath ARE an area's
    label and description, and the statements underneath are its child options,
    so they are edited on the Options tab where the safeguards live — usage
    counts, and archive rather than delete. Editing them here would be a second
    door into the same rows without any of that.
--}}
@php
    $areas = $form->optionsIn('support_areas')->with('children')->orderBy('position')->get();
@endphp

<div class="fi-sc-section-content-ctn">
    @if ($areas->isEmpty())
        <p class="fi-sc-text text-sm text-gray-500 dark:text-gray-400">
            No areas of family life yet, so nobody sees a follow-up screen.
        </p>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($areas as $area)
                <li class="py-3 first:pt-0 last:pb-0">
                    <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        {{ $area->label }}

                        @if ($area->isArchived())
                            <span class="fi-badge rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                Archived — nobody is offered this
                            </span>
                        @endif
                    </p>

                    @if ($area->description)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $area->description }}</p>
                    @endif

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $area->children->count() }} {{ Str::plural('statement', $area->children->count()) }}
                        @if ($archived = $area->children->filter->isArchived()->count())
                            · {{ $archived }} archived
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
