<div class="modal fade" id="{{ $id ?? 'myModal' }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $size ?? '' }}">
    <div class="modal-content modern-modal">
      
      {{-- Header --}}
      <div class="modal-header modern-header">
        <!-- <h5 class="modal-title"></h5> -->

        <div class="header-content">
            <div class="icon-wrapper">
                <!-- <i :class="headerIcon"></i> -->
            </div>
            <div class="header-text text-uppercase">
                <h5 class="modal-title"></h5>
            </div>
        </div>

         <button 
            type="button" 
            class="btn-close btn-close-white" 
            data-bs-dismiss="modal" 
            aria-label="Close"
        ></button>
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
