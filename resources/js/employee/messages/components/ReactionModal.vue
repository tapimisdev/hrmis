<template>
    <teleport to="body">
        <transition name="fade">
            <div
                v-if="isOpen"
                class="reaction-list-modal-backdrop"
                @click.self="closeModal"
            >
                <div
                    class="reaction-list-modal"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="reaction-list-modal__header">
                        <div class="reaction-list-modal__headline">
                            <div
                                class="reaction-list-modal__badge reaction-list-modal__badge--edit"
                            >
                                <i class="fa-regular fa-face-smile"></i>
                            </div>
                            <div class="reaction-list-modal__eyebrow">
                                Reactions
                            </div>
                            <h3 class="reaction-list-modal__title">
                                Message reactions
                            </h3>
                            <p class="reaction-list-modal__subtitle">
                                All users who reacted to this message.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="reaction-list-modal__close"
                            @click="closeModal"
                            aria-label="Close dialog"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="reaction-list-modal__body">
                        <div
                            v-if="tabOptions.length"
                            class="reaction-list-modal__filters"
                        >
                            <button
                                v-for="tab in tabOptions"
                                :key="tab.key"
                                type="button"
                                class="reaction-list-modal__filter-chip"
                                :class="{
                                    'is-active': activeTab === tab.key,
                                }"
                                @click="activeTab = tab.key"
                            >
                                <span
                                    v-if="tab.key === 'all'"
                                    class="reaction-list-modal__filter-label"
                                >
                                    All
                                </span>
                                <span
                                    v-else
                                    class="reaction-list-modal__filter-label"
                                >
                                    {{ tab.emoji }}
                                </span>
                                <span class="reaction-list-modal__filter-count">
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>

                        <div
                            v-if="filteredReactions.length"
                            class="reaction-list-modal__list"
                        >
                            <div
                                v-for="(reaction, index) in filteredReactions"
                                :key="`reaction-${reaction.user_id}-${reaction.reactionKey}-${index}`"
                                class="reaction-list-modal__item"
                                :class="{
                                    'reaction-list-modal__item--highlighted':
                                        Number(reaction.user_id) ===
                                        Number(currentUserId),
                                }"
                            >
                                <img
                                    :src="reaction.profile"
                                    :alt="reaction.user_name"
                                />
                                <div class="reaction-list-modal__content">
                                    <div class="reaction-list-modal__name-row">
                                        <span>{{ reaction.user_name }}</span>
                                        <small
                                            v-if="
                                                Number(reaction.user_id) ===
                                                Number(currentUserId)
                                            "
                                            >You</small
                                        >
                                    </div>
                                    <div class="reaction-list-modal__meta">
                                        <span
                                            class="reaction-list-modal__meta-badge"
                                        >
                                            {{ reaction.emoji }}
                                            {{ reaction.reactionLabel }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="reaction-list-modal__emoji"
                                    :title="reaction.reactionLabel"
                                >
                                    {{ reaction.emoji }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="reaction-list-modal__empty">
                            No reactions yet.
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script>
export default {
    name: "ReactionModal",
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        reactions: {
            type: Array,
            default: () => [],
        },
        currentUserId: {
            type: [String, Number],
            default: null,
        },
        reactionOptions: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["close"],
    data() {
        return {
            activeTab: "all",
        };
    },
    computed: {
        normalizedReactions() {
            return this.reactions
                .map((reaction) => {
                    const reactionKey =
                        reaction?.reactionKey || reaction?.reaction || null;

                    if (!reactionKey || !reaction?.user_name) {
                        return null;
                    }

                    return {
                        user_id: reaction.user_id,
                        user_name: reaction.user_name,
                        profile: reaction.profile,
                        reactionKey,
                        emoji:
                            reaction.emoji ||
                            this.getReactionEmoji(reactionKey) ||
                            reactionKey,
                        reactionLabel: this.getReactionLabel(reactionKey),
                    };
                })
                .filter(Boolean);
        },
        reactionTabs() {
            const counts = this.normalizedReactions.reduce((acc, reaction) => {
                if (!acc[reaction.reactionKey]) {
                    acc[reaction.reactionKey] = {
                        key: reaction.reactionKey,
                        emoji: reaction.emoji,
                        count: 0,
                    };
                }

                acc[reaction.reactionKey].count += 1;
                return acc;
            }, {});

            return Object.values(counts);
        },
        tabOptions() {
            return [
                {
                    key: "all",
                    count: this.normalizedReactions.length,
                },
                ...this.reactionTabs,
            ];
        },
        filteredReactions() {
            if (this.activeTab === "all") {
                return this.normalizedReactions;
            }

            return this.normalizedReactions.filter(
                (reaction) => reaction.reactionKey === this.activeTab,
            );
        },
    },
    watch: {
        isOpen(newValue) {
            if (!newValue) {
                this.activeTab = "all";
            }
        },
        reactions() {
            if (
                this.activeTab !== "all" &&
                !this.reactionTabs.some((tab) => tab.key === this.activeTab)
            ) {
                this.activeTab = "all";
            }
        },
    },
    methods: {
        closeModal() {
            this.$emit("close");
        },
        getReactionEmoji(reactionKey) {
            return (
                this.reactionOptions.find(
                    (reaction) => reaction.key === reactionKey,
                )?.emoji || ""
            );
        },
        getReactionLabel(reactionKey) {
            return (
                this.reactionOptions.find(
                    (reaction) => reaction.key === reactionKey,
                )?.label || reactionKey
            );
        },
    },
};
</script>

<style scoped lang="scss">
.reaction-list-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(12, 16, 23, 0.56);
    backdrop-filter: blur(14px);
}

.reaction-list-modal {
    width: min(92vw, 620px);
    max-height: min(88vh, 760px);
    display: flex;
    flex-direction: column;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(
            circle at top right,
            rgba(28, 88, 246, 0.12),
            transparent 26%
        ),
        linear-gradient(180deg, rgba(49, 55, 63, 0.98), rgba(37, 42, 49, 0.99));
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
    overflow: hidden;
}

.reaction-list-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.reaction-list-modal__headline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.reaction-list-modal__badge {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.reaction-list-modal__badge--edit {
    background: rgba(28, 88, 246, 0.18);
    color: #8eb5ff;
}

.reaction-list-modal__eyebrow {
    margin-bottom: 6px;
    color: rgba(214, 222, 235, 0.62);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.68rem;
}

.reaction-list-modal__title {
    margin: 0;
    color: #f3f6fb;
    font-size: 1.1rem;
    font-weight: 800;
}

.reaction-list-modal__subtitle {
    margin: 0;
    color: rgba(214, 222, 235, 0.62);
    line-height: 1.55;
}

.reaction-list-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    color: #f3f6fb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
}

.reaction-list-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 8px 0 12px;
    overflow-y: auto;
}

