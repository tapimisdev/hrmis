export function TaxationSettingModel(data = {}) {
    const s = data?.settings ?? {};

    const toBool = (val) => val === true || val === 1 || val === "1";

    const toNumber = (val, fallback = 0) => {
        const num = Number(val);
        return Number.isFinite(num) ? num : fallback;
    };

    const normalizeItems = (items) => {
        if (!Array.isArray(items)) return [];
        return items.map((item) => ({
            id: item?.id ?? null,
            taxation_id: item?.taxation_id ?? data?.id ?? null,
            name: item?.name ?? "",
            amount: toNumber(item?.amount),
            tax_type: item?.tax_type ?? "taxable",
            created_at: item?.created_at ?? null,
            updated_at: item?.updated_at ?? null,
        }));
    };

    const normalizeCard = (items) => {
        // If null/undefined → empty
        if (!items) return [];

        // If it's an object like { cards: [...] }
        if (!Array.isArray(items) && typeof items === "object") {
            items = items.cards ?? Object.values(items);
        }

        if (!Array.isArray(items)) return [];

        return items.map((row = {}) => ({
            title: String(row.title ?? "").trim(),
            value: String(row.value ?? "").trim(),
            icon: row.icon ?? "fa-solid fa-circle-info",
        }));
    };

    const normalizeBody = (items) => {
        // If null/undefined → empty
        if (!items) return [];

        // If it's an object like { body: [...] }
        if (!Array.isArray(items) && typeof items === "object") {
            items = items.body ?? Object.values(items);
        }

        if (!Array.isArray(items)) return [];

        // helpers
        const toMoney = (v) => {
            if (v === null || v === undefined || v === "") return "₱ 0.00";
            // if backend already formatted like "₱ 1,234.00", keep it
            if (typeof v === "string" && v.includes("₱")) return v;
            const n = Number(String(v).replace(/[^0-9.-]/g, ""));
            if (Number.isNaN(n)) return "₱ 0.00";
            return `₱ ${n.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        const toPercent = (v) => {
            if (v === null || v === undefined || v === "") return "0%";
            const n = Number(String(v).replace(/[^0-9.-]/g, ""));
            if (Number.isNaN(n)) return "0%";
            return `${n.toLocaleString("en-PH", { minimumFractionDigits: 0, maximumFractionDigits: 2 })}%`;
        };

        const toRemarksArray = (v) => {
            if (!v) return [];
            if (Array.isArray(v)) return v;
            if (typeof v === "string") {
                try {
                    const parsed = JSON.parse(v);
                    return Array.isArray(parsed) ? parsed : [];
                } catch {
                    // fallback: treat as a single remark string
                    return v.trim() ? [v] : [];
                }
            }
            return [];
        };

        // raw numeric helper for computation inputs (does NOT affect existing outputs)
        const toRawNumber = (v) => {
            if (v === null || v === undefined || v === "") return 0;
            if (typeof v === "number") return Number.isFinite(v) ? v : 0;
            const n = Number(String(v).replace(/[^0-9.-]/g, ""));
            return Number.isNaN(n) ? 0 : n;
        };

        return items.map((row = {}) => ({
            id: row.id ?? "",
            year: row.year ?? "",
            avatar: row.avatar ?? "",
            employee_no: row.employee_no ?? "",

            full_name: row.full_name ?? "",
            division: row.division ?? "",
            position: row.position ?? "",
            unit: row.unit ?? "",

            mid_year: toBool(row.mid_year),
            year_end: toBool(row.year_end),
            longevity: toBool(row.longevity),
            hazard_pay: toBool(row.hazard_pay),

            less_bir_rr3_2015: toBool(data?.less_bir_rr3_2015),

            amount_basic_salary: toMoney(row.amount_basic_salary),
            months_covered: row.months_covered,
            amount_anual_total_basic_salary: toMoney(row.amount_anual_total_basic_salary),

            amount_mid_year_bonus: toMoney(row.amount_mid_year_bonus),
            amount_year_end_bonus: toMoney(row.amount_year_end_bonus),
            amount_longevity_pay: toMoney(row.amount_longevity_pay),
            amount_hazard_pay: toMoney(row.amount_hazard_pay),
            amount_other_earnings_taxable: toMoney(row.amount_other_earnings_taxable),
            amount_other_earnings_non_taxable: toMoney(row.amount_other_earnings_non_taxable),
            amount_other_deductions: toMoney(row.amount_other_deductions),
            amount_annual_total_allowables: toMoney(row.amount_annual_total_allowables),

            amount_gross: toMoney(row.amount_gross),

            amount_less : toMoney(row.amount_less),
            amount_total_bonuses : toMoney(row.amount_total_bonuses),
            amount_bonuses_exempt : toMoney(row.amount_bonuses_exempt),
            amount_bonuses_taxable : toMoney(row.amount_bonuses_taxable),

            amount_annual_taxable: toMoney(row.amount_annual_taxable),
            amount_annual_tax: toMoney(row.amount_annual_tax),
            amount_monthly_tax: toMoney(row.amount_monthly_tax),

            portion_hazard_pay: toPercent(row.portion_hazard_pay),
            portion_basic_pay: toPercent(row.portion_basic_pay),
            portion_longevity_pay: toPercent(row.portion_longevity_pay),

            amount_portion_hazard_pay: toMoney(row.amount_portion_hazard_pay),
            amount_portion_basic_pay: toMoney(row.amount_portion_basic_pay),
            amount_portion_longevity_pay: toMoney(row.amount_portion_longevity_pay),

            tax_computation: {
                bracket_from: toMoney(row.tax_computation.bracket_from),
                bracket_to: toMoney(row.tax_computation.bracket_to),
                annual_income: toMoney(row.tax_computation.annual_income),
                fixed_tax: toMoney(row.tax_computation.fixed_tax),
                tax_rate: toNumber(row.tax_computation.tax_rate),
                excess_over: toMoney(row.tax_computation.excess_over),
                excess_amount: toMoney(row.tax_computation.excess_amount),
                tax: toMoney(row.tax_computation.tax),
                monthly_tax: toMoney(row.tax_computation.monthly_tax),
                remarks: row.tax_computation.remarks,
            },
            
            // always an array for bullet rendering
            remarks: toRemarksArray(row.remarks),
        }));
    };

    const normalizeTrainLawTable = (items) => {
        if (!Array.isArray(items)) return [];
        return items.map((row) => ({
            income_from: toNumber(row?.income_from),
            income_to: row?.income_to == null ? null : toNumber(row?.income_to),
            fixed_tax: toNumber(row?.fixed_tax),
            rate: toNumber(row?.rate),
            excess: toNumber(row?.excess),
        }));
    };

    const normalizeMapping = (items) => {
        if (!Array.isArray(items)) return [];
        return items.map((m) => ({
            label: m?.label ?? "",
            type: m?.type ?? "",
            note: m?.note ?? "",
            ok: toBool(m?.ok),
        }));
    };

    return {
        // Base
        id: data?.id ?? null,
        year: data?.year ?? "",

        // IDs (top-level from taxations table)
        hazard_tax_id: data?.hazard_tax_id ?? null,
        salary_tax_id: data?.salary_tax_id ?? null,
        longevity_id: data?.longevity_id ?? null,
        train_law_id: data?.train_law_id ?? null,

        // Toggles (top-level)
        mid_year: toBool(data?.mid_year),
        year_end: toBool(data?.year_end),
        longevity: toBool(data?.longevity),
        hazard_pay: toBool(data?.hazard_pay),

        less_bir_rr3_2015: toBool(data?.less_bir_rr3_2015),
        allowable_gsis: toBool(data?.allowable_gsis),
        allowable_philhealth: toBool(data?.allowable_philhealth),
        allowable_pagibig: toBool(data?.allowable_pagibig),

        // Portions (top-level)
        portion_hazard_pay: toNumber(data?.portion_hazard_pay),
        portion_basic_pay: toNumber(data?.portion_basic_pay),
        portion_longevity_pay: toNumber(data?.portion_longevity_pay),

        // sendYearToParent Settings (new structure)
        settings: {
            // // Lists
            other_earnings: normalizeItems(s?.other_earnings),
            other_allowables: normalizeItems(s?.other_allowables),

            // UI mapping list (already built in backend)
            mapping_components: normalizeMapping(s?.mapping_components),

            // Allocation (already in backend settings)
            allocation: {
                basicPayPct: toNumber(s?.allocation?.basicPayPct),
                hazardPayPct: toNumber(s?.allocation?.hazardPayPct),
                longevityPct: toNumber(s?.allocation?.longevityPct),
            },

            // Train law info
            train_law_year: s?.train_law_year ?? "No Train Law",
            train_law_table: normalizeTrainLawTable(s?.train_law_table),
        },

        // cards
        cards: normalizeCard(data?.cards),
        body: normalizeBody(data?.body),

        // Meta
        is_active: toBool(data?.is_active),
        created_at: data?.created_at ?? null,
        updated_at: data?.updated_at ?? null,
    };
}