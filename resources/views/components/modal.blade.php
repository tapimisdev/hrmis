<div class="modal fade" id="{{ $id ?? 'myModal' }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered {{ $size ?? '' }}">
    <div class="modal-content">
      
      {{-- Header --}}
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
