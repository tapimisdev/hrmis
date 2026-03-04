export default {
    props: {
        data: { type: Object, default: () => ({}) },
    },
    computed: {
        computation() {
            if (this.data && this.data.raw_computation) return this.data.raw_computation;
            return this.data || {};
        },
        hasMonths() {
            const rows = this.computation && this.computation.months;
            return Array.isArray(rows) && rows.length > 0;
        },
        monthsTotal() {
            const rows = (this.computation && this.computation.months) || [];
            return rows.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
        },
        effectiveDateUsed() {
            return (
                this.formatDate(
                    (this.computation.meta && this.computation.meta.salary_effective_date_used) ||
                        (this.computation.inputs && this.computation.inputs.effective_date) ||
                        ""
                ) || ""
            );
        },
        summaryCountLabel() {
            const inputs = this.computation.inputs || {};

            if (inputs.items_count !== undefined && inputs.items_count !== null) return "Items";
            if (inputs.months_covered !== undefined && inputs.months_covered !== null) return "Months Covered";
            if (inputs.months_of_service !== undefined && inputs.months_of_service !== null) return "Months of Service";

            return "";
        },
        summaryCountValue() {
            const inputs = this.computation.inputs || {};

            if (inputs.items_count !== undefined && inputs.items_count !== null) return this.safeNum(inputs.items_count);
            if (inputs.months_covered !== undefined && inputs.months_covered !== null) return this.safeNum(inputs.months_covered);
            if (inputs.months_of_service !== undefined && inputs.months_of_service !== null) return this.safeNum(inputs.months_of_service);

            return "";
        },
    },
    methods: {
        safeText(value) {
            if (value === null || value === undefined) return "";
            return String(value);
        },
        safeNum(value) {
            const number = Number(value);
            return Number.isFinite(number) ? number : 0;
        },
        money(value) {
            return this.safeNum(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        formatMoney(raw, fallback) {
            const number = Number(raw);
            if (Number.isFinite(number)) return this.money(number);
            return this.safeText(fallback);
        },
        formatDate(value) {
            const text = this.safeText(value);
            return text || "-";
        },
        formatValue(value) {
            if (typeof value === "boolean") return value ? "Yes" : "No";
            if (typeof value === "number") return this.safeNum(value);
            if (this.isRowsArray(value)) return `${value.length} item(s)`;
            return this.safeText(value) || "-";
        },
        isRowsArray(value) {
            return (
                Array.isArray(value) &&
                (value.length === 0 ||
                    (value[0] &&
                        typeof value[0] === "object" &&
                        ("label" in value[0] || "value" in value[0])))
            );
        },
        isComplex(value) {
            return Array.isArray(value) || (value && typeof value === "object");
        },
        complexHint(value) {
            if (Array.isArray(value)) return `${value.length} item(s)`;
            return "object";
        },
        pretty(value) {
            try {
                return JSON.stringify(value, null, 2);
            } catch (error) {
                return this.safeText(value);
            }
        },
    },
};
