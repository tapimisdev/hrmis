@php($snapshot = (array) ($record->snapshot_data ?? []))
<div class="header">
    <div class="title">BIR Form 2316</div>
    <div class="subtitle">Certificate of Compensation Payment / Tax Withheld for {{ $record->taxable_year }}</div>
</div>

<div class="grid">
    <div class="card">
        <h2>Employer Information</h2>
        <div class="row"><span class="label">Employer Name</span>{{ $record->employer_name ?: 'N/A' }}</div>
        <div class="row"><span class="label">Employer TIN</span>{{ $record->employer_tin ?: 'N/A' }}</div>
        <div class="row"><span class="label">Employer Address</span>{{ $record->employer_address ?: 'N/A' }}</div>
        <div class="row"><span class="label">RDO Code</span>{{ $record->rdo_code ?: 'N/A' }}</div>
    </div>
    <div class="card">
        <h2>Employee Information</h2>
        <div class="row"><span class="label">Employee Name</span>{{ $record->employee_name }}</div>
        <div class="row"><span class="label">TIN</span>{{ $record->employee_tin ?: 'N/A' }}</div>
        <div class="row"><span class="label">Address</span>{{ $record->employee_address ?: 'N/A' }}</div>
        <div class="row"><span class="label">Employee No.</span>{{ $record->employee_no }}</div>
        <div class="row"><span class="label">Position</span>{{ $record->position ?: 'N/A' }}</div>
        <div class="row"><span class="label">Employment Type</span>{{ $record->employment_type ?: 'N/A' }}</div>
    </div>
</div>

<div class="card">
    <h2>Compensation Details</h2>
    <table>
        <thead>
            <tr>
                <th>Field</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Gross Compensation Income</td><td class="text-right">{{ number_format((float) $record->gross_compensation_income, 2) }}</td></tr>
            <tr><td>Non-Taxable / Exempt Compensation</td><td class="text-right">{{ number_format((float) ($record->tax_exempt_bonus + $record->de_minimis), 2) }}</td></tr>
            <tr><td>Taxable Compensation Income</td><td class="text-right">{{ number_format((float) $record->net_taxable_income, 2) }}</td></tr>
            <tr><td>Tax Due</td><td class="text-right">{{ number_format((float) $record->annual_tax_due, 2) }}</td></tr>
            <tr><td>Tax Withheld</td><td class="text-right">{{ number_format((float) $record->tax_withheld, 2) }}</td></tr>
            <tr><td>Refund / Payable</td><td class="text-right">{{ number_format((float) $record->tax_refund_or_payable, 2) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Certification</h2>
    <div class="row"><span class="label">Employer Authorized Signatory</span>{{ data_get($snapshot, 'certification.authorized_signatory', 'N/A') }}</div>
    <div class="row"><span class="label">Employee Signature</span>{{ data_get($snapshot, 'certification.employee_signature', $record->employee_name) }}</div>
    <div class="row"><span class="label">Date Signed</span>{{ data_get($snapshot, 'certification.date_signed', optional($record->generated_at)->toDateString()) }}</div>
    <div class="row"><span class="label">Substitute Filing</span>{{ data_get($snapshot, 'certification.substitute_filing', false) ? 'Yes' : 'No' }}</div>
</div>
