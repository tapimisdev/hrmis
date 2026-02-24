@props([
'icon' => 'fa-solid fa-eye',
'id' => 'myModal',
'title' => 'View',
'size' => 'modal-lg',
])
<div class="modal fade" id="{{ $id ?? 'myModal' }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $size ?? '' }}">
    <div class="modal-content modern-modal">

      {{-- Header --}}
      <div class="modal-header modern-header">
        <div class="header-content border-bottom pb-3">
          <div class="icon-wrapper">
            <i class="{{ $icon }} text-light"></i>
          </div>
          <div class="header-text text-uppercase">
            <h5 class="modal-title">{{ $title }}</h5>
          </div>
        </div>

        <button
          type="button"
          class="btn-close btn-close-white btn-close-action"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>

      {{-- Body --}}
      <div class="modal-body">
        {{ $slot }}
      </div>

      {{-- Footer --}}
      @if(isset($footer))
      <div class="modal-footer">
        {{ $footer }}
      </div>
      @endif

    </div>
  </div>
</div>