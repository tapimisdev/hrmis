<template>
    <div class="tax-skeleton container-fluid pb-4">

        <!-- HEADER -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="sk-avatar"></div>

            <div class="flex-grow-1">
                <div class="sk-line w-50 mb-2"></div>
                <div class="sk-line w-25"></div>
            </div>

            <div class="text-end">
                <div class="sk-pill mb-2"></div>
                <div class="sk-line w-50 ms-auto"></div>
            </div>
        </div>

        <!-- DESCRIPTION -->
        <div class="sk-line w-75 mb-4"></div>

        <!-- EARNINGS INCLUDED -->
        <div class="border rounded p-3 mb-3">

            <div class="sk-line w-25 mb-3"></div>

            <div v-for="i in 5" :key="'check'+i" class="d-flex align-items-center mb-3">
                <div class="sk-checkbox me-2"></div>
                <div class="sk-line w-50"></div>
            </div>

            <hr>

            <div class="sk-line w-25 mb-3"></div>

            <div class="d-flex align-items-center">
                <div class="sk-checkbox me-2"></div>
                <div class="sk-line w-50"></div>
            </div>

        </div>

        <!-- OTHER EARNINGS -->
        <div class="border rounded p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="sk-line w-25"></div>
                <div class="sk-btn"></div>
            </div>

            <div v-for="i in 3" :key="'row'+i" class="row g-2 mb-3">

                <div class="col-12">
                    <div class="sk-input"></div>
                </div>

                <div class="col-4">
                    <div class="sk-input"></div>
                </div>

                <div class="col-6">
                    <div class="sk-input"></div>
                </div>

                <div class="col-2 d-flex align-items-center">
                    <div class="sk-icon"></div>
                </div>

            </div>

        </div>

    </div>
</template>

<script>
export default {
    name: "TaxEditSkeleton"
};
</script>

<style scoped>

.tax-skeleton {
    opacity: 0.9;
}

/* shimmer animation */

@keyframes shimmer {
    0% { background-position: -400px 0 }
    100% { background-position: 400px 0 }
}

.sk-line,
.sk-avatar,
.sk-pill,
.sk-checkbox,
.sk-input,
.sk-btn,
.sk-icon {

    background: linear-gradient(
        90deg,
        rgba(255,255,255,0.05) 25%,
        rgba(255,255,255,0.15) 37%,
        rgba(255,255,255,0.05) 63%
    );

    background-size: 400px 100%;
    animation: shimmer 1.4s infinite linear;
    border-radius: 6px;
}

/* elements */

.sk-line {
    height: 12px;
}

.sk-avatar {
    width: 48px;
    height: 48px;
    border-radius: 8px;
}

.sk-pill {
    width: 70px;
    height: 20px;
    border-radius: 20px;
}

.sk-checkbox {
    width: 16px;
    height: 16px;
}

.sk-input {
    height: 36px;
    border-radius: 6px;
}

.sk-btn {
    width: 36px;
    height: 36px;
    border-radius: 6px;
}

.sk-icon {
    width: 16px;
    height: 16px;
}

</style>