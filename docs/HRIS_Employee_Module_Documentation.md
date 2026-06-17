# HRIS Employee Module Documentation

**Coverage:** `/employee*`  
**Audience:** Employees, approvers, supervisors, and division chiefs

## Access

- Sign in using your assigned HRIS account.
- Available modules depend on your role, permissions, and employment type.
- Leave Application is available to eligible regular employees.
- Approval modules are available only to assigned approvers.
- Chief Corner is available only to division chiefs.

## Modules

| Module | Purpose |
|---|---|
| Dashboard | View HRIS summaries, pending applications, and announcements. |
| Timelogs | Record authorized web time, review attendance, request corrections, and download reports. |
| Calendar | View organization events, holidays, and schedules. |
| Announcements | Read organization and HRIS announcements. |
| Messages | Send and receive direct or group messages. |
| Credits | View leave and offset credit balances. |
| Payslip | View and download payslips by payroll period. |
| Behavioral Notices | Review assigned attendance or behavioral notices. |
| Applications | Submit and monitor employee requests. |
| Approvals | Approve or reject assigned employee requests. |
| Profile | Review and update employee information. |
| Chief Corner | Monitor division applications, timelogs, and credits. |

## Dashboard

**URL:** `/employee/dashboard`

Use the Dashboard to:

- Review employee summary information.
- Check pending applications.
- Read recent announcements.
- Open other modules from the sidebar.

## Timelogs

**URL:** `/employee/check-in-out`

### Record a Timelog

1. Open **Timelogs**.
2. Review the entries already recorded for the day.
3. Select the required action:
   - Time In
   - Break Out
   - Break In
   - Time Out
   - Overtime In
   - Overtime Out
4. Enter a daily accomplishment when required.
5. Confirm the entry.

Web time can only be used when authorized. Use the biometric device when web time is unavailable.

### Other Actions

- Review daily attendance records.
- Submit a correction for missing or incorrect entries.
- Monitor submitted correction requests.
- Download the Daily Time Record (DTR).
- Download the Daily Accomplishment Report (DAR), when available.

## Applications

| Application | URL | Required Information |
|---|---|---|
| Leave | `/employee/leaves` | Leave type, dates, reason, attachments, and approvers |
| Offset | `/employee/offset` | Dates, reason, attachments, and approvers |
| Overtime | `/employee/overtime` | Date, start time, end time, reason, and attachments |
| Pass Slip | `/employee/pass-slip` | Date or time details, reason, attachments, and approvers |
| Special Order | `/employee/special-order` | Date, order number, shift, location or hazard details, remarks, and attachments |
| Local Travel Order | `/employee/local-travel-order` | Date, travel order number, shift, hazard details, remarks, and attachments |

### Submit an Application

1. Open the required application module.
2. Select **Create** or **New Application**.
3. Complete all required fields.
4. Upload the required supporting documents.
5. Select the correct approver when requested.
6. Review the information.
7. Submit the application.

### Monitor an Application

- Open the application to view its details and approval history.
- Check its current status and approver remarks.
- Cancel or delete the request only when the option is available.
- Submit a new request when an existing request cannot be edited.

## Approvals

**URLs:**

- `/employee/approval-leaves`
- `/employee/approval-pass-slip`
- `/employee/approval-overtime`

### Process an Approval

1. Open the appropriate approval module.
2. Select the approval level, when applicable.
3. Open the request.
4. Verify the dates, reason, attachments, and previous actions.
5. Select **Approve** or **Reject**.
6. Provide a clear remark when rejecting a request.

## Credits

### Leave Credits

**URL:** `/employee/credits/leave`

- View available leave balances.
- Review leave credit transactions.
- Confirm the available balance before filing leave.

### Offset Credits

**URL:** `/employee/credits/offset`

- View earned, used, and remaining offset credits.
- Confirm the available balance before filing an offset request.

Report incorrect balances or transactions to HR.

## Payslip

**URL:** `/employee/payslip`

1. Select the month and year.
2. Select the payroll cutoff, when applicable.
3. Select **Search**.
4. Review earnings, deductions, and net pay.
5. Select **Download** to obtain a copy.

Report missing records or incorrect amounts to HR or Payroll.

## Calendar

**URL:** `/employee/calendar`

- View organization events, holidays, and schedules.
- Use the available date and search controls to locate an entry.

## Announcements

**URL:** `/employee/announcements`

- View current and previous announcements.
- Open an announcement to view its complete details and attachments.
- Use search to locate a specific announcement.

## Messages

**URL:** `/employee/messages`

- Open an existing conversation.
- Send direct or group messages.
- Create group conversations when authorized.
- Send supported attachments.

## Behavioral Notices

**URL:** `/employee/behavioral-notices`

- Review notices assigned to your account.
- Check the notice details and recorded information.
- Contact your supervisor or HR for clarification.

## Profile

**URL:** `/employee/profile`

Employees can review or update:

- Profile image
- Employee and biometric numbers
- Personal information
- Present and permanent addresses
- GSIS, PAG-IBIG, PhilHealth, SSS, TIN, and PhilSys numbers

Verify all information before saving. Contact HR when a record cannot or should not be changed directly.

## Password

Use **Change Password** from the employee account options.

- Use a strong, private password.
- Change the password immediately if it may have been exposed.
- Do not share account credentials.

## Chief Corner

**URL:** `/employee/chief-corner`

This module is available only to division chiefs.

| Section | Purpose |
|---|---|
| Overview | View recent applications and attendance highlights. |
| Applications | Review submitted applications within the division. |
| Timelogs | Review attendance summaries and daily records. |
| Credits | Review employee leave and offset balances. |

Use the available period, date, employment type, and application filters to refine the displayed records.

## Status Reference

| Status | Meaning |
|---|---|
| Pending | Waiting for an approver's action. |
| Approved | Accepted by an approver. Additional levels may still be required. |
| Rejected | Declined by an approver. Review the provided remarks. |
| Cancelled | Withdrawn by the employee. |
| Completed | The workflow is finished. |

## Troubleshooting

| Issue | Action |
|---|---|
| A module is not visible | Confirm your role and permissions with HR or the system administrator. |
| Web time is unavailable | Use the biometric device or confirm your web time access with HR. |
| A timelog is missing or incorrect | Submit a timelog correction request. |
| An application cannot be submitted | Check required fields, dates, approvers, attachments, and available credits. |
| Payslip data is missing or incorrect | Verify the selected payroll period, then contact HR or Payroll. |
| Profile changes cannot be saved | Check required fields and uploaded file requirements, then contact HR if needed. |
