<template>
    <div class="position-relative">
        <div class="row mb-3">
            <div
                v-for="field in fields"
                :key="field.key"
                :class="field.col ?? 'col-12 col-md-3 mb-3'"
            >
                <label class="form-label fw-bold">
                    {{ field.label }}
                    <span
                        v-if="errors?.[field.key]"
                        class="text-danger small ms-1"
                    >
                        ({{ errors[field.key][0] }})
                    </span>
                </label>

                <!-- SELECT -->
                <select
                    v-if="field.type === 'select'"
                    class="form-select"
                    :class="{ 'is-invalid': errors?.[field.key] }"
                    :value="form[field.key]"
                    @change="onChange(field, $event.target.value)"
                >
                    <option disabled value="">
                        {{ field.placeholder ?? "-- CHOOSE --" }}
                    </option>

                    <option
                        v-for="opt in field.options ?? []"
                        :key="String(opt.value)"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>

                <!-- MONTH -->
                <input
                    v-else-if="field.type === 'month'"
                    type="month"
                    class="form-control"
                    :class="{ 'is-invalid': errors?.[field.key] }"
                    :value="form[field.key]"
                    @change="onChange(field, $event.target.value)"
                />

                <!-- TEXT -->
                <input
                    v-else
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors?.[field.key] }"
                    :value="form[field.key]"
                    @input="onChange(field, $event.target.value)"
                />
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "FilterSearch",

    props: {
        /**
         * Fields definition for UI
         */
        fields: {
            type: Array,
            required: true,
            // [{ key,label,type,options?,placeholder?,col?,cast? }]
        },

        /**
         * Initial values of form (will be cloned internally)
         */
        initialForm: {
            type: Object,
            required: true,
        },

        /**
         * If you want the component to perform the axios request itself.
         * If empty, it will just emit "change" and you handle fetching in parent.
         */
        url: {
            type: String,
            default: "",
        },

        /**
         * Optional: headers builder (for token etc.)
         */
        headers: {
            type: Function,
            default: null,
        },

        /**
         * Auto behaviors
         */
        autoSearchOnMounted: {
            type: Boolean,
            default: true,
        },
        syncQueryString: {
            type: Boolean,
            default: true,
        },
    },

    data() {
        return {
            loading: false,
            errors: {},
            datastorage: [],
            form: { ...this.initialForm },
        };
    },

    methods: {
        // cast + set value
        setFieldValue(field, rawValue) {
            let value = rawValue;

            // field.cast supports: "number" | "string"
            if (field.cast === "number") {
                // handle "" -> "" (keep empty)
                value = rawValue === "" ? "" : Number(rawValue);
            }

            this.$set
                ? this.$set(this.form, field.key, value) // Vue2 compat
                : (this.form[field.key] = value); // Vue3
        },

        onChange(field, rawValue) {
            this.setFieldValue(field, rawValue);

            // Always notify parent of latest form state
            this.$emit("change", { ...this.form });

            // If url is provided, do internal search
            if (this.url) this.search();
        },

        search() {
            this.errors = {};
            this.loading = true;

            // keep your existing event contract
            this.$emit("payroll-list", this.datastorage, true);

            // if no url, just emit and stop
            if (!this.url) {
                this.loading = false;
                this.$emit("payroll-list", this.datastorage, false);
                return;
            }

            const config = this.headers ? { headers: this.headers() } : {};

            axios
                .post(this.url, this.form, config)
                .then((response) => {
                    const data = response?.data?.data ?? response?.data ?? [];
                    this.datastorage = data;
                    this.$emit("payroll-list", data, false);
                    this.loading = false;
                })
                .catch((error) => {
                    if (error.response?.status === 422) {
                        this.errors = error.response.data.errors || {};
                    } else {
                        Swal.fire(
                            "Error",
                            error.response?.data?.message ||
                                "Something went wrong.",
                            "error"
                        );
                    }

                    this.loading = false;
                    this.$emit("payroll-list", this.datastorage, false);
                });
        },

        hydrateFromQueryString() {
            const params = new URLSearchParams(window.location.search);

            for (const [key, value] of params.entries()) {
                const field = this.fields.find((f) => f.key === key);
                if (!field) continue;

                // cast based on field
                this.setFieldValue(field, value);
            }
        },

        pushQueryString() {
            const params = new URLSearchParams();

            for (const [k, v] of Object.entries(this.form)) {
                if (v !== "" && v !== null && v !== undefined) {
                    params.set(k, String(v));
                }
            }

            const query = params.toString();
            const newUrl = query
                ? `${window.location.pathname}?${query}`
                : window.location.pathname;

            window.history.replaceState({}, "", newUrl);
        },
    },

    watch: {
        form: {
            deep: true,
            handler() {
                if (this.syncQueryString) this.pushQueryString();
            },
        },
    },

    mounted() {
        if (this.syncQueryString) this.hydrateFromQueryString();
        if (this.autoSearchOnMounted && this.url) this.search();
    },
};
</script>
