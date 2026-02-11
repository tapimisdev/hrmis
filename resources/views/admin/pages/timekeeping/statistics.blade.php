@extends('admin.layouts.app')
@section('styles')
<style>
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
         <div class="col-md-6">
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
         <div class="col-md-6">
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
         <div class="col-md-6">
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
         <div class="col-md-6">
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
         <div class="col-md-6">
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
         <div class="col-md-6">
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
      </div>
      <div class="row mt-4">
         <div class="col-md-6">
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
         <div class="col-md-6">
            <div class="card shadow">
               <div class="card-body pb-4">
                  <h6 class="fw-bold mb-3 text-uppercase mb-4">Break Discrepancies</h6>
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
      </div>
   </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    // Declare chart instances to manage them globally
    let chartInstances = {
        pieChart: null,
        doughnutChart: null,
        barChartAbsences: null,
        lineChartUndertimes: null,
        polarAreaChartLates: null,
        barChartLeaves: null,
        barChartOffsets: null,
        barChartBreakDiscrepancies: null
    };

    // Show skeletons on initial load
    showSkeletons();

    // Event listener for month/year select changes
    $('select[name="month"], select[name="year"]').on('change', function() {
        // Destroy existing charts first
        Object.keys(chartInstances).forEach(key => {
            if (chartInstances[key]) {
                chartInstances[key].destroy();
                chartInstances[key] = null;
            }
        });

        const month = $('select[name="month"]').val();
        const year = $('select[name="year"]').val();

        if (!month || !year) return;

        const formattedDate = `${year}-${month.toString().padStart(2, '0')}`;

        showSkeletons();
        loadCharts(formattedDate);
    });

    // Function to hide loading skeletons
    function hideSkeletons() {
        $('.chart-skeleton').hide();
    }

    function showSkeletons() {
        $('.chart-skeleton').show();
    }

    // Function to load and render charts
    function loadCharts(monthYear) {
        // Show loading indicator
        $('#loading').show();

        $.ajax({
            url: '/admin/timekeeping/statistics',
            method: 'GET',
            data: { monthYear: monthYear },
            success: function(response) {
                hideSkeletons();
                $('#loading').hide();
                if (response.status === 'success') {
                    const resultData = response.data;
                    renderCharts(resultData);
                } else {
                    console.error('API Error:', response.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                hideSkeletons();
                $('#loading').hide();
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to render all charts with the fetched data
    function renderCharts(resultData) {
        // Helper functions
        function extract(source, key) {
            let labels = [];
            let values = [];

            $.each(source || [], function (_, item) {
                if (!item?.employee) return;
                labels.push([item.employee.lastname, '('+item.employee.employee_no+')']);
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

        function formatDates(dates) {
            return dates?.length ? dates.join(', ') : 'None';
        }

        // PIE – Overall Distribution
        chartInstances.pieChart = new Chart($('#pieChart'), {
            type: 'pie',
            data: {
                labels: ['Absences', 'Lates', 'Undertimes', 'Discrepancies', 'Leaves', 'Offsets'],
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
            options: { 
                responsive: true,
                animation: {
                    duration: 1200,         
                    easing: 'easeOutQuart'   
                }
            }
        });

        // DOUGHNUT – Lates vs Undertimes
        chartInstances.doughnutChart = new Chart($('#doughnutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Lates', 'Undertimes'],
                datasets: [{
                    data: [
                        sum(resultData.topLate, 'lates'),
                        sum(resultData.topUndertime, 'undertimes')
                    ]
                }]
            },
            options: { 
                responsive: true,
                animation: {
                    duration: 1200,         
                    easing: 'easeOutQuart'   
                }
            }
        });

        // BAR – Absences (with details)
        const absences = extract(resultData.topAbsent, 'absences');
        chartInstances.barChartAbsences = new Chart($('#barChartAbsences'), {
            type: 'bar',
            data: {
                labels: absences.labels,
                datasets: [{ label: 'Absences', data: absences.values }]
            },
            options: {
                animation: {
                    duration: 1200,         
                    easing: 'easeOutQuart'   
                },
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topAbsent, ctx.dataIndex);
                                if (!item) return '';
                                return [
                                    'Absence Dates: ' + formatDates(item.details.absence_dates),
                                    'Break Out/In Dates: ' + formatDates(item.details.breakOutInDiscrepancy_dates)
                                ];
                            }
                        }
                    }
                }
            }
        });

        // LINE – Undertimes (with dates)
        const undertimes = extract(resultData.topUndertime, 'undertimes');
        chartInstances.lineChartUndertimes = new Chart($('#lineChartUndertimes'), {
            type: 'line',
            data: {
                labels: undertimes.labels,
                datasets: [{ label: 'Undertimes', data: undertimes.values }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topUndertime, ctx.dataIndex);
                                return item ? 'Undertime Dates: ' + formatDates(item.details.undertime_dates) : '';
                            }
                        }
                    }
                }
            }
        });

        // POLAR – Lates (with dates)
        const lates = extract(resultData.topLate, 'lates');
        chartInstances.polarAreaChartLates = new Chart($('#polarAreaChartLates'), {
            type: 'polarArea',
            data: {
                labels: lates.labels,
                datasets: [{ data: lates.values }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topLate, ctx.dataIndex);
                                return item ? 'Late Dates: ' + formatDates(item.details.late_dates) : '';
                            }
                        }
                    }
                }
            }
        });

        // BAR – Leaves
        const leaves = extract(resultData.topLeave, 'leaves');
        chartInstances.barChartLeaves = new Chart($('#barChartLeaves'), {
            type: 'bar',
            data: {
                labels: leaves.labels,
                datasets: [{ label: 'Leaves', data: leaves.values }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topLeave, ctx.dataIndex);
                                return item ? 'Leave Dates: ' + formatDates(item.details.leave_dates) : '';
                            }
                        }
                    }
                }
            }
        });

        // BAR – Offsets
        const offsets = extract(resultData.topOffset, 'offsets');
        chartInstances.barChartOffsets = new Chart($('#barChartOffsets'), {
            type: 'bar',
            data: {
                labels: offsets.labels,
                datasets: [{ label: 'Offsets', data: offsets.values }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topOffset, ctx.dataIndex);
                                return item ? 'Offset Dates: ' + formatDates(item.details.offset_dates) : '';
                            }
                        }
                    }
                }
            }
        });

        // BAR – Break Out/In Discrepancies
        const breakOut = extract(resultData.topBreakOutInDiscrepancies, 'breakOutInDiscrepancies');
        chartInstances.barChartBreakDiscrepancies = new Chart($('#barChartBreakDiscrepancies'), {
            type: 'bar',
            data: {
                labels: breakOut.labels,
                datasets: [{ label: 'Break Out/In', data: breakOut.values }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(ctx) {
                                const item = getItemByIndex(resultData.topBreakOutInDiscrepancies, ctx.dataIndex);
                                return item ? 'Break Out/In Dates: ' + formatDates(item.details.breakOutInDiscrepancy_dates) : '';
                            }
                        }
                    }
                }
            }
        });
    }

    // Get current month/year as default
    const currentDate = new Date();
    const defaultMonthYear = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`;

    // Load charts on page load with default monthYear
    loadCharts(defaultMonthYear);
});
</script>
@endsection