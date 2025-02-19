<script src="{{asset('tabler/dist/js/tabler.min.js?1692870487')}}" defer></script>
<script src="{{asset('tabler/dist/js/demo.min.js?1692870487')}}" defer></script>
<script src="{{asset('tabler/dist/js/demo-theme.min.js?1692870487')}}"></script>
<script>
      // @formatter:off
      document.addEventListener("DOMContentLoaded", function () {
      	window.ApexCharts && (new ApexCharts(document.getElementById('chart-mentions'), {
      		chart: {
      			type: "bar",
      			fontFamily: 'inherit',
      			height: 240,
      			parentHeightOffset: 0,
      			toolbar: {
      				show: false,
      			},
      			animations: {
      				enabled: false
      			},
      			stacked: true,
      		},
      		plotOptions: {
      			bar: {
      				columnWidth: '50%',
      			}
      		},
      		dataLabels: {
      			enabled: false,
      		},
      		fill: {
      			opacity: 1,
      		},
      		series: [{
      			name: "Web",
      			data: [1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 2, 12, 5, 8, 22, 6, 8, 6, 4, 1, 8, 24, 29, 51, 40, 47, 23, 26, 50, 26, 41, 22, 46, 47, 81, 46, 6]
      		},{
      			name: "Social",
      			data: [2, 5, 4, 3, 3, 1, 4, 7, 5, 1, 2, 5, 3, 2, 6, 7, 7, 1, 5, 5, 2, 12, 4, 6, 18, 3, 5, 2, 13, 15, 20, 47, 18, 15, 11, 10, 0]
      		},{
      			name: "Other",
      			data: [2, 9, 1, 7, 8, 3, 6, 5, 5, 4, 6, 4, 1, 9, 3, 6, 7, 5, 2, 8, 4, 9, 1, 2, 6, 7, 5, 1, 8, 3, 2, 3, 4, 9, 7, 1, 6]
      		}],
      		tooltip: {
      			theme: 'dark'
      		},
      		grid: {
      			padding: {
      				top: -20,
      				right: 0,
      				left: -4,
      				bottom: -4
      			},
      			strokeDashArray: 4,
      			xaxis: {
      				lines: {
      					show: true
      				}
      			},
      		},
      		xaxis: {
      			labels: {
      				padding: 0,
      			},
      			tooltip: {
      				enabled: false
      			},
      			axisBorder: {
      				show: false,
      			},
      			type: 'datetime',
      		},
      		yaxis: {
      			labels: {
      				padding: 4
      			},
      		},
      		labels: [
      			'2020-06-20', '2020-06-21', '2020-06-22', '2020-06-23', '2020-06-24', '2020-06-25', '2020-06-26', '2020-06-27', '2020-06-28', '2020-06-29', '2020-06-30', '2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08', '2020-07-09', '2020-07-10', '2020-07-11', '2020-07-12', '2020-07-13', '2020-07-14', '2020-07-15', '2020-07-16', '2020-07-17', '2020-07-18', '2020-07-19', '2020-07-20', '2020-07-21', '2020-07-22', '2020-07-23', '2020-07-24', '2020-07-25', '2020-07-26'
      		],
      		colors: [tabler.getColor("primary"), tabler.getColor("primary", 0.8), tabler.getColor("green", 0.8)],
      		legend: {
      			show: false,
      		},
      	})).render();
      });
      // @formatter:on
</script>
<script>
        // Set theme based on session or default to light
        document.addEventListener('DOMContentLoaded', function () {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }

            // Event listener for theme toggle
            const darkModeButton = document.querySelector('a[href="?theme=dark"]');
            const lightModeButton = document.querySelector('a[href="?theme=light"]');

            darkModeButton.addEventListener('click', () => {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            });

            lightModeButton.addEventListener('click', () => {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            });
        });
</script>
<script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle changing the number of entries
            document.getElementById('entriesSelect').addEventListener('change', function () {
                window.location.href = '?entries=' + this.value;
            });

            // Live search functionality
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('#invoiceTableBody tr');

            searchInput.addEventListener('keyup', function () {
                const searchTerm = searchInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });
</script>
<script>
        document.addEventListener('DOMContentLoaded', function () {
                // Initialize List.js
                const list = new List('invoiceTableContainer', {
        sortClass: 'table-sort',
        listClass: 'table-tbody',
        valueNames: [
            'sort-no',
            'sort-invoice',
            'sort-supplier',
            'sort-orderdate',
            {
                name: 'sort-duedate',
                attr: 'data-date'
            },
            {
                name: 'sort-amount',
                attr: 'data-amount'
            },
            'sort-payment',
            'sort-status',
        ],
    });

                // Ensure "Show Entries" dropdown updates the URL
                document.getElementById('entriesSelect').addEventListener('change', function () {
                    window.location.href = '?entries=' + this.value;
                });

                // Enhanced search for formatted and raw amounts
                const searchInput = document.getElementById('searchInput');
                const tableRows = document.querySelectorAll('#invoiceTableBody tr');

                searchInput.addEventListener('keyup', function () {
                    const searchTerm = searchInput.value.toLowerCase();

                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        const rawAmount = row.querySelector('.raw-amount')?.textContent.toLowerCase() || '';

                        // Match either formatted text OR raw amount
                        row.style.display = (text.includes(searchTerm) || rawAmount.includes(searchTerm)) ? '' : 'none';
                    });
                });
            });
</script>
