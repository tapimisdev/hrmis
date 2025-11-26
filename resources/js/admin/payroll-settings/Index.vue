<template>
    <div>
        <div class="row">
            <div v-for="(item, key) in menu" :key="key" class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-uppercase">
                            {{ item.label }}
                        </h5>

                        <div
                            v-for="(field, fieldKey) in item.fields"
                            :key="fieldKey"
                            class="mb-3 mt-4"
                        >
                            <label class="form-label text-uppercase">{{
                                field.label
                            }}</label>
                            <select
                                v-model="form[key][fieldKey]"
                                class="form-control"
                                :class="{
                                    'is-invalid':
                                        errors[key] && errors[key][fieldKey],
                                }"
                            >
                                <option value="">- CHOOSE -</option>
                                <option
                                    v-for="choice in field.choices"
                                    :key="choice.id"
                                    :value="choice.id"
                                >
                                    {{ choice.name }}
                                </option>
                            </select>
                            <div
                                class="error-field text-danger mt-1"
                                v-if="errors[key] && errors[key][fieldKey]"
                            >
                                {{ errors[key][fieldKey][0] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button
                class="btn btn-primary px-5 py-3 text-uppercase fw-bold"
                @click="saveForm"
            >
                Proceed
            </button>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "PayrollSettings",
    props: {
        menu: { type: Object, required: true },
        save: { type: String, required: true },
    },
    data() {
        return {
            form: {},
            errors: {},
        };
    },
    created() {
        Object.keys(this.menu).forEach((key) => {
            this.form[key] = {};
            Object.keys(this.menu[key].fields).forEach((fieldKey) => {
                const field = this.menu[key].fields[fieldKey];
                this.form[key][fieldKey] = field.selected || "";
            });
        });
    },
    methods: {
        async saveForm() {
            this.errors = {};
            try {
                const response = await axios.post(this.save, this.form);
                const res = response.data;
                if (res.status === "success") {
                    alert(res.status, res.message || "Saved successfully!");
                } else {
                    alert(res.status, res.message || "An error occurred while saving.");
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else {
                    alert(res.status, 
                        error.response?.data?.message ||
                            "An unexpected error occurred. Please try again."
                    );
                }
            }
        },
    },
};
</script>
