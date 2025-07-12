<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Chat AI - Gemma</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            background-color: #f9f9f9;
        }

        #messages {
            margin-bottom: 1rem;
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 8px;
            background-color: #fff;
        }

        .bubble {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow-wrap: break-word;
            word-break: break-word;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Sedikit shadow untuk efek 3D */
        }

        .user {
            background: #d0ebff;
            text-align: right;
            margin-left: auto; /* Untuk rata kanan */
            max-width: 80%; /* Batasi lebar bubble user */
        }

        .ai-content {
            background: #f1f1f1;
            font-family: system-ui, sans-serif;
            white-space: pre-line; /* Mempertahankan baris baru dari respons AI */
            line-height: 1.6;
            font-size: 15px;
            text-align: left; /* Rata kiri untuk AI */
            margin-right: auto; /* Untuk rata kiri */
            max-width: 80%; /* Batasi lebar bubble AI */
        }

        /* Gaya untuk pesan error */
        .error-bubble {
            background-color: #ffe6e6; /* Warna latar merah muda */
            border: 1px solid #ff0000; /* Border merah */
            color: #ff0000; /* Teks merah */
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow-wrap: break-word;
            word-break: break-word;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Gaya untuk loading bubble */
        .loading-bubble {
            background: #ffffcc; /* Warna kuning muda */
            font-style: italic;
            color: #555;
            text-align: left;
            margin-right: auto;
            max-width: fit-content; /* Sesuai konten */
        }

        form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        input[type="text"] {
            flex-grow: 1;
            padding: 0.8rem; /* Sedikit lebih besar */
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 0.8rem 1.5rem; /* Sedikit lebih besar */
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Chat dengan Gemma</h1>
    <div id="messages"></div>

    <form id="chat-form">
        <input type="text" name="message" id="message" placeholder="Tulis pertanyaan..." required>
        <button type="submit">Kirim</button>
    </form>

    <script>
        const form = document.getElementById('chat-form');
        const messages = document.getElementById('messages');
        const delayPerChar = 15; // Delay per karakter untuk efek mengetik (ms) - Bisa disesuaikan

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const msgInput = document.getElementById('message');
            const msg = msgInput.value.trim();
            if (!msg) return;

            // Tampilkan pesan pengguna
            const userBubble = document.createElement('div');
            userBubble.className = 'bubble user';
            userBubble.innerText = msg;
            messages.appendChild(userBubble);
            msgInput.value = '';

            // Buat bubble loading
            const loadingBubble = document.createElement('div');
            loadingBubble.className = 'bubble loading-bubble';
            loadingBubble.textContent = '⏳ Mengetik jawaban...';
            messages.appendChild(loadingBubble);

            // Buat bubble untuk respons AI (kosong dulu)
            const aiResponseBubble = document.createElement('div');
            aiResponseBubble.className = 'bubble ai-content';
            aiResponseBubble.textContent = ''; // Inisialisasi kosong
            messages.appendChild(aiResponseBubble);

            // Gulir ke bawah agar pesan terbaru terlihat
            messages.scrollTop = messages.scrollHeight;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const res = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ message: msg })
                });

                // Hapus loading bubble setelah ada respons (baik sukses atau error)
                loadingBubble.remove();

                // --- Penanganan Error HTTP seperti 419 Page Expired atau 500 Internal Server Error ---
                if (!res.ok) {
                    const errorStatus = res.status;
                    let errorMessage = `Error HTTP ${errorStatus}`;

                    if (errorStatus === 419) {
                        errorMessage = 'Sesi Anda telah berakhir. Mohon refresh halaman dan coba lagi.';
                    } else if (errorStatus === 500) {
                        errorMessage = 'Terjadi kesalahan internal server. Mohon coba beberapa saat lagi.';
                    } else {
                        try {
                            const errorText = await res.text();
                            // Ambil sebagian awal dari teks error HTML untuk debug
                            const errorSnippet = errorText.replace(/<\/?[^>]+(>|$)/g, "").substring(0, 200); // Hapus tag HTML
                            errorMessage += `: ${errorSnippet}...`;
                        } catch (e) {
                            errorMessage += '. Gagal membaca detail error dari server.';
                        }
                    }
                    aiResponseBubble.textContent = errorMessage;
                    aiResponseBubble.classList.add('error-bubble');
                    console.error('HTTP Error:', res.status, errorMessage);
                    return; // Hentikan eksekusi lebih lanjut
                }
                // --- Akhir Penanganan Error HTTP ---

                // Pastikan respons adalah ReadableStream
                if (!res.body) {
                    aiResponseBubble.textContent = '❌ Streaming tidak tersedia.';
                    console.error('Server response body is null.');
                    return;
                }

                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let done = false;
                let currentPartialText = ''; // Menyimpan chunk yang baru diterima
                let accumulatedText = ''; // Menyimpan seluruh teks yang sudah ditampilkan di bubble AI

                // Fungsi untuk menampilkan teks ke bubble dengan delay
                const displayChunkWithTypingEffect = async (textToDisplay) => {
                    for (const char of textToDisplay) {
                        accumulatedText += char;
                        aiResponseBubble.innerText = accumulatedText; // Menggunakan innerText untuk keamanan dan format
                        messages.scrollTop = messages.scrollHeight;
                        await new Promise(resolve => setTimeout(resolve, delayPerChar));
                    }
                };

                while (!done) {
                    const { value, done: readerDone } = await reader.read();
                    done = readerDone;

                    if (value) {
                        const chunk = decoder.decode(value, { stream: true });
                        currentPartialText += chunk;

                        // Coba pecah chunk berdasarkan baris baru atau tanda baca
                        // Ini akan membuat output lebih rapi per kalimat/paragraf pendek
                        let lastBreakIndex = -1;
                        // Prioritaskan newline, lalu tanda baca. Pastikan tidak memotong karakter di tengah.
                        if (currentPartialText.includes('\n')) {
                            lastBreakIndex = currentPartialText.lastIndexOf('\n');
                        } else if (currentPartialText.includes('.')) {
                             lastBreakIndex = currentPartialText.lastIndexOf('.');
                        } else if (currentPartialText.includes('!')) {
                             lastBreakIndex = currentPartialText.lastIndexOf('!');
                        } else if (currentPartialText.includes('?')) {
                             lastBreakIndex = currentPartialText.C.lastIndexOf('?');
                        }

                        if (lastBreakIndex !== -1 && lastBreakIndex < currentPartialText.length - 1) { // -1 agar tidak mengirim '\n' atau '.' di akhir chunk
                            const segment = currentPartialText.substring(0, lastBreakIndex + 1); // +1 untuk menyertakan pemisah
                            await displayChunkWithTypingEffect(segment);
                            currentPartialText = currentPartialText.substring(lastBreakIndex + 1);
                        }
                    }
                }

                // Pastikan sisa teks yang mungkin ada di buffer terakhir ditampilkan
                if (currentPartialText.length > 0) {
                    await displayChunkWithTypingEffect(currentPartialText);
                }

            } catch (error) {
                // Pastikan loading bubble hilang jika ada error
                if (loadingBubble.parentNode) {
                    loadingBubble.remove();
                }
                aiResponseBubble.textContent = `❌ Error: ${error.message}. Periksa koneksi atau konsol browser.`;
                aiResponseBubble.classList.add('error-bubble');
                console.error('Streaming or network error:', error);
            }
        });
    </script>
</body>
</html>
