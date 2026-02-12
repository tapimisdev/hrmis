<template>
    <div class="row mt-2 g-3 border-bottom pb-3">
        <div class="col-md-3" v-for="card in cards" :key="card.title">
            <div class="fb-card">

                <div class="fb-content">
                    <div class="fb-title">
                        {{ card.title }}
                    </div>

                    <div class="fb-value">
                        {{ card.value }}
                    </div>
                </div>

                <i :class="card.icon + ' fb-icon'"></i>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        cards: {
            type: Array,
            required: true,
            default: () => [
                {
                    title: 'Employee Included',
                    value: '79',
                    icon: 'fa-solid fa-users'
                },
                {
                    title: 'Total Tax Forecast',
                    value: '₱ 1,250,000.00',
                    icon: 'fa-solid fa-chart-line'
                },
                {
                    title: 'Total Projected Tax Due',
                    value: '245',
                    icon: 'fa-solid fa-file-lines'
                },
                {
                    title: 'Variance vs Withheld YTD',
                    value: '₱ +85,000.00',
                    icon: 'fa-solid fa-scale-balanced'
                }

            ]
        }
    }
}
</script>

<style lang="scss" scoped>
.fb-card {
    background: var(--bs-body-bg);
    color: var(--bs-body-color);

    border: 1px solid var(--bs-border-color);
    border-radius: 4px;

    padding: 16px 18px;
    min-height: 120px;

    position: relative;
    overflow: hidden;

    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
    }
}

.fb-content {
    position: relative;
    z-index: 2;
}

.fb-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--bs-secondary-color); // soft gray text
    margin-bottom: 6px;
}

.fb-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--bs-body-color);
    letter-spacing: 0.3px;
}

.fb-icon {
    position: absolute;
    right: -12px;
    bottom: -18px;

    font-size: 6rem;
    color: var(--bs-body);
    opacity: 0.08;

    z-index: 1;
}
</style>