.reaction-list-modal__filters {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 0 16px 14px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.reaction-list-modal__filter-chip {
    min-width: auto;
    height: 40px;
    padding: 0 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    color: rgba(214, 222, 235, 0.82);
    font-size: 0.95rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
}

.reaction-list-modal__filter-chip:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.07);
}

.reaction-list-modal__filter-chip.is-active {
    color: #f3f6fb;
    background: rgba(47, 129, 247, 0.22);
    border-color: rgba(88, 166, 255, 0.42);
}

.reaction-list-modal__filter-label {
    line-height: 1;
}

.reaction-list-modal__filter-count {
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.74rem;
}

.reaction-list-modal__list {
    display: grid;
    gap: 8px;
    padding: 0 10px;
}

.reaction-list-modal__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.04);
    transition:
        background 0.2s ease,
        border-color 0.2s ease;
}

.reaction-list-modal__item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.08);
}

.reaction-list-modal__item--highlighted {
    background: rgba(88, 166, 255, 0.1);
    border-color: rgba(88, 166, 255, 0.18);
}

.reaction-list-modal__item img {
    flex-shrink: 0;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    background: rgba(255, 255, 255, 0.1);
}

.reaction-list-modal__content {
    flex: 1;
    min-width: 0;
}

.reaction-list-modal__name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.reaction-list-modal__name-row span {
    font-size: 0.98rem;
    font-weight: 700;
    color: #f3f6fb;
    word-break: break-word;
}

.reaction-list-modal__name-row small {
    font-size: 0.75rem;
    color: rgba(79, 172, 254, 0.8);
    background: rgba(79, 172, 254, 0.15);
    padding: 2px 8px;
    border-radius: 999px;
    white-space: nowrap;
}

.reaction-list-modal__meta {
    margin-top: 4px;
    color: rgba(214, 222, 235, 0.82);
    font-size: 0.82rem;
}

.reaction-list-modal__meta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
}

.reaction-list-modal__emoji {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 1.7rem;
    line-height: 1.2;
    color: rgba(255, 255, 255, 0.9);
    font-family:
        Apple Color Emoji,
        Segoe UI Emoji,
        Segoe UI Symbol,
        sans-serif;
}

.reaction-list-modal__item--highlighted .reaction-list-modal__emoji {
    color: rgba(79, 172, 254, 1);
}

.reaction-list-modal__empty {
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px 28px;
    color: rgba(214, 222, 235, 0.6);
    font-size: 0.9rem;
    text-align: center;
}

@media (max-width: 640px) {
    .reaction-list-modal-backdrop {
        padding: 12px;
    }

    .reaction-list-modal {
        width: 100%;
        max-height: min(92vh, 760px);
    }

    .reaction-list-modal__header,
    .reaction-list-modal__body {
        padding-left: 16px;
        padding-right: 16px;
    }

    .reaction-list-modal__filters {
        gap: 8px;
        padding-left: 0;
        padding-right: 0;
    }

    .reaction-list-modal__list {
        padding-left: 0;
        padding-right: 0;
    }
}
</style>
