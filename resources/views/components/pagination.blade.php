@if ($paginator->hasPages())
<div id="pagination-wrapper" class="mt-4">
    <section class="blog-pagination section">
        <div class="container">
            <div class="d-flex justify-content-center">
                <ul>
                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <li class="disabled"><span><i class="bi bi-chevron-left"></i></span></li>
                    @else
                        <li>
                            <a href="javascript:void(0)" class="ajax-page-link" data-page="{{ $paginator->currentPage() - 1 }}">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    @endif

                    {{-- Page numbers --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li><span>{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li><a href="javascript:void(0)" class="active">{{ $page }}</a></li>
                                @else
                                    <li>
                                        <a href="javascript:void(0)" class="ajax-page-link" data-page="{{ $page }}">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <li>
                            <a href="javascript:void(0)" class="ajax-page-link" data-page="{{ $paginator->currentPage() + 1 }}">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    @else
                        <li class="disabled"><span><i class="bi bi-chevron-right"></i></span></li>
                    @endif
                </ul>
            </div>
        </div>
    </section>
</div>
@endif
