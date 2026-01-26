@extends('admin.layouts.app')

@section('styles')
<style>
.id-card {
    width: 376px; 
    height: 594px;
    /* width: 204px;  
    height: 325px;  */
    position: relative;
    margin: 0 0 3px 0;
    background-color: #f8f9fa;
    overflow: hidden;
}

/* Draggable photo on the front */
.photo-id {
    width: 120px;
    height: 120px;
    background-color: #ffc107;
    line-height: 40px;
    cursor: move;
    position: absolute;
    top: 10px;
    left: 10px;
    border-radius: 4px;
    transform: translate(55px, 50px);
}

.overlay-image {
    width: 376px;
    height: 594px;
    border: 1px dotted white;
    text-align: center;
}

.custom-container {
    display: flex;              /* Two-column layout */
    justify-content: center;    /* Center columns horizontally */
    gap: 40px;                  /* Space between columns */
    padding: 20px;
}

.custom-column {
    display: flex;              /* Stack cards vertically */
    flex-direction: column;
    align-items: center;        /* Center cards in column */
    gap: 40px;                  /* Space between rotated cards */
}

.id-card-wrapper {
    width: 180px;
    height: 250px;
    position: relative;         /* To contain rotated card */
}

.id-card {
    width: 100%;
    height: 100%;
    position: absolute;
}

.rotate-card {
    transform: rotate(90deg);   /* Rotate entire wrapper */
    transform-origin: center center;
}

.id-card-bg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}


</style>
@endsection

