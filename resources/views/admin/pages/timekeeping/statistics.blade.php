@extends('admin.layouts.app')
@section('styles')
<style>
    #chartjs-tooltip {
        background: inherit;
        border-radius: 8px;
        padding: 10px;
        width: 300px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        pointer-events: auto;
    }

    .tooltip-scroll {
        max-height: 200px;
        overflow-y: auto;
        margin-top: 8px;
        font-size: 13px;
    }

   canvas{max-height:400px}
   .card-body{
   position:relative;
   overflow:hidden;
   }
   /* ================= SKELETON BASE ================= */
   .chart-skeleton{
   position:absolute;
   inset:55px 12px 12px;
   border-radius:12px;
   background: #e6e6e625;
   z-index:20;
   overflow:hidden;
   }
   .chart-skeleton::after{
   content:"";
   position:absolute;
   inset:0;
   background:linear-gradient(100deg,
   transparent 20%,
   rgba(255,255,255,.6) 40%,
   transparent 60%);
   animation:shimmer 1.3s infinite;
   }
   @keyframes shimmer{
   0%{transform:translateX(-100%)}
   100%{transform:translateX(100%)}
   }
   .chart-skeleton.hidden{
   opacity:0;
   pointer-events:none;
   transition:.4s ease;
   }
   /* ================= SHAPES ================= */
   .skeleton-center{
   position:absolute;
   inset:0;
   display:flex;
   align-items:center;
   justify-content:center;
   }
   .skeleton-ring{
   width:180px;
   height:180px;
   border-radius:50%;
   background: radial-gradient(circle,#d0d0d0 55%, rgba(100, 100, 100, 0.8)6%);
   }
   /* bars */
   .skeleton-bars{
   display:flex;
   align-items:flex-end;
   justify-content:space-between;
   height:100%;
   padding:20px;
   }
   .skeleton-bar{
   width:16%;
   background:#d0d0d0;
   border-radius:6px;
   animation:pulse 1.2s infinite;
   }
   .skeleton-bar:nth-child(1){height:40%}
   .skeleton-bar:nth-child(2){height:70%}
   .skeleton-bar:nth-child(3){height:55%}
   .skeleton-bar:nth-child(4){height:85%}
   .skeleton-bar:nth-child(5){height:60%}
   @keyframes pulse{
   0%,100%{opacity:.6}
   50%{opacity:1}
   }
   /* lines */
   .skeleton-chart-line{
   padding:24px;
   height:100%;
   display:flex;
   flex-direction:column;
   justify-content:space-between;
   }
   .skeleton-line{
   height:6px;
   background:#d0d0d0;
   border-radius:6px;
   }
   .skeleton-line:nth-child(1){width:90%}
   .skeleton-line:nth-child(2){width:70%}
   .skeleton-line:nth-child(3){width:95%}
   .skeleton-line:nth-child(4){width:60%}
   .skeleton-line:nth-child(5){width:85%}
</style>
@endsection
@section('content')
<div class="container-fluid">
   <x-header title="Timelogs Statistics" subtitle="View Different Report Statistics"/>
   <div class="container">
        <div class="card shadow card-body mb-5">
            <div class="controls pb-3">
                <h6 class="text-uppercase fw-bold">Apply Filters</h6>
                <div class="row mt-3">
                    <div class="col-12 col-md-3">
                        <label class="mb-2">Month</label>
                        <select name="month" class="form-select">
                            @foreach($months as $key => $name)
                                <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="mb-2">Year</label>
                        <select name="year" class="form-select">
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $key == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
      <div class="row">
        <div class="col-md-12 mb-5">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">ESS Portal Statuses</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-center">
                        <div class="skeleton-ring"></div>
                     </div>
                  </div>
                  <canvas id="noLoginChart"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Overall Distribution</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-center">
                        <div class="skeleton-ring"></div>
                     </div>
                  </div>
                  <canvas id="pieChart"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Lates vs Undertimes</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-center">
                        <div class="skeleton-ring"></div>
                     </div>
                  </div>
                  <canvas id="doughnutChart"></canvas>
               </div>
            </div>
         </div>
      </div>
      <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Lates</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-center">
                        <div class="skeleton-ring"></div>
                     </div>
                  </div>
                  <canvas id="polarAreaChartLates"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Undertimes</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-chart-line">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                     </div>
                  </div>
                  <canvas id="lineChartUndertimes"></canvas>
               </div>
            </div>
         </div>
      </div>
      <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Absences</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-bars">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                     </div>
                  </div>
                  <canvas id="barChartAbsences"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Break In/Out Discrepancies</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-bars">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                     </div>
                  </div>
                  <canvas id="barChartBreakDiscrepancies"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Leaves</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-bars">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                     </div>
                  </div>
                  <canvas id="barChartLeaves"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Offsets</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-bars">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                     </div>
                  </div>
                  <canvas id="barChartOffsets"></canvas>
               </div>
            </div>
         </div>
         <div class="col-md-6 mb-4">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Special Order</h6>
                  <div class="chart-skeleton">
                     <div class="skeleton-bars">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                     </div>
                  </div>
                  <canvas id="barChartSO"></canvas>
               </div>
            </div>
         </div>
          <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-body pb-4">
                        <h6 class="fw-bold mb-3 text-uppercase mb-4">Top Pass Slip / OBS</h6>
                        <div class="chart-skeleton">
                            <div class="skeleton-bars">
                                <div class="skeleton-bar"></div>
                                <div class="skeleton-bar"></div>
                                <div class="skeleton-bar"></div>
                                <div class="skeleton-bar"></div>
                                <div class="skeleton-bar"></div>
                            </div>
                        </div>
                        <canvas id="barChartOBS"></canvas>
                    </div>
                </div>
            </div>
      </div>
   </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {

    let chartInstances = {};
    let currentRequest = null;

    /* =============================
       BASIC UTILITIES
    ============================== */

    function destroyCharts() {
        Object.values(chartInstances).forEach(chart => {
            if (chart) chart.destroy();
        });
        chartInstances = {};
    }

    function showSkeletons() { $('.chart-skeleton').show(); }
    function hideSkeletons() { $('.chart-skeleton').hide(); }

    function extract(source, key) {
        const labels = [];
        const values = [];

        $.each(source || [], function (_, item) {
            if (!item?.employee) return;
            labels.push(`${item.employee.lastname} (${item.employee.employee_no})`);
            values.push(Number(item[key] ?? 0));
        });

        return { labels, values };
    }

    function sum(source, key) {
        let total = 0;
        $.each(source || [], function (_, item) {
            total += Number(item[key] ?? 0);
        });
        return total;
    }

    function getItemByIndex(source, index) {
        const items = Object.values(source || {}).filter(i => i?.employee);
        return items[index] ?? null;
    }

    function formatGroupedDates(dates) {
        if (!dates?.length) return [];

        const grouped = {};

        dates.forEach(dateStr => {
            const date = new Date(dateStr);
            const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
            const day = date.getDate();

            if (!grouped[month]) grouped[month] = [];
            grouped[month].push(day);
        });

        return Object.entries(grouped).map(([month, days]) => {
            days.sort((a,b)=>a-b);
            return `${month} ${days.join(', ')}`;
        });
    }

    function buildDateTooltip(source, dateKey) {
        return function (ctx) {
            const item = getItemByIndex(source, ctx.dataIndex);
            if (!item) return '';

            const rawDates = item.details?.[dateKey] || [];
            if (!rawDates.length) return 'DATES: None';

            const formattedLines = formatGroupedDates(rawDates);

            return [
                '',
                'DATES:',
                '────────────',
                ...formattedLines
            ];
        };
    }

    /* =============================
       CHART BUILDERS
    ============================== */

    function createBarChart(instanceKey, elementId, label, source, valueKey, dateKey) {
        const extracted = extract(source, valueKey);

        const backgroundColors = extracted.values.map(() => {
            const r = Math.floor(Math.random() * 256);
            const g = Math.floor(Math.random() * 256);
            const b = Math.floor(Math.random() * 256);
            return `rgba(${r}, ${g}, ${b}, 0.9)`; 
        });

        chartInstances[instanceKey] = new Chart($(elementId), {
            type: 'bar',
            data: {
                labels: extracted.labels,
                datasets: [{
                    label: label,
                    data: extracted.values,
                    backgroundColor: backgroundColors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            afterLabel: buildDateTooltip(source, dateKey)
                        }
                    }
                }
            }
        });
    }

    function createLineChart(instanceKey, elementId, label, source, valueKey, dateKey, color) {
        const extracted = extract(source, valueKey);

        chartInstances[instanceKey] = new Chart($(elementId), {
            type: 'line',
            data: {
                labels: extracted.labels,
                datasets: [{
                    label: label,
                    data: extracted.values,
                    borderColor: color,
                    backgroundColor: color + '33',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: {
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            afterLabel: buildDateTooltip(source, dateKey)
                        }
                    }
                }
            }
        });
    }

    /* =============================
       LOAD DATA
    ============================== */

    function loadCharts(monthYear) {
        if (!monthYear) return;

        if (currentRequest) currentRequest.abort();

        destroyCharts();
        showSkeletons();
        $('#loading').show();

        currentRequest = $.ajax({
            url: '/admin/timekeeping/statistics',
            method: 'GET',
            data: { monthYear },
            dataType: 'json'
        });

        currentRequest
            .done(function (response) {
                if (response?.status !== 'success') {
                    console.error(response?.message || 'Invalid API');
                    return;
                }

                renderCharts(response.data);
            })
            .always(function () {
                hideSkeletons();
                $('#loading').hide();
                currentRequest = null;
            });
    }

    /* =============================
       RENDER CHARTS
    ============================== */

    function renderCharts(resultData) {

        /* ========= ESS PORTAL STATUS ========= */

        const accessed = resultData.loginAccessed ?? {};
        const notAccessed = resultData.loginNotAccessed ?? {};

        const accessedCount = Number(accessed.count ?? 0);
        const notAccessedCount = Number(notAccessed.count ?? 0);
        const totalEmployees = accessedCount + notAccessedCount;

        chartInstances.noLoginChart = new Chart($('#noLoginChart'), {
            type: 'pie',
            data: {
                labels: ['Accessed', 'Not Accessed'],
                datasets: [{
                    data: [accessedCount, notAccessedCount]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `EMPLOYEES: ${totalEmployees}`
                    }
                }
            }
        });

        /* ========= LATE VS UNDERTIME ========= */

        chartInstances.doughnutChart = new Chart($('#doughnutChart'), {
            type: 'doughnut',
            data: {
                labels: ['LATES', 'UNDERTIMES'],
                datasets: [{
                    data: [
                        sum(resultData.topLate, 'lates'),
                        sum(resultData.topUndertime, 'undertimes')
                    ]
                }]
            },
            options: { responsive: true }
        });

        /* ========= OVERALL DISTRIBUTION ========= */

        chartInstances.pieChart = new Chart($('#pieChart'), {
            type: 'pie',
            data: {
                labels: ['ABSENCES','LATES','UNDERTIMES','DISCREPANCIES','LEAVES','OFFSETS'],
                datasets: [{
                    data: [
                        sum(resultData.topAbsent, 'absences'),
                        sum(resultData.topLate, 'lates'),
                        sum(resultData.topUndertime, 'undertimes'),
                        sum(resultData.topBreakOutInDiscrepancies, 'breakOutInDiscrepancies'),
                        sum(resultData.topLeave, 'leaves'),
                        sum(resultData.topOffset, 'offsets')
                    ]
                }]
            },
            options: { responsive: true }
        });

        /* ========= METRIC CHARTS ========= */

        createBarChart('barChartAbsences','#barChartAbsences','ABSENCES',
            resultData.topAbsent,'absences','absence_dates');

        createLineChart('lineChartUndertimes','#lineChartUndertimes','UNDERTIME',
            resultData.topUndertime,'undertimes','undertime_dates','#e74a3b');

        createLineChart('lineChartLates','#polarAreaChartLates','LATES',
            resultData.topLate,'lates','late_dates','#4e73df');

        createBarChart('barChartLeaves','#barChartLeaves','LEAVES',
            resultData.topLeave,'leaves','leave_dates');

        createBarChart('barChartOffsets','#barChartOffsets','OFFSETS',
            resultData.topOffset,'offsets','offset_dates');

        createBarChart('barChartSO','#barChartSO','SPECIAL ORDER',
            resultData.topSO,'special_order','special_order_dates');

        createBarChart('barChartOBS','#barChartOBS','PASS SLIP / OBS',
            resultData.topOBS,'obs','obs_dates');

        createBarChart('barChartBreakDiscrepancies','#barChartBreakDiscrepancies',
            'BREAKIN/BREAKOUT DISCREPANCIES',
            resultData.topBreakOutInDiscrepancies,
            'breakOutInDiscrepancies',
            'breakOutInDiscrepancy_dates');
    }

    /* =============================
       EVENTS
    ============================== */

    $('select[name="month"], select[name="year"]').on('change', function () {
        const month = $('select[name="month"]').val();
        const year = $('select[name="year"]').val();
        if (!month || !year) return;

        loadCharts(`${year}-${month.padStart(2,'0')}`);
    });

    /* =============================
       INITIAL LOAD
    ============================== */

    const now = new Date();
    const defaultMonthYear = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
    loadCharts(defaultMonthYear);

});
</script>
@endsection