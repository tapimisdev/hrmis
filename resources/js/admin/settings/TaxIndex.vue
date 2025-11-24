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
                        <select
                            v-model="selectedTaxes[index]"
                            class="form-select mt-3"
                        >
                            <option value="" disabled>- CHOOSE -</option>
                            <option
                                v-for="tax in taxes"
                                :key="tax.id"
                                :value="tax.id"
                            >
                                {{ tax.name }}
                            </option>
                        </select>
                        <div v-if="errors[item.key]" class="text-danger mt-1">
                            {{ errors[item.key][0] }}
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
        url: {
            type: String,
            required: true,
        },
        taxes: {
            type: Array,
            required: true,
        },
        menu: {
            type: Object,
            required: true,
        },
        savedSettings: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        const selected = Object.keys(this.menu).map((key) => {
            return this.savedSettings[key] || "";
        });

        return {
            selectedTaxes: selected,
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
                tax_id: this.selectedTaxes[index],
            }));

            axios
                .post(this.url, payload)
                .then((response) => {
                    if (response.data.savedSettings) {
                        // update selectedTaxes if server returns updated saved settings
                        Object.keys(this.menu).forEach((key, idx) => {
                            this.selectedTaxes[idx] =
                                response.data.savedSettings[key] || "";
                        });
                    }
                    alert("success", response.data.message);
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        alert(
                            "error",
                            error.message ||
                                "An error occurred while submitting the form."
                        );
                    }
                });
        },
    },
};
</script>
