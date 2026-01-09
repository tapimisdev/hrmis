<template>
    <div class="feature-section mt-4">
        <div class="section-header px-2">
            <h5 class="section-title">What you can do</h5>
            <p class="section-subtitle">Quick access to your features</p>
        </div>

        <div class="cards-wrapper">
            <button
                class="scroll-btn left"
                @click="scroll(-300)"
                v-show="canScrollLeft"
                aria-label="Scroll left"
            >
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div
                class="cards-container"
                ref="container"
                @scroll="updateScrollButtons"
            >
                <div
                    v-for="(card, index) in cards"
                    :key="card.id"
                    class="card"
                    :style="{ '--card-color': card.color }"
                    @click="navigate(card.route)"
                    :class="index === 0 ? (isRegular ? 'd-block' : 'd-none') : ''"
                  >
                    <div class="card-icon">
                      <i :class="card.icon"></i>
                    </div>
                    <h6 class="card-title">{{ card.name }}</h6>
                    <p class="card-desc">{{ card.description }}</p>
                    <span v-if="card.pending > 0" class="badge">
                      {{ card.pending }} pending
                    </span>
                    <span v-else class="status">All set!</span>
                </div>

            </div>

            <button
                class="scroll-btn right"
                @click="scroll(300)"
                v-show="canScrollRight"
                aria-label="Scroll right"
            >
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "FeatureCards",
    data() {
        return {
            canScrollLeft: false,
            canScrollRight: true,
            cards: [],
        };
    },
    mounted() {
        this.fetchCards();
        this.updateScrollButtons();
        window.addEventListener("resize", this.updateScrollButtons);
    },
    beforeUnmount() {
        window.removeEventListener("resize", this.updateScrollButtons);
    },
    methods: {
        async fetchCards() {
            try {
                const response = await axios.get("/employee/get-pendings", {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.cards = response.data.data;
            } catch (error) {
                console.error("Error fetching stats:", error);
            }
        },
        scroll(amount) {
            this.$refs.container.scrollBy({ left: amount, behavior: "smooth" });
        },
        updateScrollButtons() {
            const el = this.$refs.container;
            if (el) {
                this.canScrollLeft = el.scrollLeft > 0;
                this.canScrollRight =
                    el.scrollLeft < el.scrollWidth - el.clientWidth - 5;
            }
        },
        navigate(route) {
          window.location.href = route
        }
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../../sass/variables";

.feature-section {
    margin-bottom: 1.5rem;
}

.section-header {
    margin-bottom: 1rem;

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin: 0 0 0.25rem 0;
    }

    .section-subtitle {
        font-size: 0.85rem;
        color: var(--bs-tertiary-color);
        margin: 0;
    }
}

.cards-wrapper {
    position: relative;
}

.scroll-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    color: $dark;

    &:hover {
        background: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
        transform: translateY(-50%) scale(1.05);
    }

    &.left {
        left: -12px;
    }
    &.right {
        right: -12px;
    }

    i {
        font-size: 0.9rem;
    }
}

.cards-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 0.5rem 0 1rem;
    scrollbar-width: none;
    -ms-overflow-style: none;

    &::-webkit-scrollbar {
        display: none;
    }
}

.card {
    min-width: 220px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px var(--bs-shadow-color);
    border: 1px solid var(--bs-border-color);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;

    &::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--card-color);
        border-radius: 12px 12px 0 0;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);

        &::before {
            opacity: 1;
        }
    }
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--card-color) 10%, var(--bs-light));
    color: var(--bs-primary);
    margin-bottom: 0.75rem;

    i {
        font-size: 1.5rem;
    }
}

.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.card-desc {
    font-size: 0.8rem;
    color: var(--bs-tertiary-color);
    margin: 0 0 0.75rem 0;
    line-height: 1.3;
}

.badge {
    display: inline-block;
    padding: 0.3rem 0.6rem;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-radius: 16px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status {
    font-size: 0.7rem;
    color: #10b981;
    font-weight: 600;
}

// Responsive
@media (max-width: 768px) {
    .scroll-btn {
        display: none;
    }

    .card {
        min-width: 200px;
        padding: 1rem;
    }

    .card-icon {
        width: 44px;
        height: 44px;

        i {
            font-size: 1.3rem;
        }
    }
}

@media (max-width: 576px) {
    .section-title {
        font-size: 1rem;
    }
    .section-subtitle {
        font-size: 0.8rem;
    }

    .cards-container {
        gap: 0.75rem;
    }

    .card {
        min-width: 180px;
        padding: 1rem;
    }

    .card-title {
        font-size: 0.95rem;
    }
    .card-desc {
        font-size: 0.75rem;
    }
}
</style>
