<template>
    <ApprovalPayroll v-model="localValue" />
</template>

<script>
import ApprovalPayroll from "../../../pera-rata/create/steps/ApprovalPayroll.vue";

export default {
    name: "GovernmentBonusApprovalPayroll",
    components: { ApprovalPayroll },
    props: {
        modelValue: Object,
    },
    emits: ["update:modelValue"],
    computed: {
        localValue: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
    },
};
</script>
