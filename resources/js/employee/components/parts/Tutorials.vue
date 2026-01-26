<template>
    <div
        class="widget-container"
        ref="tutorialEl"
        :style="isMobile ? { left: '50%', top: '20%', transform: 'translate(-50%, -50%)' } : { left: tutorialPos.x + 'px', top: tutorialPos.y + 'px' }"
        :class="{ 'dark-mode': isDarkMode }"
    >
        <div class="widget-header text-uppercase" @mousedown="startDrag">
            <div class="d-flex align-items-center">
                <div
                    style="width: 30px; height: 30px"
                    class="bg-warning rounded-2 me-3 d-flex justify-content-center align-items-center"
                >
                    <i class="fa-solid fa-video"></i>
                </div>
                <div class="fw-bold text-warning text-uppercase">Tutorial</div>
                <button
                    class="btn-close"
                    @click="closeTutorial"
                    aria-label="Close"
                ></button>
            </div>
        </div>
        <div class="widget-content">
            <iframe
                src="https://player.vimeo.com/video/1158272662?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                style="position:absolute;top:0;left:0;width:100%;height:100%;"
                title="ORBIT ESS">
            </iframe>
        </div>
    </div>
</template>

<script>
const TUTORIAL_POS_KEY = "tutorial_pos";

export default {
    name: "Tutorial",
    props: {
        isDarkMode: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            tutorialPos: { x: 100, y: 100 },
            isMobile: false,
            dragging: false,
            dragStart: { x: 0, y: 0 },
        };
    },
    mounted() {
        this.isMobile = window.innerWidth <= 767;
        if (!this.isMobile) {
            this.tutorialPos = JSON.parse(
                localStorage.getItem(TUTORIAL_POS_KEY) || '{"x":100,"y":100}',
            );
        }
        window.addEventListener('resize', this.updateIsMobile);
    },
    beforeUnmount() {
        document.removeEventListener("mousemove", this.onDrag);
        document.removeEventListener("mouseup", this.stopDrag);
        window.removeEventListener('resize', this.updateIsMobile);
    },
    methods: {
        updateIsMobile() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 767;
            if (wasMobile && !this.isMobile) {
                // Became desktop, load saved position
                this.tutorialPos = JSON.parse(
                    localStorage.getItem(TUTORIAL_POS_KEY) || '{"x":100,"y":100}',
                );
            }
        },
        closeTutorial() {
            localStorage.setItem("show_tutorials", "false");
            this.$emit("close");
        },
        startDrag(e) {
            if (this.isMobile) return;
            this.dragging = true;
            this.dragStart = {
                x: e.clientX - this.tutorialPos.x,
                y: e.clientY - this.tutorialPos.y,
            };
            document.addEventListener("mousemove", this.onDrag);
            document.addEventListener("mouseup", this.stopDrag);
        },
        onDrag(e) {
            if (!this.dragging) return;

            const el = this.$refs.tutorialEl;
            if (!el) return;

            const rect = el.getBoundingClientRect();

            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let newX = e.clientX - this.dragStart.x;
            let newY = e.clientY - this.dragStart.y;

            // Clamp X
            newX = Math.max(0, Math.min(newX, viewportWidth - rect.width));

            // Clamp Y
            newY = Math.max(0, Math.min(newY, viewportHeight - rect.height));

            this.tutorialPos.x = newX;
            this.tutorialPos.y = newY;
        },
        stopDrag() {
            this.dragging = false;
            document.removeEventListener("mousemove", this.onDrag);
            document.removeEventListener("mouseup", this.stopDrag);
            if (!this.isMobile) {
                localStorage.setItem(TUTORIAL_POS_KEY, JSON.stringify(this.tutorialPos));
            }
        },
    },
};
</script>

<style lang="scss" scoped>
@media (max-width: 767.98px) {
    .widget-container {
        min-width: 280px !important;
        max-width: 90vw !important;
    }
}

.widget-container {
    position: fixed;
    min-width: 420px;
    max-width: 400px; /* Fixed from 40px to 400px */
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 999; /* Kept lower than Notes (10000) to avoid overlap issues */
    overflow: hidden;

    &.dark-mode {
        background-color: #343a40;
        border-color: #495057;
        color: #fff;
    }
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background-color: inherit !important;
    border-bottom: 1px solid #ddd;
    cursor: move;
    font-weight: 600;
    position: relative;

    .dark-mode & {
        background-color: inherit !important;
        border-bottom-color: #6c757d;
        color: #fff;
    }

    .btn-close {
        position: absolute;
        right: 14px;
        font-size: 0.8rem;
    }
}
</style>