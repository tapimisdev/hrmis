<template>
    <div>
        <div class="row">
            <div
                v-for="(item, index) in menuArray"
                :key="index"
                class="col-12 col-md-4"
            >
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ item.name }}</h5>

                        <!-- TABLE ID SELECT -->
                        <select
                            v-model="selectedTable[index]"
                            class="form-select mt-3"
                        >
                            <option value="">- SELECT TABLE -</option>
                            <option
                                v-for="opt in taxes"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                        <div
                            v-if="errors[item.key + '.table_id']"
                            class="text-danger mt-1"
                        >
                            {{ errors[item.key + ".table_id"][0] }}
                        </div>

                        <!-- TAX ID SELECT -->
                        <select
                            v-model="selectedTaxes[index]"
                            class="form-select mt-3"
                        >
                            <option value="">- SELECT TAX -</option>
                            <option
                                v-for="opt in taxes"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                        <div
                            v-if="errors[item.key + '.tax_id']"
                            class="text-danger mt-1"
                        >
                            {{ errors[item.key + ".tax_id"][0] }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button
                    type="button"
                    class="btn btn-primary px-4 py-2 text-uppercase fw-bold"
                    @click="save"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "tax-settings",

    props: {
        url: { type: String, required: true },
        taxes: { type: Array, required: true }, // both selects use this
        menu: { type: Object, required: true },
        savedSettings: { type: Object, default: () => ({}) },
    },

    data() {
        const keys = Object.keys(this.menu);

        return {
            selectedTable: keys.map(
                (k) => this.savedSettings[k]?.table_id || ""
            ),
            selectedTaxes: keys.map((k) => this.savedSettings[k]?.tax_id || ""),
            errors: {},
        };
    },

    computed: {
        menuArray() {
            return Object.entries(this.menu).map(([key, name]) => ({
                key,
                name,
            }));
        },
    },

    methods: {
        save() {
            this.errors = {};

            const payload = this.menuArray.map((item, index) => ({
                data_id: item.key,
                table_id: this.selectedTable[index],
                tax_id: this.selectedTaxes[index],
            }));

            axios
                .post(this.url, payload)
                .then((res) => {
                    alert("success", res.data.message);
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        alert("error", "Something went wrong.");
                    }
                });
        },
    },
};
</script>
