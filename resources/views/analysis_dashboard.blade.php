<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Penjualan Motor dengan AI</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f7f6; }
        .container { max-width: 900px; margin: auto; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        /* You can remove the button styling if you remove the button entirely */
        button {
            padding: 12px 25px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        button:hover { background-color: #0056b3; }
        pre, div[id$="Output"] {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap; /* Wrap text */
            word-wrap: break-word; /* Break long words */
            font-family: monospace;
            margin-bottom: 20px;
        }
        #pythonAnalysisOutput { background-color: #e6f7ff; border-color: #cceeff; }
        #gemmaInsightOutput { background-color: #e0f7fa; border-color: #b2ebf2; }
        p.error { color: red; font-weight: bold; }
        @keyframes blink {
            50% { opacity: 0; }
        }
        .blinking-cursor {
            animation: blink 1s infinite;
        }
        .chart-container {
            width: 100%;
            height: 400px;
            margin-bottom: 30px;
        }
    </style>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
</head>
<body>
    <div class="container">
        <h1>Analisis Penjualan Motor dengan AI</h1>
        <h2>Grafik Penjualan Tahunan:</h2>
        <div id="salesByYearChart" class="chart-container">
            <p><i>Memuat grafik penjualan tahunan...</i></p>
        </div>

        <h2>Grafik Penjualan Bulanan (Keseluruhan):</h2>
        <div id="salesByMonthChart" class="chart-container">
            <p><i>Memuat grafik penjualan bulanan...</i></p>
        </div>

        <h2>Hasil Analisis Python (Raw Data):</h2>
        <pre id="pythonAnalysisOutput"><i>Memulai analisis Python...</i></pre>

        <h2>Insight dari Gemma:</h2>
        <div id="gemmaInsightOutput">
            <p><i>Menganalisis dengan AI...</i></p>
        </div>
    </div>

    <script>
        // Function to render charts using Highcharts
        function renderCharts(analysisData) {
            // Data for Sales by Year Chart
            const years = Object.keys(analysisData.sales_by_year);
            const salesAnnual = Object.values(analysisData.sales_by_year);

            Highcharts.chart('salesByYearChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Penjualan Motor Per Tahun'
                },
                xAxis: {
                    categories: years,
                    title: {
                        text: 'Tahun'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Total Penjualan'
                    },
                    labels: {
                        formatter: function () {
                            return Highcharts.numberFormat(this.value, 0, ',', '.');
                        }
                    }
                },
                tooltip: {
                    valueSuffix: ' unit',
                    formatter: function () {
                        return '<b>' + this.x + '</b><br/>' +
                               'Penjualan: ' + Highcharts.numberFormat(this.y, 0, ',', '.');
                    }
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: [{
                    name: 'Penjualan',
                    data: salesAnnual
                }]
            });

            // Data for Sales by Month Chart
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const salesMonthlyRaw = analysisData.sales_by_month_overall;
            const sortedMonthNumbers = Object.keys(salesMonthlyRaw).sort((a, b) => parseInt(a) - parseInt(b));
            const salesMonthly = sortedMonthNumbers.map(monthNum => salesMonthlyRaw[monthNum]);

            Highcharts.chart('salesByMonthChart', {
                chart: {
                    type: 'line'
                },
                title: {
                    text: 'Penjualan Motor Per Bulan (Agregat)'
                },
                xAxis: {
                    categories: months,
                    title: {
                        text: 'Bulan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Total Penjualan'
                    },
                    labels: {
                        formatter: function () {
                            return Highcharts.numberFormat(this.value, 0, ',', '.');
                        }
                    }
                },
                tooltip: {
                    valueSuffix: ' unit',
                    formatter: function () {
                        return '<b>' + this.x + '</b><br/>' +
                               'Penjualan: ' + Highcharts.numberFormat(this.y, 0, ',', '.');
                    }
                },
                series: [{
                    name: 'Penjualan',
                    data: salesMonthly
                }]
            });
        }


        // --- New Function to handle the analysis process ---
        function startAnalysis() {
            const pythonOutputDiv = document.getElementById('pythonAnalysisOutput');
            const gemmaInsightDiv = document.getElementById('gemmaInsightOutput');
            const salesByYearChartDiv = document.getElementById('salesByYearChart');
            const salesByMonthChartDiv = document.getElementById('salesByMonthChart');

            // Clear previous content and show loading messages
            pythonOutputDiv.innerText = 'Memulai analisis Python...';
            gemmaInsightDiv.innerHTML = '<p><i>Menganalisis dengan AI...</i></p>';
            salesByYearChartDiv.innerHTML = '<p><i>Memuat grafik penjualan tahunan...</i></p>'; // Clear chart div
            salesByMonthChartDiv.innerHTML = '<p><i>Memuat grafik penjualan bulanan...</i></p>'; // Clear chart div


            fetch('/api/analyze-motor-sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({})
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || JSON.stringify(errorData));
                    });
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('text/event-stream')) {
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let gemmaFullText = '';
                    gemmaInsightDiv.innerHTML = '';

                    function readStream() {
                        reader.read().then(({ done, value }) => {
                            if (done) {
                                console.log('Stream complete');
                                gemmaInsightDiv.innerHTML = gemmaFullText;
                                return;
                            }

                            const chunk = decoder.decode(value, { stream: true });
                            const lines = chunk.split('\n').filter(line => line.startsWith('data: '));

                            lines.forEach(line => {
                                try {
                                    const dataJson = line.substring(6);
                                    const eventData = JSON.parse(dataJson);

                                    if (eventData.type === 'python_analysis') {
                                        pythonOutputDiv.innerText = JSON.stringify(eventData.data, null, 2);
                                        renderCharts(eventData.data);
                                    } else if (eventData.type === 'gemma_insight') {
                                        gemmaFullText += eventData.text;
                                        gemmaInsightDiv.innerHTML = gemmaFullText + ' <span class="blinking-cursor">|</span>';
                                    } else if (eventData.type === 'error') {
                                        gemmaInsightDiv.innerHTML = `<p class="error">Error: ${eventData.message}</p>`;
                                        pythonOutputDiv.innerText += `\nError: ${eventData.message}`;
                                    } else if (eventData.type === 'end_stream') {
                                        console.log('End of stream signal received.');
                                    }
                                } catch (e) {
                                    console.error("Error parsing JSON from stream:", e, line);
                                    gemmaInsightDiv.innerHTML = `<p class="error">Error parsing AI response.</p>`;
                                }
                            });
                            readStream();
                        }).catch(error => {
                            gemmaInsightDiv.innerHTML = `<p class="error">Kesalahan koneksi streaming: ${error.message}</p>`;
                            console.error('Fetch stream error:', error);
                        });
                    }
                    readStream();

                } else {
                    response.json().then(data => {
                        pythonOutputDiv.innerText = JSON.stringify(data, null, 2);
                        gemmaInsightDiv.innerHTML = '<p class="error">Error: Tidak ada streaming insight dari AI.</p>';
                    }).catch(error => {
                        pythonOutputDiv.innerText = 'Terjadi kesalahan saat memproses respons: ' + error;
                        console.error('Error:', error);
                    });
                }
            })
            .catch(error => {
                pythonOutputDiv.innerText = 'Terjadi kesalahan umum: ' + error.message;
                gemmaInsightDiv.innerHTML = `<p class="error">Kesalahan umum: ${error.message}</p>`;
                console.error('Error:', error);
            });
        }

        // --- Call the startAnalysis function when the DOM is fully loaded ---
        document.addEventListener('DOMContentLoaded', startAnalysis);

        // You can comment out or remove the button and its event listener if not needed
        // document.getElementById('runAnalysisButton').addEventListener('click', startAnalysis);
    </script>
</body>
</html>
