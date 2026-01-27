<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
      <title>Payroll Form</title>
      <style>
         .absences-cell {
         padding: 0;
         vertical-align: top;
         }
         .absences-table {
         width: 100%;
         height: 100%;
         border-collapse: collapse;
         table-layout: fixed;
         font-size: 8px;
         }
         .absences-table th,
         .absences-table td {
         border: 1px solid black;
         padding: 3px;
         }
         .absences-table th {
         width: 55%;
         text-align: left;
         font-weight: normal;
         }
         .absences-table td {
         width: 22.5%;
         }
         .abs-total th {
         text-align: center;
         font-weight: bold;
         border-top: 1px solid black;
         }
         .top-border {
         border-top: 1px solid black;
         }
         .net-pay {
         font-weight: bold;
         border-top: 1px solid black;
         }
         body {
         font-family: 'Poppins', sans-serif;
         font-size: 8px;
         margin: 20px;
         }
         .form-container {
         width: 350px;
         border: 1px solid black;
         padding: 5px 10px;
         }
         /* Top header lines */
         .top-row {
         display: flex;
         justify-content: space-between;
         font-weight: bold;
         margin-bottom: 5px;
         }
         .top-row input,
         .top-row select {
         border: none;
         border-bottom: 1px solid black;
         font-weight: normal;
         width: 90px;
         text-align: center;
         }
         .top-row .name-position,
         .top-row .dept-age {
         display: flex;
         justify-content: space-between;
         margin-bottom: 5px;
         }
         .top-row .field-group {
         display: flex;
         align-items: center;
         }
         .top-row .field {
         border: none;
         border-bottom: 1px solid black;
         width: 120px;
         text-align: center;
         font-weight: normal;
         font-size: 11px;
         }
         /* Table for Hours, Rate, Amount & Absences */
         .table-main {
         border-collapse: collapse;
         width: 100%;
         margin-bottom: 5px;
         }
         .table-main th, 
         .table-main td {
         border: 1px solid black;
         text-align: center;
         padding: 2px 4px;
         }
         .table-main th {
         font-weight: normal;
         font-size: 9px;
         }
         .table-main .hours-rate-amount th {
         border-right: none;
         }
         .table-main .deductions th {
         writing-mode: vertical-rl;
         transform: rotate(180deg);
         font-weight: bold;
         width: 12px;
         padding: 2px 0;
         }
         .table-main .deductions td {
         border-left: none;
         text-align: left;
         font-size: 8px;
         padding-left: 5px;
         vertical-align: top;
         height: 70px;
         }
         /* Deductions rows text alignment */
         .deductions-text {
         font-size: 8px;
         line-height: 1.2;
         padding-left: 3px;
         }
         /* Earnings and deductions totals */
         .totals-row td {
         border-top: none !important;
         font-weight: bold;
         text-align: right;
         padding-right: 5px;
         height: 16px;
         }
         /* Time in/out table */
         .time-table {
         width: 100%;
         border-collapse: collapse;
         font-size: 8px;
         margin-top: 20px;
         }
         .time-table th,
         .time-table td {
         border: 1px solid black;
         padding: 1px 3px;
         text-align: center;
         font-family: monospace;
         font-size: 8.5px;
         vertical-align: top;
         }
         .time-table th.days-col {
         width: 15px;
         vertical-align: middle;
         }
         .time-table th.daily-total {
         width: 30px;
         }
         .time-table tbody tr {
         height: 22px;
         }
         .time-table td.days-col {
         text-align: left;
         padding-left: 4px;
         font-weight: normal;
         }
         /* Time cell colors */
         .red-time {
         color: red;
         text-decoration: underline;
         font-weight: bold;
         }
         .black-time {
         color: black;
         text-decoration: underline;
         }
         /* Small certification text */
         .certify {
         font-size: 7px;
         margin-top: 16px;
         margin-bottom: 16px;
         font-style: italic;
         text-align: center;
         }
         /* Bottom footer */
         .footer {
         display: flex;
         justify-content: center;
         font-size: 7px;
         }
         .footer .model {
         font-weight: bold;
         line-height: 1.2;
         }
         .footer .signature {
         border-top: 1px solid black;
         width: 140px;
         text-align: center;
         }
      </style>
   </head>
   <body>
      <div style="display: flex; justify-content: center; gap: 10px;">
      <div class="form-container">
         <!-- Top header info -->
         <div class="top-row" style="margin-bottom: 3px;">
            <div>No. <input type="text" style="width:50px;" /></div>
            <div>Pay Ending <input type="text" style="width:90px;" /> 20 <input type="text" style="width:30px;" /></div>
         </div>
         <div class="top-row name-position">
            <div class="field-group">
               <label for="name">Name</label>
               <input type="text" id="name" class="field" />
            </div>
            <div class="field-group">
               <label for="position">Position</label>
               <input type="text" id="position" class="field" />
            </div>
         </div>
         <div class="top-row dept-age">
            <div class="field-group">
               <label for="dept">Dept.</label>
               <input type="text" id="dept" class="field" />
            </div>
            <div class="field-group">
               <label for="age">Age</label>
               <input type="text" id="age" class="field" />
            </div>
         </div>
         <!-- Hours, Rate, Amount, Deductions Table -->
         
         <table class="time-table">
            <thead>
               <tr>
                  <th class="days-col" rowspan="2">Days</th>
                  <th colspan="2">MORNING</th>
                  <th colspan="2">AFTERNOON</th>
                  <th colspan="2">OVERTIME</th>
                  <th class="daily-total" rowspan="2">Daily Total</th>
               </tr>
               <tr>
                  <th>IN</th>
                  <th>OUT</th>
                  <th>IN</th>
                  <th>OUT</th>
                  <th>IN</th>
                  <th>OUT</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td class="days-col">1</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">2</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">3</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">4</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">5</td>
                  <td class="red-time">10:59</td>
                  <td></td>
                  <td class="black-time">04:42</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">6</td>
                  <td class="black-time">06:38</td>
                  <td class="red-time">12:01</td>
                  <td class="red-time">12:07</td>
                  <td class="black-time">04:01</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">7</td>
                  <td class="black-time">06:57</td>
                  <td class="red-time">12:01</td>
                  <td class="red-time">12:01</td>
                  <td class="black-time">04:03</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">8</td>
                  <td class="black-time">06:50</td>
                  <td class="red-time">12:03</td>
                  <td class="red-time">12:03</td>
                  <td class="black-time">04:12</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">9</td>
                  <td class="black-time">06:51</td>
                  <td class="red-time">12:26</td>
                  <td class="red-time">12:26</td>
                  <td class="black-time">04:14</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">10</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">11</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">12</td>
                  <td class="red-time">11:03</td>
                  <td class="red-time">12:33</td>
                  <td class="red-time">12:33</td>
                  <td class="black-time">05:05</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">13</td>
                  <td class="black-time">06:51</td>
                  <td class="red-time">12:26</td>
                  <td class="red-time">12:26</td>
                  <td class="black-time">04:12</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">14</td>
                  <td class="black-time">07:10</td>
                  <td class="red-time">12:05</td>
                  <td class="red-time">12:06</td>
                  <td class="black-time">04:19</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
               <tr>
                  <td class="days-col">15</td>
                  <td class="black-time">06:47</td>
                  <td class="red-time">12:12</td>
                  <td class="red-time">12:12</td>
                  <td class="black-time">04:03</td>
                  <td></td>
                  <td></td>
                  <td></td>
               </tr>
            </tbody>
         </table>
         <div class="certify">I hereby certify that the above records are true and correct.</div>
         <div class="footer">
            <div class="signature">EMPLOYEE'S SIGNATURE</div>
         </div>
      </div>
   </body>
</html>