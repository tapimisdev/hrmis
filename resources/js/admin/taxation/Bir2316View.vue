<template>
    <div class="container-fluid py-4 bir2316-view">
        <div class="bir2316-toolbar">
            <a class="btn btn-secondary" :href="backUrl">Back</a>
            <div class="bir2316-actions">
                <a class="btn btn-primary text-capitalize" :href="excelDownloadUrl">Download</a>
            </div>
        </div>

        <div v-if="loading" class="bir2316-message">Loading BIR 2316 record...</div>
        <div v-else-if="error" class="alert alert-danger bir2316-message">{{ error }}</div>

        <div v-else class="bir2316-scroll">
            <main class="bir-form">
                <header class="bir-header">
                    <div class="bir-use-box">
                        <span>For BIR<br>Use Only</span>
                        <b>BCS/<br>Item:</b>
                    </div>
                    <div class="bir-agency">
                        <img
                            class="bir-seal"
                            :src="'/img/bir-logo-bw.png'"
                            alt="Bureau of Internal Revenue seal"
                        >
                        <div>
                            <strong>Republic of the Philippines</strong>
                            <b>Department of Finance</b>
                            <strong>Bureau of Internal Revenue</strong>
                        </div>
                    </div>
                </header>

                <div class="bir-title-row">
                    <div class="bir-form-number">
                        <small>BIR Form No.</small>
                        <strong>2316</strong>
                        <span>September 2021 (ENCS)</span>
                    </div>
                    <div class="bir-heading">
                        <strong>Certificate of Compensation<br>Payment/Tax Withheld</strong>
                        <span>For Compensation Payment With or Without Tax Withheld</span>
                    </div>
                    <div class="bir-barcode-wrap">
                        <img class="bir-barcode" :src="'/img/bir-2316-barcode.svg'" alt="BIR Form 2316 barcode">
                    </div>
                </div>

                <div class="bir-instruction">
                    Fill in all applicable spaces. Mark all appropriate boxes with an "X".
                </div>

                <div class="bir-period">
                    <div class="period-choice">
                        <strong>1</strong>
                        <span>For the Year (YYYY)</span>
                        <div class="digit-boxes">
                            <i v-for="(digit, index) in yearDigits" :key="index">{{ digit }}</i>
                        </div>
                    </div>
                    <div class="period-choice">
                        <strong>2</strong>
                        <span>For the Period</span>
                        <span class="period-value">
                            From <b class="date-value" contenteditable="true" spellcheck="false"></b>
                            To <b class="date-value" contenteditable="true" spellcheck="false"></b>
                        </span>
                    </div>
                </div>

                <div class="bir-body">
                    <div class="bir-left">
                        <FormSection title="Part I - Employee Information">
                            <InfoRow number="3" label="TIN" :value="employee.tin" segmented />
                            <div class="bir-info-pair employee-name-row">
                                <InfoRow number="4" label="Employee's Name (Last Name, First Name, Middle Name)" :value="employee.name" />
                                <InfoRow number="5" label="RDO Code" :value="employer.rdo_code" compact />
                            </div>
                            <InfoRow number="6" label="Registered Address" :value="employeeData.registered_address || employee.address">
                                <template #side><b>6A</b> ZIP Code<br><strong>{{ employeeData.registered_zip_code || employeeData.zip_code || blank }}</strong></template>
                            </InfoRow>
                            <InfoRow number="6B" label="Local Home Address" :value="employeeData.local_address || employee.address">
                                <template #side><b>6C</b> ZIP Code<br><strong>{{ employeeData.local_zip_code || employeeData.zip_code || blank }}</strong></template>
                            </InfoRow>
                            <InfoRow number="6D" label="Foreign Address" :value="employeeData.foreign_address" />
                            <div class="bir-info-pair">
                                <InfoRow number="7" label="Date of Birth (MM/DD/YYYY)" :value="date(employeeData.birth_date)" />
                                <InfoRow number="8" label="Contact Number" :value="employeeData.contact_number" />
                            </div>
                            <div class="bir-info-pair">
                                <InfoRow number="9" label="Statutory Minimum Wage rate per day" value="" />
                                <InfoRow number="10" label="Statutory Minimum Wage rate per month" value="" />
                            </div>
                            <div class="mwe-row">
                                <b>11</b>
                                <i class="check"></i>
                                <span>Minimum Wage Earner (MWE) whose compensation is exempt from withholding tax and not subject to income tax</span>
                            </div>
                        </FormSection>

                        <FormSection title="Part II - Employer Information (Present)">
                            <InfoRow number="12" label="TIN" :value="employer.tin" segmented />
                            <InfoRow number="13" label="Employer's Name" :value="employer.name" />
                            <InfoRow number="14" label="Registered Address" :value="employer.address">
                                <template #side><b>14A</b> ZIP Code<br><strong>{{ employerData.zip_code || blank }}</strong></template>
                            </InfoRow>
                            <div class="employer-type">
                                <b>15</b> Type of Employer
                                <span><i class="check checked">X</i> Main Employer</span>
                                <span><i class="check"></i> Secondary Employer</span>
                            </div>
                        </FormSection>

                        <FormSection title="Part III - Employer Information (Previous)">
                            <InfoRow number="16" label="TIN" value="" segmented />
                            <InfoRow number="17" label="Employer's Name" value="" />
                            <InfoRow number="18" label="Registered Address" value="">
                                <template #side><b>18A</b> ZIP Code</template>
                            </InfoRow>
                        </FormSection>

                        <FormSection title="Part IV-A - Summary">
                            <AmountTable :rows="summaryRows" />
                        </FormSection>
                    </div>

                    <div class="bir-right">
                        <FormSection title="Part IV-B Details of Compensation Income & Tax Withheld from Present Employer">
                            <div class="amount-heading">
                                <span>A. NON-TAXABLE/EXEMPT COMPENSATION INCOME</span>
                                <strong>Amount</strong>
                            </div>
                            <AmountTable :rows="nonTaxableRows" />
                            <div class="amount-heading taxable">
                                <span>B. TAXABLE COMPENSATION INCOME</span>
                                <strong>Amount</strong>
                            </div>
                            <div class="subheading">REGULAR</div>
                            <AmountTable :rows="regularTaxableRows" />
                            <div class="subheading">SUPPLEMENTARY</div>
                            <AmountTable :rows="supplementaryRows" />
                        </FormSection>
                    </div>
                </div>

                <div class="page-one-declaration">
                    I/We declare, under the penalties of perjury, that this certificate has been made in good faith,
                    verified by me/us, and to the best of my/our knowledge and belief, is true and correct, pursuant to
                    the provisions of the National Internal Revenue Code, as amended, and the regulations issued under
                    authority thereof. Further, I/we give my/our consent to the processing of my/our information as
                    contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful
                    purposes.
                </div>
            </main>

            <section class="bir-continuation">
                <div class="continuation-signatures">
                    <b class="continuation-number">53</b>
                    <SignatureBox
                        value=""
                        label="Present Employer/Authorized Agent Signature over Printed Name"
                    />
                    <span class="date-label">Date Signed</span>
                    <DateBoxes value="" />
                </div>

                <div class="conforme">CONFORME:</div>

                <div class="continuation-signatures">
                    <b class="continuation-number">54</b>
                    <SignatureBox
                        :value="certification.employee_signature || employee.name"
                        label="Employee Signature over Printed Name"
                    />
                    <span class="date-label">Date Signed</span>
                    <DateBoxes :value="signedDate" />
                </div>

                <div class="id-row">
                    <span>CTC/Valid ID No.<br>of Employee</span><b></b>
                    <span>Place of<br>Issue</span><b></b>
                    <span>Date Issued</span><DateBoxes value="" />
                    <span>Amount paid, if CTC</span><b></b>
                </div>

                <h3>To be accomplished under substituted filing</h3>

                <div class="substituted-grid">
                    <div>
                        <b class="continuation-number">55</b>
                        <p>
                            I declare, under the penalties of perjury, that the information herein stated are reported
                            under BIR Form No. 1604-C which has been filed with the Bureau of Internal Revenue.
                        </p>
                        <SignatureBox
                            value=""
                            label="Present Employer/Authorized Agent Signature over Printed Name"
                        />
                        <small>(Head of Accounting/Human Resource or Authorized Representative)</small>
                    </div>
                    <div>
                        <p>
                            I declare, under the penalties of perjury that I am qualified under substituted filing of
                            Income Tax Return (BIR Form No. 1700), since I received purely compensation income from only
                            one employer in the Philippines for the calendar year; that taxes have been correctly
                            withheld by my employer (tax due equals tax withheld); that the BIR Form No. 1604-C filed by
                            my employer to the BIR shall constitute as my income tax return; and that BIR Form No. 2316
                            shall serve the same purpose as if BIR Form No. 1700 has been filed pursuant to the provisions
                            of Revenue Regulations (RR) No. 3-2002, as amended.
                        </p>
                        <b class="continuation-number">56</b>
                        <SignatureBox
                            :value="certification.employee_signature || employee.name"
                            label="Employee Signature over Printed Name"
                        />
                    </div>
                </div>
            </section>
            <div class="privacy-note">*NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)</div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

