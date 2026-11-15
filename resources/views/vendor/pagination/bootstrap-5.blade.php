@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between flex items-center justify-between w-full" aria-label="Pagination">
        <div class="d-flex justify-content-between flex-fill d-sm-none flex sm:hidden justify-between w-full items-center">
            <ul class="pagination flex items-center gap-2 list-none p-0 m-0">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled opacity-50 cursor-not-allowed" aria-disabled="true">
                        <span class="page-link px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-slate-50 text-slate-400">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled opacity-50 cursor-not-allowed" aria-disabled="true">
                        <span class="page-link px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-slate-50 text-slate-400">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between hidden sm:flex items-center justify-between w-full gap-4">
            <div>
                <p class="small text-muted text-xs text-slate-500 font-medium m-0">
                    Affichage de
                    <span class="fw-semibold font-bold text-slate-800">{{ $paginator->firstItem() }}</span>
                    à
                    <span class="fw-semibold font-bold text-slate-800">{{ $paginator->lastItem() }}</span>
                    sur
                    <span class="fw-semibold font-bold text-slate-800">{{ $paginator->total() }}</span>
                    résultats
                </p>
            </div>

            <div>
                <ul class="pagination flex items-center gap-1.5 list-none p-0 m-0">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled opacity-50 cursor-not-allowed" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg border border-slate-200 bg-slate-50 text-slate-400 shadow-sm" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled opacity-50" aria-disabled="true"><span class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold text-slate-400">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg bg-blue-600 text-white shadow-md shadow-blue-500/20">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition shadow-sm" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled opacity-50 cursor-not-allowed" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link w-9 h-9 flex items-center justify-center text-xs font-bold rounded-lg border border-slate-200 bg-slate-50 text-slate-400 shadow-sm" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
