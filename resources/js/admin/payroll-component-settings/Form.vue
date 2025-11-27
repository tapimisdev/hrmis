<template>
    <div class="container">
        <form @submit.prevent="submitForm">
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="mb-2">
                                Icon <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="fa-solid fa-money-bill"
                                v-model="form.icon"
                            />
                            <div class="error-field">{{ errors.icon }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="mb-2">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Enter name"
                                v-model="form.name"
                            />
                            <div class="error-field">{{ errors.name }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="mb-2">
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="unique-slug"
                                v-model="form.slug"
                            />
                            <div class="error-field">{{ errors.slug }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="mb-2">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" v-model="form.type">
                                <option disabled value="">Select Type</option>
                                <option value="earnings">Earnings</option>
                                <option value="taxes">Taxes</option>
                            </select>
                            <div class="error-field">{{ errors.type }}</div>
                        </div>
                    </div>
                </div>

                <div
                    class="card-footer border-top bg-transparent pt-4 d-flex justify-content-end"
                >
                    <button
                        type="submit"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold"
                    >
                        Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "PayrollComponentForm",
    props: {
        saveUrl: { type: String, required: true },
    },

    data() {
        return {
            form: {
                icon: "",
                name: "",
                slug: "",
                type: "",
            },
            errors: {},
        };
    },

    methods: {
        async submitForm() {
            this.errors = {};
            try {
                const response = await axios.post(this.saveUrl, this.form);
                const res = response.data;
                if(res.status == 'success') {
                  alert('success', res.message, res.redirect);
                  this.form = {}
                } else {
                  alert('error', res.message);
                }

            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
            }
        },
    },
};
</script>