const FormSection = {
    props: { title: String },
    template: `
        <section class="form-section">
            <h2>{{ title }}</h2>
            <slot />
        </section>
    `,
};

const InfoRow = {
    props: {
        number: String,
        label: String,
        value: [String, Number],
        segmented: Boolean,
        compact: Boolean,
    },
    computed: {
        displayValue() {
            return this.value || "\u00a0";
        },
        digits() {
            return String(this.value || "").replace(/\D/g, "").slice(0, 12).padEnd(12, " ").split("");
        },
    },
    template: `
        <div class="info-row" :class="{ compact }">
            <div class="info-main">
                <div class="field-label"><b>{{ number }}</b> {{ label }}</div>
                <div v-if="segmented" class="tin-boxes">
                    <i v-for="(digit, index) in digits" :key="index">{{ digit }}</i>
                </div>
                <strong v-else class="field-value">{{ displayValue }}</strong>
            </div>
            <div v-if="$slots.side" class="info-side"><slot name="side" /></div>
        </div>
    `,
};

const AmountTable = {
    props: { rows: Array },
    methods: {
        money(value) {
            if (value === null || value === undefined || value === "") return "";
            return new Intl.NumberFormat("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        },
    },
    template: `
        <table class="amount-table">
            <colgroup>
                <col class="number-column">
                <col>
                <col class="amount-column">
            </colgroup>
            <tbody>
                <tr
                    v-for="row in rows"
                    :key="row.number"
                    :class="{ total: row.total, tall: row.tall, compact: row.compact }"
                >
                    <th scope="row">{{ row.number }}</th>
                    <td>{{ row.label }}</td>
                    <td class="amount">{{ money(row.value) }}</td>
                </tr>
            </tbody>
        </table>
    `,
};

const SignatureBox = {
    props: { value: String, label: String },
    template: `
        <div class="signature-box">
            <strong>{{ value || "\u00a0" }}</strong>
            <span>{{ label }}</span>
        </div>
    `,
};

const DateBoxes = {
    props: { value: String },
    computed: {
        digits() {
            return String(this.value || "").replace(/\D/g, "").slice(0, 8).padEnd(8, " ").split("");
        },
    },
    template: `
        <div class="date-boxes">
            <i v-for="(digit, index) in digits" :key="index">{{ digit }}</i>
        </div>
    `,
};

export default {
    components: { FormSection, InfoRow, AmountTable, SignatureBox, DateBoxes },
    props: {
        apiUrl: { type: String, required: true },
        backUrl: { type: String, required: true },
        previewUrl: { type: String, required: true },
        pdfDownloadUrl: { type: String, required: true },
        excelDownloadUrl: { type: String, required: true },
    },
    data() {
        return {
            record: null,
            loading: true,
            error: "",
            blank: "\u00a0",
        };
    },
    computed: {
        snapshot() { return this.record?.snapshot_data || {}; },
        employee() { return this.record?.employee || {}; },
        employer() { return this.record?.employer || {}; },
        employeeData() { return this.snapshot.employee || {}; },
        employerData() { return this.snapshot.employer || {}; },
        pdfValues() { return this.snapshot.pdf_values || {}; },
        compensation() { return this.record?.compensation || {}; },
        certification() { return this.record?.certification || {}; },
        yearDigits() { return String(this.record?.taxable_year || "").padEnd(4, " ").slice(0, 4).split(""); },
        signedDate() { return this.date(this.certification.date_signed || this.record?.generated_at); },
        contributions() { return Number(this.pdfValues.total_contributions || 0); },
        nonTaxableTotal() {
            return Number(this.pdfValues.non_taxable_compensation)
                || Number(this.compensation.tax_exempt_bonus || 0)
                    + Number(this.compensation.de_minimis || 0)
                    + this.contributions
                    + Number(this.pdfValues.other_nontaxable_compensation || 0);
        },
        summaryRows() {
            const c = this.compensation;
            return [
                { number: "19", label: "Gross Compensation Income from Present Employer", value: Number(c.gross_taxable_income || 0) + this.nonTaxableTotal },
                { number: "20", label: "Less: Total Non-Taxable/Exempt Compensation Income", value: this.nonTaxableTotal },
                { number: "21", label: "Taxable Compensation Income from Present Employer", value: c.net_taxable_income },
                { number: "22", label: "Add: Taxable Compensation Income from Previous Employer, if applicable", value: 0, tall: true },
                { number: "23", label: "Gross Taxable Compensation Income", value: c.net_taxable_income, total: true },
                { number: "24", label: "Tax Due", value: c.annual_tax_due, total: true },
                { number: "25A", label: "Amount of Taxes Withheld - Present Employer", value: c.tax_withheld },
                { number: "25B", label: "Amount of Taxes Withheld - Previous Employer, if applicable", value: 0, tall: true },
                { number: "26", label: "Total Amount of Taxes Withheld as adjusted", value: c.tax_withheld, total: true },
                { number: "27", label: "5% Tax Credit (PERA Act of 2008)", value: 0 },
                { number: "28", label: "Total Taxes Withheld", value: c.tax_withheld, total: true },
            ];
        },
        nonTaxableRows() {
            const c = this.compensation;
            return [
                { number: "29", label: "Basic Salary (including the exempt P250,000 & below) or the Statutory Minimum Wage of the MWE", value: 0, tall: true },
                { number: "30", label: "Holiday Pay (MWE)", value: 0 },
                { number: "31", label: "Overtime Pay (MWE)", value: 0 },
                { number: "32", label: "Night Shift Differential (MWE)", value: 0 },
                { number: "33", label: "Hazard Pay (MWE)", value: 0 },
                { number: "34", label: "13th Month Pay and Other Benefits (maximum of P90,000)", value: c.tax_exempt_bonus, tall: true },
                { number: "35", label: "De Minimis Benefits", value: c.de_minimis },
                { number: "36", label: "SSS, GSIS, PHIC & PAG-IBIG Contributions (employee share only)", value: this.contributions, tall: true },
                { number: "37", label: "Salaries and Other Forms of Compensation", value: this.pdfValues.other_nontaxable_compensation, tall: true },
                { number: "38", label: "Total Non-Taxable/Exempt Compensation Income", value: this.nonTaxableTotal, total: true },
            ];
        },
        regularTaxableRows() {
            const c = this.compensation;
            return [
                { number: "39", label: "Basic Salary", value: c.annual_basic_salary },
                { number: "40", label: "Representation", value: 0 },
                { number: "41", label: "Transportation", value: 0 },
                { number: "42", label: "Cost of Living Allowance (COLA)", value: 0 },
                { number: "43", label: "Fixed Housing Allowance", value: 0 },
                { number: "44A", label: "Others (specify)", value: 0, tall: true },
                { number: "44B", label: "Others (specify)", value: 0, tall: true },
            ];
        },
        supplementaryRows() {
            const c = this.compensation;
            return [
                { number: "45", label: "Commission", value: 0 },
                { number: "46", label: "Profit Sharing", value: 0 },
                { number: "47", label: "Fees Including Director's Fees", value: 0 },
                { number: "48", label: "Taxable 13th Month Benefits", value: c.net_taxable_benefit },
                { number: "49", label: "Hazard Pay", value: c.hazard_pay },
                { number: "50", label: "Overtime Pay", value: 0 },
                { number: "51A", label: "Others (specify): Longevity Pay", value: c.longevity_pay, tall: true },
                { number: "51B", label: "Others (specify)", value: this.pdfValues.other_taxable_compensation, tall: true },
                { number: "52", label: "Total Taxable Compensation Income (Sum of Items 39 to 51B)", value: c.gross_taxable_income, total: true, tall: true },
            ];
        },
    },
    async mounted() {
        try {
            const { data } = await axios.get(this.apiUrl, { headers: { Accept: "application/json" } });
            this.record = data.data || data;
        } catch (error) {
            this.error = error?.response?.data?.message || "Unable to load BIR 2316 record.";
        } finally {
            this.loading = false;
        }
    },
    methods: {
        date(value) {
            if (!value) return "";
            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) return value;
            return new Intl.DateTimeFormat("en-US", {
                month: "2-digit",
                day: "2-digit",
                year: "numeric",
            }).format(parsed);
        },
    },
};
</script>

<style>
.bir2316-view { min-height: 100vh; background: #e9edf2; }
.bir2316-toolbar, .bir2316-message, .bir2316-scroll { width: min(100%, 1180px); margin-left: auto; margin-right: auto; }
.bir2316-toolbar { margin-bottom: 1rem; display: flex; justify-content: space-between; gap: .75rem; }
.bir2316-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.bir2316-message { padding: 3rem; text-align: center; background: #fff; }
.bir2316-scroll { overflow-x: auto; padding-bottom: 1rem; }
.bir-form, .bir-continuation { width: 1024px; margin: 0 auto; color: #000; box-shadow: 0 10px 28px rgba(0,0,0,.15); font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.08; }
.bir-form { border: 0; background: #cfcfcf; }
.bir-continuation { border: 2px solid #000; }
.bir-header { height: 52px; display: grid; grid-template-columns: 31% 38% 31%; background: #fff; }
.bir-use-box { grid-column: 1; padding: 4px 6px; display: flex; align-items: center; gap: 12px; font-size: 10px; }
.bir-use-box span { padding: 3px 5px; background: #d8d8d8; line-height: 1; }
.bir-agency { grid-column: 2; display: flex; align-items: center; justify-content: center; gap: 12px; text-align: center; font-size: 10px; line-height: 1.18; }
.bir-agency > div:last-child { display: grid; }
.bir-seal { width: 48px; height: 48px; display: block; flex: 0 0 48px; border: 0; outline: 0; border-radius: 0; background: transparent; object-fit: contain; }
.bir-title-row { height: 84px; display: grid; grid-template-columns: 17% 55% 28%; background: #fff; border: 2px solid #000; }
.bir-title-row > div { border-right: 2px solid #000; }
.bir-title-row > div:last-child { border-right: 0; }
.bir-heading { display: grid; place-content: center; text-align: center; }
.bir-heading strong { font-size: 20px; line-height: 1.15; }
.bir-heading span { margin-top: 3px; font-size: 9px; }
.bir-form-number { display: grid; place-content: center; text-align: center; }
.bir-form-number strong { font-size: 33px; line-height: .95; }
.bir-form-number span { font-size: 9px; }
.bir-barcode-wrap { padding: 5px; display: grid; align-content: center; }
.bir-barcode { width: 100%; height: 72px; display: block; object-fit: fill; }
.bir-instruction { height: 17px; padding: 2px 5px; border-right: 2px solid #000; border-bottom: 2px solid #000; border-left: 2px solid #000; background: #fff; font-size: 8px; }
.bir-period { display: grid; grid-template-columns: 50% 50%; border-right: 2px solid #000; border-bottom: 1px solid #000; border-left: 2px solid #000; }
.period-choice { height: 37px; padding: 3px 7px; display: flex; align-items: center; gap: 7px; border-right: 2px solid #000; }
.period-choice:last-child { border-right: 0; }
.period-choice > strong { font-size: 12px; }
.digit-boxes, .tin-boxes { display: flex; margin-left: auto; }
.digit-boxes i, .tin-boxes i { width: 18px; height: 17px; display: grid; place-items: center; border: 1px solid #000; border-right: 0; font-style: normal; font-weight: 700; }
.digit-boxes i:last-child, .tin-boxes i:last-child { border-right: 1px solid #000; }
.period-value { margin-left: auto; display: flex; align-items: center; gap: 5px; }
.date-value { width: 72px; height: 27px; padding: 5px 8px; display: inline-block; box-sizing: border-box; overflow: hidden; border: 1px solid #777; background: #fff; line-height: 15px; text-align: center; white-space: nowrap; }
.date-value:focus { outline: 2px solid #4b8df8; outline-offset: -2px; }
.bir-body { display: grid; grid-template-columns: 50% 50%; align-items: start; border-right: 2px solid #000; border-left: 2px solid #000; }
.bir-left { border-right: 2px solid #000; }
.form-section { border-bottom: 1px solid #000; }
.form-section:last-child { border-bottom: 0; }
.form-section h2 { height: 19px; margin: 0; padding: 2px 5px; display: grid; place-items: center; background: #bdbdbd; border-bottom: 1px solid #000; font-size: 10px; font-weight: 800; text-align: center; }
.info-row { min-height: 39px; display: flex; border-bottom: 1px solid #000; }
.info-row:last-child { border-bottom: 0; }
.info-main { min-width: 0; padding: 2px 5px 4px; display: grid; flex: 1; align-content: start; gap: 2px; }
.info-side { width: 96px; padding: 2px 4px; flex: 0 0 96px; border-left: 1px solid #000; text-align: left; }
.info-side strong { min-height: 20px; margin-top: 2px; padding: 3px; display: block; border: 1px solid #777; background: #fff; text-align: center; }
.field-label { font-size: 8px; }
.field-label b { margin-right: 3px; font-size: 10px; }
.field-value { min-height: 22px; padding: 4px 6px; overflow: hidden; border: 1px solid #777; background: #fff; font-size: 9px; text-transform: uppercase; }
.tin-boxes { margin: 0 0 0 16px; }
.tin-boxes i { width: 22px; height: 20px; background: #fff; }
.bir-info-pair { display: grid; grid-template-columns: 1fr 1fr; }
.bir-info-pair > .info-row:first-child { border-right: 1px solid #000; }
.employee-name-row { grid-template-columns: 80% 20%; }
.mwe-row { min-height: 35px; padding: 3px 6px; display: grid; grid-template-columns: 18px 19px 1fr; align-items: center; gap: 5px; border-bottom: 1px solid #000; text-align: center; }
.employer-type { height: 30px; padding: 3px 6px; display: flex; align-items: center; gap: 22px; border-bottom: 1px solid #000; }
.employer-type span { display: flex; align-items: center; gap: 5px; }
.check { width: 14px; height: 14px; display: inline-grid; place-items: center; border: 1px solid #000; font-style: normal; font-weight: 800; }
.amount-heading { height: 31px; display: grid; grid-template-columns: 1fr 190px; border-bottom: 0; background: #cfcfcf; font-weight: 800; }
.amount-heading span, .amount-heading strong { padding: 4px 6px; }
.amount-heading strong { text-align: center; }
.amount-heading.taxable { border-top: 1px solid #000; }
.subheading { height: 21px; padding: 4px 24px; background: #cfcfcf; font-weight: 800; text-align: left; }
.amount-table { width: 100%; border: 0; border-collapse: collapse; table-layout: fixed; }
.amount-table .number-column { width: 28px; }
.amount-table .amount-column { width: 190px; }
.amount-table th,
.amount-table td { height: 35px; padding: 3px 5px; border: 0; vertical-align: middle; font-size: 9px; font-weight: 400; }
.amount-table th { text-align: center; font-size: 9px; font-weight: 700; }
.amount-table tr.tall > * { height: 46px; }
.amount-table tr.compact > * { height: 22px; }
.amount-table td.amount { height: 27px; padding: 5px 7px; border: 4px solid #cfcfcf; background: #fff; box-shadow: inset 0 0 0 1px #777; text-align: right; font-family: "Courier New", monospace; font-size: 9px; font-weight: 700; }
.amount-table tr.tall td.amount { height: 38px; }
.amount-table tr.total > * { font-weight: 800; }
.page-one-declaration { min-height: 44px; padding: 4px 20px; border: 2px solid #000; border-top-width: 2px; background: #fff; font-size: 8px; line-height: 1.25; text-align: left; }
.bir-continuation { margin-top: 22px; padding: 16px 3px 0; background: #fff; }
.continuation-signatures { min-height: 54px; display: grid; grid-template-columns: 34px 1fr 76px 185px; align-items: end; gap: 8px; }
.continuation-number { align-self: center; text-align: center; }
.date-label { align-self: center; text-align: right; }
.date-boxes { display: flex; align-self: center; }
.date-boxes i { width: 23px; height: 27px; display: grid; place-items: center; border: 1px solid #777; border-right: 0; font-style: normal; }
.date-boxes i:last-child { border-right: 1px solid #777; }
.signature-box { display: grid; text-align: center; }
.signature-box strong { min-height: 19px; padding: 2px; border-bottom: 1px solid #000; text-transform: uppercase; }
.signature-box span { padding-top: 3px; font-size: 8px; }
.conforme { margin: 2px 0; font-weight: 800; }
.id-row { min-height: 42px; display: grid; grid-template-columns: 110px 130px 55px 100px 70px 185px 105px 1fr; align-items: end; gap: 4px; font-size: 8px; }
.id-row > b { height: 25px; border: 1px solid #777; }
.bir-continuation h3 { height: 20px; margin: 0 -3px; display: grid; place-items: center; border-top: 2px solid #000; border-bottom: 2px solid #000; background: #bdbdbd; font-size: 10px; }
.substituted-grid { display: grid; grid-template-columns: 1fr 1fr; }
.substituted-grid > div { min-height: 132px; padding: 5px 7px; position: relative; border-right: 2px solid #000; }
.substituted-grid > div:last-child { border-right: 0; }
.substituted-grid p { min-height: 78px; margin: 0 0 8px 25px; font-size: 8px; line-height: 1.2; text-align: justify; }
.substituted-grid .continuation-number { position: absolute; left: 9px; top: 81px; }
.substituted-grid small { display: block; text-align: center; }
.privacy-note { width: 1024px; margin: 0 auto; padding: 3px 7px 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 8px; }
@media (max-width: 767px) {
    .bir2316-toolbar { align-items: stretch; flex-direction: column; }
    .bir2316-actions { justify-content: flex-start; }
    .bir-form, .bir-continuation, .privacy-note { margin-left: 0; }
}
@media print {
    .bir2316-view { padding: 0 !important; background: #fff; }
    .bir2316-toolbar { display: none; }
    .bir2316-scroll { width: auto; overflow: visible; padding: 0; }
    .bir-form, .bir-continuation, .privacy-note { width: 100%; box-shadow: none; }
    .bir-continuation { break-before: page; margin-top: 0; }
}
</style>
