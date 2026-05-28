<template>
  <div class="printed-payslip">
    <div class="printed-payslip__masthead">
      <img :src="logoSrc" class="printed-payslip__logo" alt="">
      <div class="printed-payslip__agency">
        TECHNOLOGY APPLICATION AND PROMOTION INSTITUTE
      </div>
    </div>

    <table class="printed-payslip__table">
      <colgroup>
        <col
          v-for="(width, column) in columnWidths"
          :key="column"
          :style="{ width: `${width}%` }"
        >
      </colgroup>
      <tbody>
        <tr>
          <td colspan="7" class="printed-payslip__title">
            ***** PAY SLIP *****<br>
            <span>for the month {{ monthYear }}</span>
          </td>
        </tr>

        <tr class="printed-payslip__info">
          <td colspan="7">NAME <span>:</span> <strong>{{ payslip.name }}</strong></td>
        </tr>

        <tr class="printed-payslip__info">
          <td colspan="7">DESIGNATION <span>:</span> <strong>{{ payslip.position }}</strong></td>
        </tr>

        <tr class="printed-payslip__head">
          <th>EARNINGS</th>
          <th colspan="2">AMOUNT</th>
          <th>DEDUCTIONS</th>
          <th colspan="2">AMOUNT</th>
          <th>NET AMOUNT</th>
        </tr>

        <tr
          v-for="(row, index) in detailRows"
          :key="index"
          class="printed-payslip__detail"
        >
          <td class="printed-payslip__center">{{ row.earningLabel }}</td>
          <td class="printed-payslip__peso">{{ row.earningAmount !== '' ? pesoSymbol : '' }}</td>
          <td class="printed-payslip__amount">{{ formatMoney(row.earningAmount) }}</td>
          <td>{{ row.deductionLabel }}</td>
          <td class="printed-payslip__peso printed-payslip__danger">{{ row.deductionAmount !== '' ? pesoSymbol : '' }}</td>
          <td class="printed-payslip__amount printed-payslip__danger">{{ formatMoney(row.deductionAmount) }}</td>
          <td></td>
        </tr>

        <tr class="printed-payslip__total">
          <td></td>
          <td class="printed-payslip__peso">{{ pesoSymbol }}</td>
          <td class="printed-payslip__amount">{{ formatMoney(monthlyRate) }}</td>
          <td></td>
          <td class="printed-payslip__peso printed-payslip__danger">{{ pesoSymbol }}</td>
          <td class="printed-payslip__amount printed-payslip__danger">{{ formatMoney(totalDeductions) }}</td>
          <td class="printed-payslip__amount printed-payslip__net-inline">{{ formatMoney(payslip.net_pay) }}</td>
        </tr>

        <tr
          v-for="blankRow in 3"
          :key="`blank-${blankRow}`"
          class="printed-payslip__blank"
        >
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>

        <tr class="printed-payslip__net-pay">
          <td colspan="7">
            <div class="printed-payslip__net-pay-line">
              <span>NET PAY</span>
              <span>:</span>
              <span>{{ pesoSymbol }}</span>
              <span>{{ formatMoney(payslip.net_pay) }}</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'CosTemplate',

  props: {
    payslip: {
      type: Object,
      required: true
    }
  },

  data() {
    return {
      pesoSymbol: '₱',
      logoSrc: '/img/dost-tapi-small.png',
      columnWidths: [24.8, 4.1, 11.6, 22, 4.1, 11.7, 21.7]
    };
  },

  computed: {
    monthYear() {
      const source = this.payslip.period_covered || '';
      const parts = source.split(' ');
      return parts.length >= 2 ? `${parts[0]} ${parts[1]}` : source;
    },

    payrollDate() {
      if (!this.payslip.payroll_date) {
        return this.payslip.remarks || '';
      }

      return new Date(`${this.payslip.payroll_date}T00:00:00`).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      });
    },

    monthlyRate() {
      return Number(this.payslip.monthly_rate || 0);
    },

    earnings() {
      const rows = this.payslip.earnings?.length
        ? this.payslip.earnings
        : [{ description: 'Monthly Salary', amount: this.monthlyRate }];

      return rows
        .map((earning) => ({
          description: String(earning.description || '').toUpperCase(),
          amount: Number(earning.amount || 0)
        }))
        .filter((earning, index) => index === 0 || earning.amount !== 0);
    },

    deductions() {
      const rows = (this.payslip.deductions || [])
        .map((deduction) => ({
          description: String(deduction.description || '').toUpperCase(),
          amount: Number(deduction.amount || 0)
        }));

      while (rows.length < 5) {
        rows.push({ description: '', amount: '' });
      }

      return rows;
    },

    detailRows() {
      const count = Math.max(this.earnings.length, this.deductions.length, 5);

      return Array.from({ length: count }, (_, index) => {
        const earning = this.earnings[index] || {};
        const deduction = this.deductions[index] || {};

        return {
          earningLabel: earning.description || '',
          earningAmount: earning.amount ?? '',
          deductionLabel: deduction.description || '',
          deductionAmount: deduction.amount ?? ''
        };
      });
    },

    totalDeductions() {
      return this.deductions.reduce((total, deduction) => {
        return total + (Number(deduction.amount) || 0);
      }, 0);
    }
  },

  methods: {
    formatMoney(value) {
      if (value === '' || value === null || value === undefined) {
        return '';
      }

      const amount = Number(value);
      if (Number.isNaN(amount)) {
        return '';
      }

      if (amount === 0) {
        return '-';
      }

      return amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }
  }
};
</script>

