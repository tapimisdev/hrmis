<template>
    <div class="border rounded p-3">
        <div class="fw-bold mb-2">Preview</div>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Hazard Tax List</div>
                <div class="fw-bold">
                    {{
                        selectedName(options.hazardTax, form.hazardTaxId) || "—"
                    }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Salary Tax List</div>
                <div class="fw-bold">
                    {{
                        selectedName(options.salaryTax, form.salaryTaxId) || "—"
                    }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Salary Tax List</div>
                <div class="fw-bold">
                    {{
                        selectedName(options.salaryTax, form.longevityTaxId) ||
                        "—"
                    }}
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">TRAIN Law</div>
                <div class="fw-bold">
                    {{ selectedName(options.trainLaw, form.trainLawId) || "—" }}
                </div>
            </div>
        </div>

        <hr class="my-3" />

        <div class="fw-bold mb-2">Monthly Tax Portion Allocation</div>
        <ul class="mb-0">
            <li>
                Hazard Pay: <b>{{ form.allocation.hazardPayPct }}%</b>
            </li>
            <li>
                Basic Pay: <b>{{ form.allocation.basicPayPct }}%</b>
            </li>
            <li>
                Longevity: <b>{{ form.allocation.longevityPct }}%</b>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "ForecastPreview",
    props: {
        form: { type: Object, required: true },
        options: { type: Object, required: true },
    },
    methods: {
        selectedName(list, id) {
            const item = (list || []).find((x) => String(x.id) === String(id));
            return item ? item.year : "";
        },
    },
};
</script>
