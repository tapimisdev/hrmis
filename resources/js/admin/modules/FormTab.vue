<template>
    <form @submit.prevent="submitForm">
        <div class="modal-body">
            <div class="mb-3" v-for="field in fields" :key="field.model">
                <label :for="field.model" class="form-label">
                    {{ field.label }} <span class="ms-1 text-danger">*</span>
                </label>
                <input
                    type="text"
                    :id="field.model"
                    class="form-control"
                    v-model="form[field.model]"
                    :class="{ 'is-invalid': errors[field.model] }"
                />
                <span class="text-danger" v-if="errors[field.model]">
                    {{ errors[field.model][0] }}
                </span>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i
                    class="fa-solid me-1"
                    :class="form.id ? 'fa-pen-to-square' : 'fa-plus'"
                ></i>
                Add
            </button>
        </div>
    </form>
</template>

<script>
import axios from "axios";

export default {
    props: {
        submit_url: {
            type: String,
            required: true,
        },
        highest_order: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            form: {
                tab_name: "",
                order: parseInt(this.highest_order) + 1,
            },
            order: parseInt(this.highest_order) + 1,
            errors: {},
            fields: [
                { label: "Name", model: "tab_name" },
                { label: "Order", model: "order" },
            ],
        };
    },
    methods: {
        submitForm() {
            this.errors = {};
            axios
                .post(this.submit_url, this.form)
                .then((res) => {
                    SuccesToast.fire({
                        title: res.data.message || "successfully added!",
                    });
                    this.order += 1;
                    const tab_name = this.form.tab_name;
                    this.form = {
                        tab_name: "",
                        tab_slug: "",
                        order: this.order,
                    };
                    this.$emit("formSubmitted", tab_name);
                })
                .catch((error) => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else {
                        ErrorToast.fire({
                            title:
                                error.response?.data?.error ||
                                error.response?.data?.message ||
                                "An error occurred",
                        });
                    }
                });
        },
    },
};
</script>
