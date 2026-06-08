<template>
    <div class="row mt-2 g-3 border-bottom pb-3">

        <!-- Cards -->
        <template v-if="cards && cards.length">
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
        </template>

        <!-- Empty State -->
        <div v-else class="col-12">
            <div class="fb-empty">
                <i class="fa-solid fa-chart-column fb-empty-icon"></i>
                <div class="fb-empty-title">
                    No Taxation Summary Available
                </div>
                <div class="fb-empty-text">
                    There are no employees or forecast data for this year.
                </div>
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
                    value: '0',
                    icon: 'fa-solid fa-users'
                },
                {
                    title: 'Total Gross Income',
                    value: '₱ 0.00',
                    icon: 'fa-solid fa-money-bill-trend-up'
                },
                {
                    title: 'Total Taxable Income',
                    value: '₱ 0.00',
                    icon: 'fa-solid fa-receipt'
                },
                {
                    title: 'Total Annual Tax',
                    value: '₱ 0.00',
                    icon: 'fa-solid fa-landmark'
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

.fb-empty {
    text-align: center;
    padding: 40px 20px;
    border: 1px dashed var(--bs-border-color);
    border-radius: 6px;
    background: var(--bs-body-bg);

    .fb-empty-icon {
        font-size: 3rem;
        color: var(--bs-secondary-color);
        opacity: 0.6;
        margin-bottom: 12px;
    }

    .fb-empty-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 6px;
    }

    .fb-empty-text {
        font-size: 0.9rem;
        color: var(--bs-secondary-color);
    }
}
</style>