@section('content')
<div class="container-fluid min-vh-100">
    
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="ID MAKER" subtitle="Manage or update employee's ID in this module" >
        <a href="{{ route('offset.create') }}" class="btn btn-warning py-3 px-4">
            <i class="fa-solid fa-paper-plane me-2"></i> Apply
        </a>
    </x-header-employee>

    <div class="card rounded-4 p-3">
        <div class="card-body">
            <div class="mb-3 text-uppercase fw-bold">
                <h3>ID CARD TEMPLATE CONFIGURATION</h3>
                <hr>
                <div class="d-flex justify-content-end">
                    <div class="btn btn-primary px-5 py-2" data-bs-toggle="modal" data-bs-target="#id-card-container">Generate</div>
                </div>
            </div>

            <form id="idTemplateForm"
                action="{{ route('id-maker.save_configuration') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-3">
                @csrf

                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2">Front ID Card Template</label>
                        <input type="file" name="front_image" id="front-id" class="form-control">
                        <div class="overlay-image mt-3" id="front-preview">
                            @if($latestFront)
                                <img src="{{ asset('storage/' . $latestFront->image) }}"
                                    class="img-fluid w-100 h-100"
                                    style="object-fit: cover;">
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2">Back ID Card Template</label>
                        <input type="file" name="back_image" id="back-id" class="form-control">
                        <div class="overlay-image mt-3" id="back-preview">
                            @if($latestBack)
                                <img src="{{ asset('storage/' . $latestBack->image) }}"
                                    class="img-fluid w-100 h-100"
                                    style="object-fit: cover;">
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade show" style="display: block;" id="id-card-container" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Generate ID</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div class="container custom-container">

                    <!-- First column: Back IDs -->
                    <div class="back-id custom-column">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="id-card-wrapper rotate-card">
                                <div class="id-card">
                                    @if($latestBack)
                                        <img src="{{ asset('storage/'.$latestBack->image) }}" 
                                            class="id-card-bg"
                                            alt="Back ID">
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>

                    <!-- Second column: Front IDs -->
                    <div class="front-id custom-column">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="id-card-wrapper rotate-card">
                                <div class="id-card">
                                    @if($latestFront)
                                        <img src="{{ asset('storage/'.$latestFront->image) }}" 
                                            class="id-card-bg"
                                            alt="Front ID">
                                    @endif
                                    <div class="photo-id"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Understood</button>
                <button type="button" class="btn btn-success" id="download-ids">Download All</button>
                <button type="button" class="btn btn-info" id="print-front-ids">Print Front IDs</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Draggable and resizable photo-id
    if ($.ui && $.ui.draggable && $.ui.resizable) {
        $(".photo-id").each(function() {
            $(this).draggable({
                containment: "parent"
            }).resizable({
                handles: "n, e, s, w, ne, se, sw, nw",
                minHeight: 20,
                minWidth: 20,
                maxHeight: $(this).parent().height(),
                maxWidth: $(this).parent().width(),
                resize: function(event, ui) {
                    let parent = $(this).parent();
                    if (ui.position.top + ui.size.height > parent.height()) {
                        ui.size.height = parent.height() - ui.position.top;
                    }
                    if (ui.position.left + ui.size.width > parent.width()) {
                        ui.size.width = parent.width() - ui.position.left;
                    }
                }
            });
        });
    } else {
        console.error("jQuery UI draggable or resizable is not loaded.");
    }

    // Upload front/back images via AJAX
    $('#front-id, #back-id').on('change', function () {
        let formData = new FormData($('#idTemplateForm')[0]);
        let input = this;

        $.ajax({
            url: $('#idTemplateForm').attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.front_image) {
                    $('#front-preview').html(
                        `<img src="${response.front_image}" class="img-fluid w-100 h-100" style="object-fit: cover;">`
                    );
                }
                if (response.back_image) {
                    $('#back-preview').html(
                        `<img src="${response.back_image}" class="img-fluid w-100 h-100" style="object-fit: cover;">`
                    );
                }
                input.value = '';
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            }
        });
    });
   
    // Download the entire .front-id container as one HD image
    // $('#download-ids').on('click', async function() {
    //     let front = document.querySelector('.front-id');
    //     let back = document.querySelector('.back-id');

    //     if (!front || !back) {
    //         alert('Front or back ID container not found!');
    //         return;
    //     }

    //     try {
    //         // Render each container to a canvas, ignoring elements with .outcast
    //         const frontCanvas = await html2canvas(front, { 
    //             scale: 5, 
    //             useCORS: true, 
    //             allowTaint: true, 
    //             backgroundColor: null,
    //             ignoreElements: (el) => el.classList.contains('outcast')
    //         });

    //         const backCanvas = await html2canvas(back, { 
    //             scale: 5, 
    //             useCORS: true, 
    //             allowTaint: true, 
    //             backgroundColor: null,
    //             ignoreElements: (el) => el.classList.contains('outcast')
    //         });

    //         // Create a combined canvas
    //         const combinedCanvas = document.createElement('canvas');
    //         combinedCanvas.width = Math.max(frontCanvas.width, backCanvas.width);
    //         combinedCanvas.height = frontCanvas.height + backCanvas.height;

    //         const ctx = combinedCanvas.getContext('2d');
    //         ctx.imageSmoothingEnabled = false;

    //         // Draw front on top
    //         ctx.drawImage(frontCanvas, 0, 0);
    //         // Draw back below
    //         ctx.drawImage(backCanvas, 0, frontCanvas.height);

    //         // Export as PNG
    //         combinedCanvas.toBlob(function(blob) {
    //             saveAs(blob, 'id_cards_hd.png');
    //         }, 'image/png');

    //     } catch (err) {
    //         console.error('Error generating combined ID image:', err);
    //     }
    // });

    $('#download-ids').on('click', async function() {
        let front = document.querySelector('.front-id');
        let back = document.querySelector('.back-id');

        if (!front || !back) {
            alert('Front or back ID container not found!');
            return;
        }

        try {
            // Render each container to a canvas, ignoring elements with .outcast
            const frontCanvas = await html2canvas(front, { 
                scale: 5, 
                useCORS: true, 
                allowTaint: true, 
                backgroundColor: null,
                ignoreElements: (el) => el.classList.contains('outcast')
            });

            const backCanvas = await html2canvas(back, { 
                scale: 5, 
                useCORS: true, 
                allowTaint: true, 
                backgroundColor: null,
                ignoreElements: (el) => el.classList.contains('outcast')
            });

            // Canvas size based on DOM elements
            const canvasWidth = Math.max(frontCanvas.width, backCanvas.width);
            const canvasHeight = frontCanvas.height + backCanvas.height;

            const combinedCanvas = document.createElement('canvas');
            combinedCanvas.width = canvasWidth;
            combinedCanvas.height = canvasHeight;

            const ctx = combinedCanvas.getContext('2d');
            ctx.imageSmoothingEnabled = false;

            // Center front and back horizontally
            const frontX = (canvasWidth - frontCanvas.width) / 2;
            const backX = (canvasWidth - backCanvas.width) / 2;

            // Draw front on top
            ctx.drawImage(frontCanvas, frontX, 0);

            // Draw back below front
            ctx.drawImage(backCanvas, backX, frontCanvas.height);

            // Export as PNG
            combinedCanvas.toBlob(function(blob) {
                saveAs(blob, 'id_cards_hd.png');
            }, 'image/png');

        } catch (err) {
            console.error('Error generating combined ID image:', err);
        }
    });



});

</script>
@endsection
