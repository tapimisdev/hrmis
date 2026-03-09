<template>
    <div class="container py-3">
        <div class="d-flex justify-content-center">
            <div class="card shadow-sm w-100 dtr-card">
                <div
                    class="card-header d-flex align-items-center justify-content-between"
                >
                    <div class="fw-semibold">Daily Time Record</div>
                    <div class="small text-body-secondary">
                        {{ monthLabel }} {{ yearLabel }}
                    </div>
                </div>

                <div class="card-body">
                    <!-- Top info (TABLE, auto-hide empty) -->
                    <div v-if="topRows.length" class="top-table-wrap mb-3">
                        <table
                            class="table table-sm table-bordered align-middle top-info-table"
                        >
                            <tbody>
                                <tr v-for="(pair, idx) in topRows" :key="idx">
                                    <template v-if="pair.length === 1">
                                        <th class="top-th">
                                            {{ pair[0].label }}
                                        </th>
                                        <td class="top-td" colspan="3">
                                            {{ pair[0].value }}
                                        </td>
                                    </template>

                                    <template v-else>
                                        <th class="top-th">
                                            {{ pair[0].label }}
                                        </th>
                                        <td class="top-td">
                                            {{ pair[0].value }}
                                        </td>
                                        <th class="top-th">
                                            {{ pair[1].label }}
                                        </th>
                                        <td class="top-td">
                                            {{ pair[1].value }}
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tables -->
                    <div class="row g-3">
                        <!-- LEFT 1-15 -->
                        <div class="col-12 col-lg-6">
                            <div class="table-responsive">
                                <table
                                    class="table table-sm table-bordered align-middle text-center dtr-table"
                                >
                                    <thead class="table-secondary">
                                        <tr>
                                            <th
                                                rowspan="2"
                                                class="text-start"
                                                style="width: 44px"
                                            >
                                                Day
                                            </th>
                                            <th colspan="2">Morning</th>
                                            <th colspan="2">Afternoon</th>
                                            <th colspan="2">Overtime</th>
                                            <th rowspan="2" style="width: 74px">
                                                Total
                                            </th>
                                            <th
                                                rowspan="2"
                                                style="width: 180px"
                                            >
                                                Remarks
                                            </th>
                                        </tr>
                                        <tr>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>In</th>
                                            <th>Out</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="row in rowsFirstHalf"
                                            :key="'l-' + row.dayNo"
                                        >
                                            <td class="text-center fw-semibold">
                                                {{ row.dayNo }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(row, 'morning_in')
                                                "
                                            >
                                                {{ row.morning_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'morning_out',
                                                    )
                                                "
                                            >
                                                {{ row.morning_out }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'afternoon_in',
                                                    )
                                                "
                                            >
                                                {{ row.afternoon_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'afternoon_out',
                                                    )
                                                "
                                            >
                                                {{ row.afternoon_out }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'overtime_in',
                                                    )
                                                "
                                            >
                                                {{ row.overtime_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'overtime_out',
                                                    )
                                                "
                                            >
                                                {{ row.overtime_out }}
                                            </td>

                                            <td class="fw-semibold">
                                                {{ row.daily_total }}
                                            </td>

                                            <td class="text-start">
                                                <div class="remarks-cell">
                                                    <span
                                                        v-for="(
                                                            rk, i
                                                        ) in filteredRemarks(
                                                            row.remarks,
                                                        )"
                                                        :key="i"
                                                        class=""
                                                        :class="
                                                            remarkBadgeClass(rk)
                                                        "
                                                    >
                                                        {{ rk }}
                                                    </span>
                                                    <span
                                                        v-if="
                                                            filteredRemarks(
                                                                row.remarks,
                                                            ).length === 0
                                                        "
                                                        class=""
                                                    >
                                                        —
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RIGHT 16-31 -->
                        <div class="col-12 col-lg-6">
                            <div class="table-responsive">
                                <table
                                    class="table table-sm table-bordered align-middle text-center dtr-table"
                                >
                                    <thead class="table-secondary">
                                        <tr>
                                            <th
                                                rowspan="2"
                                                class="text-start"
                                                style="width: 44px"
                                            >
                                                Day
                                            </th>
                                            <th colspan="2">Morning</th>
                                            <th colspan="2">Afternoon</th>
                                            <th colspan="2">Overtime</th>
                                            <th rowspan="2" style="width: 74px">
                                                Total
                                            </th>
                                            <th
                                                rowspan="2"
                                                style="width: 180px"
                                            >
                                                Remarks
                                            </th>
                                        </tr>
                                        <tr>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>In</th>
                                            <th>Out</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="row in rowsSecondHalf"
                                            :key="'r-' + row.dayNo"
                                        >
                                            <td class="text-start fw-semibold">
                                                {{ row.dayNo }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(row, 'morning_in')
                                                "
                                            >
                                                {{ row.morning_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'morning_out',
                                                    )
                                                "
                                            >
                                                {{ row.morning_out }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'afternoon_in',
                                                    )
                                                "
                                            >
                                                {{ row.afternoon_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'afternoon_out',
                                                    )
                                                "
                                            >
                                                {{ row.afternoon_out }}
                                            </td>

                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'overtime_in',
                                                    )
                                                "
                                            >
                                                {{ row.overtime_in }}
                                            </td>
                                            <td
                                                :class="
                                                    cellClass(
                                                        row,
                                                        'overtime_out',
                                                    )
                                                "
                                            >
                                                {{ row.overtime_out }}
                                            </td>

                                            <td class="fw-semibold">
                                                {{ row.daily_total }}
                                            </td>

                                            <td class="text-start">
                                                <div class="remarks-cell">
                                                    <span
                                                        v-for="(
                                                            rk, i
                                                        ) in filteredRemarks(
                                                            row.remarks,
                                                        )"
                                                        :key="i"
                                                        :class="
                                                            remarkBadgeClass(rk)
                                                        "
                                                    >
                                                        {{ rk }}
                                                    </span>
                                                    <span
                                                        v-if="
                                                            filteredRemarks(
                                                                row.remarks,
                                                            ).length === 0
                                                        "
                                                        class="text-body-secondary small"
                                                    >
                                                        —
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div
                        class="d-flex justify-content-center w-100 text-center mt-0 pb-4"
                        style="gap: 100px;"
                      >
                        <div style="width: 320px">
                            <div class="mt-2 text-uppercase mb-1">
                                {{ originalName }}
                            </div>
                            <div
                                class="border-top pt-1 small fw-semibold"
                                style="min-width: 220px"
                            >
                                EMPLOYEE'S SIGNATURE
                            </div>
                            <div
                              class="mt-4 small text-center fst-italic text-body-secondary"
                              >
                                  I hereby certify that the above records are true
                                  and correct.
                            </div>
                        </div>
                        <div style="width: 320px; position: relative">
                            <div class="mt-2 text-uppercase mb-1">
                                {{ supervisor }}
                            </div>
                            <div
                                class="border-top pt-1 small fw-semibold"
                            >
                                SUPERVISOR'S SIGNATURE
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    payload: {
        type: Object,
        default: () => ({
            computedData: [],
            summary: [],
            payroll_value: {},
            information: {},
        }),
    },
    month: { type: Number, required: true },
    year: { type: Number, required: true },
    supervisor: { type: String, required: true },
    // Optional: overrides (still supported)
    form: {
        type: Object,
        default: () => ({
            no: "",
            payEnding: "",
            year: "",
            name: "",
            position: "",
            dept: "",
            age: "",
        }),
    },
});

const computedData = computed(() => props.payload?.computedData ?? []);
const info = computed(() => props.payload?.information ?? {});

const originalName = computed(() =>
    `${props.payload?.information?.firstname ?? ""} ${
        props.payload?.information?.lastname ?? ""
    }`.trim(),
);

/** Labels */
const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];
const monthLabel = computed(() => monthNames[(props.month || 1) - 1] || "");
const yearLabel = computed(() => props.year || "");

/** TOP mapping (information -> display) */
const top = computed(() => {
    const i = info.value || {};
    const f = props.form || {};

    const fullName = [
        i.lastname,
        i.firstname,
        i.middlename ? `${i.middlename}` : null,
        i.suffix ? `${i.suffix}` : null,
    ]
        .filter(Boolean)
        .join(", ")
        .replace(/\s+/g, " ")
        .trim();

    return {
        no: f.no || "",
        payEnding: f.payEnding || "",
        year: f.year || (props.year ? String(props.year) : ""),

        name: f.name || fullName,
        position: f.position || i.position_name || i.position_code || "",
        dept: f.dept || i.unit_name || i.unit_code || "",
        age: f.age || (i.age != null ? String(i.age) : ""),
    };
});

/** helpers for hiding empties */
function hasValue(v) {
    return v !== null && v !== undefined && String(v).trim() !== "";
}

/**
 * Build rows as pairs: [[{label,value},{label,value}], ...]
 * - removes any fields with no value
 * - if odd count, last row becomes 1 item (colspan=3)
 */
const topRows = computed(() => {
    const items = [
        { label: "Name", value: top.value.name },
        { label: "Position", value: top.value.position },
        { label: "Division/Unit", value: top.value.dept },
        { label: "Age", value: top.value.age },
    ].filter((x) => hasValue(x.value));

    const rows = [];
    for (let i = 0; i < items.length; i += 2) {
        if (items[i + 1]) rows.push([items[i], items[i + 1]]);
        else rows.push([items[i]]);
    }
    return rows;
});

/** Helpers */
function normalizeDayKey(dateStr) {
    return (dateStr || "").slice(0, 10);
}

function minutesToHM(mins) {
    const m = Number(mins || 0);
    if (!m) return "";
    const h = Math.floor(m / 60);
    const r = m % 60;
    if (h && r) return `${h}:${String(r).padStart(2, "0")}`;
    if (h && !r) return `${h}:00`;
    return `0:${String(r).padStart(2, "0")}`;
}

function daysInMonth(year, month) {
    return new Date(year, month, 0).getDate();
}

// ✅ local date key helpers (prevents timezone shifting)
function pad2(n) {
    return String(n).padStart(2, "0");
}
function makeLocalDateKey(y, m, d) {
    // m is 1-12
    return `${y}-${pad2(m)}-${pad2(d)}`;
}

/** EXACT match like parent */
function hasRemarkExact(remarks, keyword) {
    if (!Array.isArray(remarks)) return false;
    return remarks.some(
        (r) =>
            String(r).trim().toLowerCase() ===
            String(keyword).trim().toLowerCase(),
    );
}

/** same status logic as parent */
function hasStatus(remarks) {
    return (
        hasRemarkExact(remarks, "restday") ||
        hasRemarkExact(remarks, "holiday") ||
        hasRemarkExact(remarks, "leave") ||
        hasRemarkExact(remarks, "ob") ||
        hasRemarkExact(remarks, "absent")
    );
}

/** Group duplicates per YYYY-MM-DD */
const byDay = computed(() => {
    const map = new Map();
    for (const item of computedData.value) {
        const key = normalizeDayKey(item.date);
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(item);
    }
    return map;
});

/** prefer real logs (but still just DISPLAY raw values) */
function pickBestRow(rows = []) {
    const score = (r) => {
        let s = 0;
        if (r?.time_in) s += 50;
        if (r?.time_out) s += 50;
        if (String(r?.break || "").includes(" to ")) s += 10;
        if (String(r?.overtime || "").includes(" to ")) s += 5;
        if (Number(r?.paid_hours) > 0) s += 10;
        if (Number(r?.total_time_work) > 0) s += 8;

        const remarks = r?.remarks || [];
        if (hasStatus(remarks)) s -= 5;
        return s;
    };

    return [...rows].sort((a, b) => score(b) - score(a))[0] || null;
}

/** build month rows using selected month/year */
const monthRows = computed(() => {
    const y = props.year;
    const m = props.month;
    const totalDays = daysInMonth(y, m);

    const rows = [];

    for (let dayNo = 1; dayNo <= totalDays; dayNo++) {
        // ✅ NO toISOString (timezone-safe)
        const key = makeLocalDateKey(y, m, dayNo);

        const candidates = byDay.value.get(key);
        const chosen = candidates ? pickBestRow(candidates) : null;

        const remarks = chosen?.remarks ?? [];

        const breakPair = chosen?.break ? String(chosen.break).split(" to ") : [];
        const overtimePair = chosen?.overtime
            ? String(chosen.overtime).split(" to ")
            : [];

        rows.push({
            dayNo,
            dateKey: key,
            raw: chosen,
            remarks,
            late_undertime: Number(chosen?.late_undertime || 0),

            // ✅ DO NOT VALIDATE / DO NOT BLANK:
            // show whatever is stored, even if swapped/wrong
            morning_in: chosen?.time_in || "",
            morning_out: breakPair[0] || "",
            afternoon_in: breakPair[1] || "",
            afternoon_out: chosen?.time_out || "",
            overtime_in: overtimePair[0] || "",
            overtime_out: overtimePair[1] || "",

            daily_total:
                Number(chosen?.total_time_work) > 0
                    ? minutesToHM(chosen.total_time_work)
                    : "",
        });
    }

    // fill to 31 rows (for print layout)
    for (let d = totalDays + 1; d <= 31; d++) {
        rows.push({
            dayNo: d,
            dateKey: null,
            raw: null,
            remarks: [],
            late_undertime: 0,
            morning_in: "",
            morning_out: "",
            afternoon_in: "",
            afternoon_out: "",
            overtime_in: "",
            overtime_out: "",
            daily_total: "",
        });
    }

    return rows;
});

const rowsFirstHalf = computed(() => monthRows.value.slice(0, 15));
const rowsSecondHalf = computed(() => monthRows.value.slice(15, 31));

/** Remarks column: same idea as parent getFilteredRemarks */
function filteredRemarks(remarks) {
    if (!Array.isArray(remarks)) return [];
    return remarks.filter((r) => {
        const v = String(r).toLowerCase().trim();
        return ![
            "restday",
            "holiday",
            "leave",
            "ob",
            "absent",
            "today",
            "overtime",
            "pending overtime",
        ].includes(v);
    });
}

/** Optional: color tags similar to parent */
function remarkBadgeClass(remark) {
    const lower = String(remark).toLowerCase().trim();
    if (lower === "incomplete log") return "text-bg-danger";
    if (lower === "late" || lower === "undertime") return "text-bg-warning";
    return "";
}

/** Cell coloring (NO muting on status days; just display raw) */
function cellClass(row, fieldKey) {
    if (!row?.raw) return "text-body-secondary";
    if (!row?.[fieldKey]) return "text-body-secondary";

    // Optional highlight if flagged by remarks or has late/undertime
    if (
        hasRemarkExact(row.remarks, "consider absent") ||
        Number(row.late_undertime) > 0
    ) {
        return "text-danger fw-semibold";
    }

    return "text-body";
}
</script>


<style scoped>
.dtr-card {
    max-width: auto;
}

/* top info table */
.top-table-wrap {
    border-radius: 8px;
    overflow: hidden;
}

.top-info-table {
    margin-bottom: 0;
}

.top-th {
    width: 120px;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    background: rgba(0, 0, 0, 0.03);
    white-space: nowrap;
    padding: 0.35rem 0.5rem;
}

.top-td {
    font-size: 0.92rem;
    font-weight: 600;
    padding: 0.35rem 0.5rem;
}

/* your table styles untouched */
.dtr-table td,
.dtr-table th {
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        "Liberation Mono", "Courier New", monospace;
    font-size: 0.78rem;
    padding: 0.25rem;
    white-space: nowrap;
}

.remarks-cell {
    text-transform: capitalize;
    white-space: normal;
    line-height: 1.1;
}

/* print tweaks */
@media print {
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