<style scoped lang="scss">
.printed-payslip {
  background: #ffffff;
  color: #000000;
  font-family: "Courier New", Courier, monospace;
  margin: 0 auto 34px;
  max-width: 100%;
  overflow-x: auto;
  padding: 8px 0 16px;
  width: 100%;
}

.printed-payslip__masthead {
  align-items: center;
  display: flex;
  gap: 38px;
  justify-content: center;
  margin: 18px auto 18px;
  min-width: 100%;
  width: 100%;
}

.printed-payslip__logo {
  display: block;
  height: 36px;
  object-fit: contain;
  width: 42px;
}

.printed-payslip__agency {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0;
  white-space: normal;
}

.printed-payslip__table {
  background: #ffffff;
  border: 1px solid #000000;
  border-collapse: collapse;
  font-size: 12px;
  line-height: 1.15;
  min-width: 100%;
  table-layout: fixed;
  width: 100%;
}

.printed-payslip__table td,
.printed-payslip__table th {
  border-left: 1px solid #000000;
  border-right: 1px solid #000000;
  height: 18px;
  overflow: hidden;
  padding: 1px 3px;
  text-overflow: clip;
  vertical-align: middle;
}

.printed-payslip__title {
  border-bottom: 1px solid #000000;
  font-size: 14px;
  font-weight: 700;
  height: 36px;
  line-height: 1.2;
  padding: 3px 5px !important;
  text-align: center;
}

.printed-payslip__title span {
  font-size: 12px;
  font-weight: 400;
}

.printed-payslip__info td {
  border-bottom: 0;
  border-top: 0;
  height: 17px;
  padding: 0 3px;
}

.printed-payslip__info:first-of-type td {
  padding-top: 2px;
}

.printed-payslip__info span {
  display: inline-block;
  text-align: center;
  width: 18px;
}

.printed-payslip__head th {
  border: 1px solid #000000;
  font-weight: 700;
  height: 17px;
  padding: 0 4px;
  text-align: center;
}

.printed-payslip__detail td {
  border-bottom: 0;
  border-left: 0;
  border-right: 0;
  border-top: 0;
  height: 17px;
  padding: 1px 5px;
}

.printed-payslip__center {
  text-align: center;
}

.printed-payslip__peso {
  text-align: center;
}

.printed-payslip__amount {
  text-align: right;
  white-space: nowrap;
}

.printed-payslip__danger {
  color: #9c0000;
}

.printed-payslip__total td {
  border-bottom: 0;
  border-left: 0;
  border-right: 0;
  border-top: 0;
  font-weight: 700;
  height: 18px;
  padding: 1px 5px;
}

.printed-payslip__total .printed-payslip__amount:not(.printed-payslip__net-inline),
.printed-payslip__total .printed-payslip__peso {
  border-top: 1px solid #000000;
}

.printed-payslip__net-inline {
  border-top: 0;
  color: #000000;
}

.printed-payslip__blank td {
  border-bottom: 0;
  border-left: 0;
  border-right: 0;
  border-top: 0;
  height: 13px;
}

.printed-payslip__net-pay td {
  border: 0;
  font-weight: 700;
  height: 32px;
  padding: 0 4px 8px;
  vertical-align: bottom;
}

.printed-payslip__net-pay-line {
  align-items: center;
  display: flex;
  gap: 14px;
  justify-content: flex-end;
  min-height: 24px;
  padding-right: 64px;
}

.printed-payslip__net-pay-line span:last-child {
  border-bottom: 1px solid #000000;
  min-width: 76px;
  padding: 0 4px;
  text-align: right;
}

@media (max-width: 767.98px) {
  .printed-payslip {
    max-width: 100%;
  }

  .printed-payslip__masthead,
  .printed-payslip__table {
    min-width: 738px;
  }
}

</style>
