@extends('adminmodule::layouts.master')

@section('title', translate('gallery'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-3">
                <div>
                    <h2 class="page-title mb-1">{{ translate('gallery') }}</h2>
                    <p class="text-muted mb-0">{{ translate('manage_uploaded_images_used_across_mstoo') }}</p>
                </div>
                @if($can_edit)
                    <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
                        <span class="material-icons">upload</span> {{ translate('upload_media') }}
                    </button>
                @endif
            </div>

            @include('businesssettingsmodule::admin.system-setup._nav')

            <div class="card mstoo-notify-card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-8">
                            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ translate('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-control">
                                <option value="">{{ translate('all') }}</option>
                                @foreach(['jpg','png','webp','gif'] as $type)
                                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn--primary w-100" type="submit">{{ translate('filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3 mb-3">
                @forelse($media as $item)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <div class="card h-100 mstoo-gallery-card" role="button"
                             data-filename="{{ $item['filename'] }}"
                             data-url="{{ $item['url'] }}"
                             data-size="{{ $item['size_label'] }}"
                             data-dimensions="{{ ($item['width'] ?? '-') }} × {{ ($item['height'] ?? '-') }}"
                             data-date="{{ $item['uploaded_at'] }}"
                             data-refs="{{ implode(', ', $item['references']) }}"
                             data-bs-toggle="modal" data-bs-target="#mediaPreviewModal">
                            <img src="{{ $item['thumb_url'] }}" alt="{{ $item['filename'] }}" class="card-img-top" style="height:140px;object-fit:cover;">
                            <div class="card-body py-2">
                                <div class="small text-truncate" title="{{ $item['filename'] }}">{{ $item['filename'] }}</div>
                                <div class="text-muted small">{{ $item['size_label'] }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card card-body text-center text-muted">{{ translate('no_media_uploaded_yet') }}</div>
                    </div>
                @endforelse
            </div>

            <div class="d-flex justify-content-end">{{ $media->links() }}</div>
        </div>
    </div>

    <div class="modal fade" id="uploadMediaModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.system-setup.gallery.upload') }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('upload_media') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" multiple required>
                    <small class="text-muted d-block mt-2">{{ translate('jpg_png_webp_gif_max_2mb') }}</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('close') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('upload') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="mediaPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="media-filename"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="media-preview" class="img-fluid rounded mb-3" alt="">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ translate('file_size') }}</dt><dd class="col-sm-9" id="media-size"></dd>
                        <dt class="col-sm-3">{{ translate('dimensions') }}</dt><dd class="col-sm-9" id="media-dimensions"></dd>
                        <dt class="col-sm-3">{{ translate('uploaded') }}</dt><dd class="col-sm-9" id="media-date"></dd>
                        <dt class="col-sm-3">{{ translate('usage') }}</dt><dd class="col-sm-9" id="media-refs"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" id="copy-media-url">{{ translate('copy_url') }}</button>
                    @if($can_edit)
                        <form method="POST" id="delete-media-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger" onclick="return confirm('{{ translate('are_you_sure') }}?')">{{ translate('delete') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        var deleteBase = @json(url('/admin/system-setup/gallery'));
        $('#mediaPreviewModal').on('show.bs.modal', function (event) {
            var card = $(event.relatedTarget);
            var filename = card.data('filename');
            $('#media-filename').text(filename);
            $('#media-preview').attr('src', card.data('url'));
            $('#media-size').text(card.data('size'));
            $('#media-dimensions').text(card.data('dimensions'));
            $('#media-date').text(card.data('date'));
            $('#media-refs').text(card.data('refs') || '{{ translate('not_in_use') }}');
            $('#copy-media-url').data('url', card.data('url'));
            $('#delete-media-form').attr('action', deleteBase + '/' + encodeURIComponent(filename));
        });
        $('#copy-media-url').on('click', function () {
            navigator.clipboard.writeText($(this).data('url'));
            toastr.success('{{ translate('copied') }}');
        });
    </script>
@endpush
