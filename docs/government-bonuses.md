# Government Bonuses User Guide

## Purpose

The `Government Bonuses` module is used to generate payrolls for different government bonus types using:

- a bonus computation setup per bonus type
- a rule set per bonus type
- manual employee selection when you want to override rule results

This module is useful when each bonus has different qualification rules, but HR or payroll staff still need the option to include specific employees manually.

## Main Modules

### 1. Government Bonus Rules

Path:

- `Payroll > Government Bonus Rules`

Use this page to create and maintain the rule definition for each bonus type.

Each record here represents one government bonus type, such as:

- Mid-Year Bonus
- Year-End Bonus
- CNA Incentive
- Other future government bonus types

### 2. Government Bonuses

Path:

- `Payroll > Government Bonuses`

Use this page to generate the actual payroll for a selected bonus type and month.

## How To Use

### Step 1. Prepare the bonus rule

Before using the module, make sure the government bonus rule already defines:

- how the amount is computed
- what qualification rules should be checked
- whether the bonus type is active

### Step 2. Create the government bonus rule

Go to:

- `Payroll > Government Bonus Rules`

Create one record for each bonus type.

#### Field purpose

`Name`

- The display name of the bonus type.
- This is what users will select during payroll generation.
- Example: `Mid-Year Bonus`

`Slug`

- A unique system-friendly name for the bonus type.
- Usually lowercase with dashes.
- Example: `mid-year-bonus`

`Computation Type`

- Defines how the system computes the bonus amount.
- `Manual` starts the employee amount at `0.00` and lets payroll adjust later.
- `Fixed Amount` gives the same amount to every generated employee.
- `Percentage of Salary` computes the amount from the employee's latest salary.
- `Formula` lets the user enter a formula expression for the system to compute.

`Amount Value` or `Percentage Value`

- Used together with the selected computation type.
- For `Fixed Amount`, this is the actual monetary amount.
- For `Percentage of Salary`, this is the percentage rate.
- For `Manual`, this is not required.

`Formula Expression`

- Used when `Computation Type` is `Formula`.
- This is where the end user enters the formula.
- Allowed variables:
  - `salary`
  - `basic_salary`
  - `monthly_salary`
  - `years_of_service`
  - `months_of_service`
- Allowed operators:
  - `+`
  - `-`
  - `*`
  - `/`
  - comparison operators such as `<`, `>`, `<=`, `>=`, `==`, `!=`
  - ternary `condition ? true_value : false_value`
- Example:
  - `(salary * 0.5) + 1000`
  - `salary * years_of_service * 0.05`
  - `salary < 50000 ? 20000 : 10000`

`Computation Notes`

- Optional notes that describe the intended formula or basis.
- Use this when the bonus follows a policy, formula, or special handling that payroll users should remember.

`Service Date Basis`

- Tells the system which date base to use when checking service age.
- `Organization` uses the employee's organization hire date.
- `Company` uses the employee's company hire date.
- `Current Year` evaluates service from the start of the payroll year.

`Minimum Years of Service`

- The minimum number of years required for the employee to qualify by service length.
- Leave blank or set `0` if there is no minimum service requirement.

`Active Account`

- If `Required`, only active employees pass this rule.
- If `Ignore`, account status will not block rule matching.

`Work Shift`

- If `Required`, the employee must have a work/shift schedule in the selected payroll month.
- If `Ignore`, missing schedule will not block rule matching.

`Information`

- If `Required`, the employee must have complete employee records needed by payroll.
- If `Ignore`, incomplete profile data will not block rule matching.

`Salary`

- If `Required`, the employee must have a valid salary record for the payroll month.
- If `Ignore`, missing salary data will not block rule matching.

`Status`

- `Active` means the bonus type is available during payroll generation.
- `Inactive` means the bonus type is hidden from the generator list.

## How Payroll Generation Works

Go to:

- `Payroll > Government Bonuses > Create`

### Step 1: Create Payroll

#### Field purpose

`Label`

- The payroll title or reference label.
- This is shown in the payroll record and payroll header.
- Example: `Mid-Year Bonus 2026`

`Month`

- The target payroll month.
- The system uses this month to evaluate month-based payroll eligibility checks and salary-based computations.

`Employment Type`

- The employee type to include in the payroll.
- Current setup is for `Regular`.

`Government Bonus Type`

- The bonus rule set to use for generation.
- This also determines:
  - how the amount is computed
  - which qualification rules are checked during review

### Step 2: Employee Review

After clicking `Next`, the system checks employees and separates them into two groups.

#### Rule-Matched Employees

- These employees passed the configured qualification rules.
- They are automatically selected by default.
- You may:
  - keep them selected
  - uncheck them if you want to exclude them
  - use `Select all`
  - use `Clear`

#### Rule-Failed / Override Employees

- These employees failed one or more configured rules.
- You may manually check the employee to bypass the rule result and include them in the payroll.

#### Reason links

- Employees with failed checks show one or more reasons.
- Clicking the reason opens the employee information page in a new tab for review.

#### Total selected for generation

- This count shows how many employees will actually be generated.
- The payroll cannot be submitted if no employee is selected.

### Step 3: Approval

Select the required payroll approvers.

This step follows the same approval behavior used by other payroll modules.

## After Generation

After submission, the system creates the payroll and processes selected employees.

In the generated payroll page, you can:

- view generated employees
- view bonus amount
- adjust payroll line amounts
- add remarks
- delete an employee payroll line if needed
- update payroll status using the normal payroll status flow

## Eligibility Summary

An employee is included automatically only when all of the following are satisfied:

- the employee belongs to the selected employment type
- the employee passes the configured rule checks

An employee may still be included manually when:

- the employee failed a configurable rule
- but is still checked during payroll generation

## Recommended Setup Process

1. Create the government bonus rule.
2. Set the computation type and computation value if needed.
3. Set the qualification rules.
4. Generate the payroll from `Government Bonuses`.
5. Review rule-matched and override employees.
6. Submit for processing.

## Common Notes

`Why is an employee not automatically selected?`

- The employee may have failed one or more rules.
- Check the remarks shown in the review step.

`What happens if I check an Override employee?`

- The employee is included in the payroll even if they failed the configured rules.
- This is the manual bypass behavior requested for the module.

`What happens if a bonus type is inactive?`

- It will not appear in the payroll generation form.

## Suggested End-User Reminder

Before generating a government bonus payroll, always verify:

- the correct bonus type rule is active
- the computation type and computation value are correct
- the review step selected employees match the intended final payroll
