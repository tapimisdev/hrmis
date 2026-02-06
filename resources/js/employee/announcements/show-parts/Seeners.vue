<template>
    <div>
        <!-- Modal -->
        <ModalVue
            ref="seeners"
            size="modal-xl"
            headerIcon="fa-solid fa-eye text-light"
            title="Viewers"
        >
            <div class="row container-modal mb-1">
                <div class="col-md-4" v-for="seener in data" :key="seener.id">
                    <div class="seener-item p-3 ">
                        <img
                            :src="seener.profile"
                            :alt="seener.firstname + ' ' + seener.lastname"
                            class="seener-item-avatar"
                        />
                        <div class="seener-name">
                            {{ seener.firstname }} {{ seener.lastname }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="data.length === 0" class="px-3 mt-2">
                <div
                    class="d-flex justify-content-center p-3 pb-4"
                    role="alert"
                >
                    -- No viewers --
                </div>
            </div>
        </ModalVue>

        <!-- Viewers Section -->
        <div class="viewers-section">
            <div
                class="seeners"
                @click="$refs.seeners.open()"
                style="cursor: pointer"
            >
                <div class="seeners-avatars">
                    <img
                        v-for="(seener, idx) in data.slice(0, 3)"
                        :key="seener.id"
                        :src="seener.profile"
                        :alt="seener.firstname + ' ' + seener.lastname"
                        :title="seener.firstname + ' ' + seener.lastname"
                        class="seener-avatar"
                        :style="{ zIndex: 3 - idx }"
                    />
                </div>

                <span class="seeners-count"> {{ data.length }}+ viewed </span>
            </div>
        </div>
    </div>
</template>

<script>
import ModalVue from "../../../components/ModalVue.vue";

export default {
    components: { ModalVue },
    props: {
        data: {
            type: Array,
            required: true,
        },
    },
};
</script>
<style scoped lang="scss">
.container-modal {
    overflow-y: auto;

    .seener-item  {
      display: flex;
      align-items: center;

      .seener-item-avatar {
        height: 80px;
        width: 80px;
        border-radius: 12px;
        object-fit: cover;
        margin-right: 12px;
      }
      .seener-name {
          width: 100%;
          max-width: 200px;
          font-weight: bold;
          text-transform: uppercase;
          display: -webkit-box;
          -webkit-line-clamp: 1;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }

    }
}
// Viewers Section
.viewers-section {
    display: flex;
    justify-content: flex-end;
    padding: 1.5rem 0.5rem;
}

.seeners {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.seeners-avatars {
    display: flex;
    align-items: center;
}

.seener-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #fff;
    object-fit: cover;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    transition: transform 0.2s ease;

    &:not(:first-child) {
        margin-left: -12px;
    }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

.seeners-count {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
}
</style>
