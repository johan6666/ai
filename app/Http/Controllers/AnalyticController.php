<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Http; // Tambahkan ini untuk memanggil Ollama API
use Symfony\Component\HttpFoundation\StreamedResponse; // Untuk streaming response dari Ollama

class AnalyticController extends Controller
{
    // Method untuk menampilkan view dashboard
    public function showAnalysisDashboard()
    {
        return view('analysis_dashboard'); // Mengarahkan ke resources/views/analysis_dashboard.blade.php
    }

    // Method untuk memproses analisis data dan AI (dipanggil via API)
    public function analyzeMotorSales(Request $request)
    {
        // 1. Definisikan PATH ke Python Interpreter Anda (di dalam virtual environment)
        $pythonPath = base_path('venv_analisis\Scripts\python.exe');

        // 2. Definisikan PATH ke Skrip Python Anda
        $scriptPath = storage_path('app\scripts\analyze_motor_sales.py');

        // 3. Definisikan PATH ke Dataset CSV Anda
        $datasetPath = storage_path('app\datasets\penjualan_motor.csv'); // Pastikan ini benar

        // --- Validasi Awal ---
        if (!file_exists($pythonPath)) {
            return response()->json(['error' => 'Python interpreter tidak ditemukan: ' . $pythonPath . '. Pastikan virtual environment aktif dan path benar.'], 500);
        }
        if (!file_exists($scriptPath)) {
            return response()->json(['error' => 'Skrip Python tidak ditemukan: ' . $scriptPath], 500);
        }
        if (!file_exists($datasetPath)) {
            return response()->json(['error' => 'Dataset CSV tidak ditemukan: ' . $datasetPath], 500);
        }

        // 4. Buat dan Jalankan Proses Python
        $process = new Process([$pythonPath, $scriptPath, $datasetPath]);
        $process->setTimeout(600); // Set timeout 10 menit

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $pythonOutput = $process->getOutput();
            $analysisResult = json_decode($pythonOutput, true);

            // Validasi Output JSON dari Python
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Gagal mengurai JSON dari Python',
                    'raw_output' => $pythonOutput
                ], 500);
            }
            if (isset($analysisResult['error'])) {
                return response()->json([
                    'error' => 'Skrip Python melaporkan error',
                    'message' => $analysisResult['error']
                ], 500);
            }

            // -----------------------------------------------------------
            // Mengirim Ringkasan ke Ollama (Gemma)
            // -----------------------------------------------------------

            $qualitativeSummary = $analysisResult['qualitative_summary_for_ai'];

            // Instruksi untuk Gemma: minta insight dari ringkasan analisis data
            $gemmaPrompt = "Berikut adalah ringkasan analisis data penjualan motor:\n\n" . $qualitativeSummary . "\n\n";
            $gemmaPrompt .= "Mohon berikan insight, interpretasi, atau implikasi strategis singkat mengenai tren penjualan ini. Identifikasi juga potensi peluang atau risiko yang terlihat dari data. Berikan respons dalam 2-3 paragraf, dan bersifat profesional.";

            $ollamaStream = Http::timeout(600)
                ->withOptions([
                    'stream' => true,
                ])
                ->post('http://localhost:11434/api/generate', [
                    'model' => 'gemma3', // Atau model lain yang Anda pilih (llama3, phi3)
                    'prompt' => $gemmaPrompt,
                    'stream' => true,
                    'options' => [
                        'num_predict' => 500, // Cukup untuk 2-3 paragraf insight
                    ],
                ]);

            // Mengembalikan hasil analisis Python dan streaming insight dari Gemma
            return new StreamedResponse(function () use ($ollamaStream, $analysisResult) {
                // Pertama, kirim hasil analisis Python (non-streaming)
                echo "data: " . json_encode([
                    'type' => 'python_analysis',
                    'data' => $analysisResult
                ]) . "\n\n";
                flush();

                // Lalu, stream respons dari Ollama
                $body = $ollamaStream->toPsrResponse()->getBody();
                while (!$body->eof()) {
                    $chunk = $body->read(1024);
                    $lines = explode("\n", trim($chunk));
                    foreach ($lines as $line) {
                        if (empty($line)) continue;
                        $data = json_decode($line, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($data['response'])) {
                            echo "data: " . json_encode([
                                'type' => 'gemma_insight',
                                'text' => $data['response']
                            ]) . "\n\n";
                            flush();
                        } elseif (isset($data['error'])) {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => "Gemma Error: " . $data['error']
                            ]) . "\n\n";
                            flush();
                        }
                    }
                }
                echo "data: " . json_encode(['type' => 'end_stream']) . "\n\n";
                flush();

            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);


        } catch (ProcessFailedException $exception) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat menjalankan skrip Python',
                'message' => $exception->getMessage(),
                'output_error' => $process->getErrorOutput()
            ], 500);
        }
    }
}
