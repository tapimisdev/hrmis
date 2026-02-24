<template>
    <div
        class="modal fade"
        id="printableModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content modern-modal">
                <!-- Header -->
                <div class="modal-header modern-header border-bottom">
                    <div class="header-content mb-0 d-flex align-items-center">
                        <div class="icon-wrapper me-2">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">Print View DTR</h5>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <!-- Content -->
                <div class="modal-body" ref="printArea">
                    <slot></slot>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button
                        class="btn py-2 px-4 btn-primary"
                        type="button"
                        @click="printModalBody"
                    >
                        Print
                    </button>

                    <button
                        class="btn py-2 px-4 btn-danger"
                        type="button"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PrintableModal",
    methods: {
        open() {
            $("#printableModal").modal("show");
        },

        printModalBody() {
            const el = this.$refs.printArea;
            if (!el) return;

            const content = el.innerHTML;

            // hidden iframe
            const iframe = document.createElement("iframe");
            iframe.style.position = "fixed";
            iframe.style.right = "0";
            iframe.style.bottom = "0";
            iframe.style.width = "0";
            iframe.style.height = "0";
            iframe.style.border = "0";
            document.body.appendChild(iframe);

            const win = iframe.contentWindow;
            const doc = win.document;

            const styles = Array.from(
                document.querySelectorAll('link[rel="stylesheet"]'),
            )
                .map((link) => `<link rel="stylesheet" href="${link.href}">`)
                .join("\n");

            const inlineStyles = Array.from(document.querySelectorAll("style"))
                .map((style) => `<style>${style.innerHTML}</style>`)
                .join("\n");

            const printCss = `
        <style>
          @page { size: A4 landscape; margin: 6mm; }

          html, body {
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }

          .no-print { display: none !important; }

          .container { max-width: 100% !important; padding: 0 !important; }
          .card { border: none !important; box-shadow: none !important; }

          /* FORCE 2 columns in print */
          .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 4mm !important;
            align-items: flex-start !important;
          }
          .col-12.col-lg-6 {
            flex: 0 0 calc(50% - 2mm) !important;
            max-width: calc(50% - 2mm) !important;
            width: calc(50% - 2mm) !important;
          }

          .table-responsive { overflow: visible !important; }

          table { width: 100% !important; page-break-inside: auto; }
          tr { page-break-inside: avoid; page-break-after: auto; }
          thead { display: table-header-group; }

          /* a4 safety */
          .dtr-table td, .dtr-table th { font-size: 0.70rem !important; }
          .top-info-table td, .top-info-table th { font-size: 0.72rem !important; }

          #print-root { display: block; }
        </style>
      `;

            doc.open();
            doc.write(`
        <!doctype html>
        <html>
          <head>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Print</title>
            ${styles}
            ${inlineStyles}
            ${printCss}
          </head>
          <body>
            <div id="print-root">${content}</div>
          </body>
        </html>
      `);
            doc.close();

            // attach script safely (NO <script> string)
            const script = doc.createElement("script");
            script.text = `
        (function () {
          function fitToOnePage() {
            const root = document.getElementById('print-root');
            if (!root) return;

            // reset zoom
            document.body.style.zoom = 1;

            // viewport
            const vw = document.documentElement.clientWidth;
            const vh = document.documentElement.clientHeight;

            // content
            const rect = root.getBoundingClientRect();
            const cw = rect.width || 1;
            const ch = rect.height || 1;

            // scale (never upscale)
            const scale = Math.min(1, vw / cw, vh / ch);

            document.body.style.zoom = scale.toFixed(3);
          }

          // Some browsers need a tick after styles load
          setTimeout(() => {
            fitToOnePage();
            setTimeout(() => {
              window.focus();
              window.print();
            }, 80);
          }, 50);
        })();
      `;
            doc.body.appendChild(script);

            // cleanup after printing
            win.onafterprint = () => {
                setTimeout(() => {
                    if (iframe && iframe.parentNode)
                        iframe.parentNode.removeChild(iframe);
                }, 200);
            };
        },
    },
};
</script>

<style scoped>
.badge {
    font-size: 0.85rem;
}
.table {
    font-size: 0.9rem;
}
</style>
