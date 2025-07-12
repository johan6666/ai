<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    // public function chat(Request $request)
    // {
    //     $prompt = $request->input('message');

    //     $ollamaStream = Http::timeout(120)
    //         ->withOptions(['stream' => true])
    //         ->post('http://localhost:11434/api/generate', [
    //             'model' => 'gemma3',
    //             'prompt' => $prompt,
    //             'stream' => true,
    //             'options' => [
    //                 'num_predict' => 2000,
    //             ],
    //         ]);

    //     return new StreamedResponse(function () use ($ollamaStream) {
    //         while (@ob_end_flush());
    //         ini_set('zlib.output_compression', 'Off');

    //         $body = $ollamaStream->toPsrResponse()->getBody();

    //         while (!$body->eof()) {
    //             $chunk = $body->read(1024);
    //             $lines = explode("\n", trim($chunk));

    //             foreach ($lines as $line) {
    //                 if (empty($line)) continue;
    //                 $data = json_decode($line, true);

    //                 if (json_last_error() === JSON_ERROR_NONE && isset($data['response'])) {
    //                     echo $data['response'];
    //                     echo str_repeat(' ', 256); // paksa flush
    //                     if (ob_get_level() > 0) ob_flush();
    //                     flush();
    //                     usleep(5000); // delay kecil biar lebih natural
    //                 } elseif (isset($data['error'])) {
    //                     echo "ERROR: " . $data['error'];
    //                     if (ob_get_level() > 0) ob_flush();
    //                     flush();
    //                 }
    //             }
    //         }
    //     }, 200, [
    //         'Content-Type' => 'text/plain; charset=utf-8',
    //         'Cache-Control' => 'no-cache',
    //         'X-Accel-Buffering' => 'no',
    //     ]);
    // }
    public function chat(Request $request)
    {
        // set_time_limit(600); // Pertahankan ini jika max_execution_time di php.ini lebih kecil

        $prompt = $request->input('message');

        $ollamaStream = Http::timeout(600) // Tingkatkan timeout ke 600 detik (10 menit) untuk keamanan
            ->withOptions([
                'stream' => true,
            ])
            ->post('http://localhost:11434/api/generate', [
                'model' => 'gemma3',
                'prompt' => $prompt,
                'stream' => true,
                'options' => [
                    'num_predict' => 2000,
                ],
            ]);

        return new StreamedResponse(function () use ($ollamaStream) {
            // Hapus atau komentari ini: @ob_end_flush() dan ini_set('zlib.output_compression', 'Off');
            // karena StreamedResponse dan konfigurasi Nginx/PHP-CGI sudah mengurus ini.
            // Memaksa ob_end_flush() di awal bisa menyebabkan error jika tidak ada buffer aktif.
            // while (@ob_end_flush());
            // ini_set('zlib.output_compression', 'Off');

            $body = $ollamaStream->toPsrResponse()->getBody();

            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $lines = explode("\n", trim($chunk));

                foreach ($lines as $line) {
                    if (empty($line)) continue;
                    $data = json_decode($line, true);

                    if (json_last_error() === JSON_ERROR_NONE && isset($data['response'])) {
                        echo $data['response'];
                        // Hapus baris di bawah ini. Kita tidak perlu memaksa flush dengan spasi atau sleep di backend
                        // karena frontend akan menangani buffering yang lebih cerdas.
                        // echo str_repeat(' ', 256); // Ini bisa jadi penyebab 'terpotong' jika frontend tidak handle whitespace
                        // if (ob_get_level() > 0) ob_flush(); // StreamedResponse sudah menangani flush
                        // flush();                            // StreamedResponse sudah menangani flush
                        // usleep(5000); // Delay di backend tidak diperlukan, frontend akan handle kecepatan tampilan
                    } elseif (isset($data['error'])) {
                        echo "ERROR: " . $data['error'];
                        // if (ob_get_level() > 0) ob_flush(); // StreamedResponse sudah menangani flush
                        // flush();                            // StreamedResponse sudah menangani flush
                    }
                }
            }
        }, 200, [
            // Ganti 'text/plain' menjadi 'text/event-stream' untuk kompatibilitas yang lebih baik
            // dengan Fetch API streaming (meskipun akan tetap bekerja dengan text/plain)
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            // X-Accel-Buffering: no sudah benar untuk Nginx
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // --- FUNGSI BARU UNTUK REKOMENDASI (MODIFIKASI) ---

    // Menampilkan view form rekomendasi
    public function recommendationIndex()
    {
        return view('recommendation'); // Nama file view baru
    }

    // Memproses input Judul Temuan dan Uraian Temuan, lalu menghasilkan rekomendasi
    public function generateRecommendation(Request $request)
    {
        $judulTemuan = $request->input('judul_temuan');
        $uraianTemuan = $request->input('uraian_temuan');

        $kodeTemuanKategori = $request->input('kode_temuan_kategori');
        $subKategori1Data = $request->input('sub_kategori_1_data');
        $subKategori2Data = $request->input('sub_kategori_2_data');

        // Ambil array dari rekomendasi yang akan digenerate
        $recommendationsToGenerate = $request->input('recommendations_to_generate');

        if (empty($judulTemuan) || empty($uraianTemuan) || empty($recommendationsToGenerate)) {
            return new StreamedResponse(function () {
                echo "Mohon lengkapi detail temuan dan tambahkan setidaknya satu rekomendasi yang akan digenerate.";
            }, 200, ['Content-Type' => 'text/plain']);
        }

        // Base prompt yang berisi informasi temuan umum
        $basePrompt = "Berdasarkan temuan berikut:\n";
        if (!empty($kodeTemuanKategori)) {
            $basePrompt .= "- Kategori Utama Temuan: " . $kodeTemuanKategori . "\n";
        }
        if (!empty($subKategori1Data)) {
            $basePrompt .= "- Sub Kategori 1: " . $subKategori1Data . "\n";
        }
        if (!empty($subKategori2Data)) {
            $basePrompt .= "- Sub Kategori 2: " . $subKategori2Data . "\n";
        }
        $basePrompt .= "- Judul Temuan: \"" . $judulTemuan . "\"\n";
        $basePrompt .= "- Uraian Temuan: \"" . $uraianTemuan . "\"\n\n";

        // Marker untuk memisahkan rekomendasi di stream
        $marker = "[[REKOMENDASI_BARU]]"; // Pastikan ini unik dan konsisten dengan frontend

        return new StreamedResponse(function () use ($basePrompt, $recommendationsToGenerate, $marker) {
            foreach ($recommendationsToGenerate as $index => $recData) {
                // Tambahkan marker sebelum setiap rekomendasi baru (kecuali yang pertama)
                if ($index > 0) {
                    echo $marker; // Kirim marker
                    // ob_flush(); // HAPUS BARIS INI
                    flush(); // Biarkan ini
                }

                // Bangun prompt spesifik untuk rekomendasi ini
                $specificPrompt = $basePrompt;
                $specificPrompt .= "Buatkan saya rekomendasi yang ";

                if (!empty($recData['kode_rekomendasi'])) {
                    $specificPrompt .= "bersifat " . strtolower($recData['kode_rekomendasi']);
                    if (!empty($recData['sub_kode_rekomendasi'])) {
                        $specificPrompt .= " dengan fokus pada " . strtolower($recData['sub_kode_rekomendasi']);
                    }
                } else {
                    $specificPrompt .= "relevan"; // Default jika tidak ada kategori rekomendasi dipilih
                }
                $specificPrompt .= ". Rekomendasi harus spesifik, dapat ditindaklanjuti, dan mudah dipahami.\n";


                $ollamaStream = Http::timeout(600)
                    ->withOptions([
                        'stream' => true,
                    ])
                    ->post('http://localhost:11434/api/generate', [
                        'model' => 'gemma3', // Pastikan ini model yang kamu gunakan
                        'prompt' => $specificPrompt,
                        'stream' => true,
                        'options' => [
                            'num_predict' => 280, // Perhatikan Anda set ke 10, yang akan menghasilkan respons sangat pendek
                        ],
                    ]);

                $body = $ollamaStream->toPsrResponse()->getBody();

                while (!$body->eof()) {
                    $chunk = $body->read(1024);
                    $lines = explode("\n", trim($chunk));

                    foreach ($lines as $line) {
                        if (empty($line)) continue;
                        $data = json_decode($line, true);

                        if (json_last_error() === JSON_ERROR_NONE && isset($data['response'])) {
                            echo $data['response'];
                            // ob_flush(); // HAPUS BARIS INI
                            flush(); // Penting untuk mengirim chunk ke browser segera
                        } elseif (isset($data['error'])) {
                            echo "ERROR: " . $data['error'];
                            // ob_flush(); // HAPUS BARIS INI
                            flush(); // Biarkan ini
                        }
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
