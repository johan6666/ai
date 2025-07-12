<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generator Rekomendasi AI</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            background-color: #f9f9f9;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #34495e;
        }

        select,
        input[type="text"],
        textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #fff;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-6.4H18.6c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2069.4c0%204.4%201.8%208.7%205.4%2012.9l132%20127.2c3.2%203.2%207%205.4%2011.6%205.4s8.4-2.2%2011.6-5.4l132-127.2c3.6-4.2%205.4-8.5%205.4-12.9z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 0.7em top 50%, 0 0;
            background-size: 0.65em auto;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        #recommendation-output-template, /* Template untuk output recommendation, disembunyikan */
        .hidden {
            display: none;
        }

        /* Gaya khusus untuk textarea output agar lebih jelas dapat diedit */
        .recommendation-textarea { /* Menggunakan kelas karena akan ada banyak */
            background-color: #ffffff;
            cursor: text;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            min-height: 150px;
            margin-top: 0.5rem;
        }

        .input-wrapper-flex {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .input-wrapper-flex .flex-grow {
            flex-grow: 1;
        }

        button {
            padding: 0.8rem 1.8rem;
            font-size: 18px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
            height: fit-content;
        }
        button.add-button {
            background-color: #007bff; /* Biru untuk tambah */
        }
        button.add-button:hover {
            background-color: #0056b3;
        }
        button.remove-button {
            background-color: #dc3545; /* Merah untuk hapus */
            font-size: 14px;
            padding: 0.5rem 1rem;
            margin-left: 10px;
            height: fit-content;
        }
        button.remove-button:hover {
            background-color: #c82333;
        }

        button:hover {
            background-color: #218838;
        }

        .loading-text {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            margin-top: 1rem;
        }

        .error-message {
            color: #dc3545;
            font-weight: bold;
            text-align: center;
            margin-top: 1rem;
        }

        .recommendation-slot {
            border: 1px solid #e0e0e0;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            background-color: #f8f8f8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative; /* Untuk positioning tombol hapus */
        }
        .recommendation-slot + .recommendation-slot {
            margin-top: 1.5rem; /* Jarak antar slot */
        }

        .recommendation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 0.5rem;
        }
        .recommendation-header h3 {
            margin: 0;
            color: #34495e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generator Rekomendasi AI</h1>

        <form id="recommendation-form">
            <div class="form-group">
                <label for="kode_temuan">Kode Temuan:</label>
                <select id="kode_temuan" required>
                    <option value="">-- Pilih Kode Temuan --</option>
                    <option value="Ketidakpatuhan terhadap Peraturan">Ketidakpatuhan terhadap Peraturan</option>
                    <option value="Kelemahan Sistem Pengendalian Intern">Kelemahan Sistem Pengendalian Intern</option>
                    <option value="Temuan 3E (Ekonomi, Efisiensi, Efektivitas)">Temuan 3E (Ekonomi, Efisiensi, Efektivitas)</option>
                    <option value="Sumber Daya Manusia">Sumber Daya Manusia</option>
                </select>
            </div>

            <div class="form-group hidden" id="combo2-group">
                <label for="sub_kategori1">Sub Kategori 1:</label>
                <select id="sub_kategori1">
                    <option value="">-- Pilih Sub Kategori 1 --</option>
                </select>
            </div>

            <div class="form-group hidden" id="combo3-group">
                <label for="sub_kategori2">Sub Kategori 2:</label>
                <select id="sub_kategori2">
                    <option value="">-- Pilih Sub Kategori 2 --</option>
                </select>
            </div>

            <div class="form-group hidden" id="judul-temuan-group">
                <label for="judul_temuan">Judul Temuan:</label>
                <input type="text" id="judul_temuan" placeholder="Contoh: Pemborosan Energi Listrik" required>
            </div>

            <div class="form-group hidden" id="uraian-temuan-group">
                <label for="uraian_temuan">Uraian Temuan:</label>
                <textarea id="uraian_temuan" placeholder="Jelaskan secara detail temuan Anda, misal: Penggunaan AC dan lampu kantor sering lupa dimatikan di malam hari dan akhir pekan, menyebabkan tagihan listrik melonjak." required></textarea>
            </div>

            <hr style="margin: 2rem 0; border-top: 1px dashed #ccc;">

            <h2>Daftar Rekomendasi</h2>
            <div id="recommendations-container">
                </div>

            <div class="form-group" style="text-align: center; margin-top: 2rem;">
                <button type="button" id="add-recommendation-slot" class="add-button">
                    + Tambah Rekomendasi
                </button>
            </div>

            <div class="form-group" style="text-align: center; margin-top: 2rem;">
                <button type="submit">
                    Buat Rekomendasi AI (untuk Semua Slot)
                </button>
            </div>


            <div id="loading-indicator" class="loading-text" style="display: none;">
                ⏳ AI sedang membuat rekomendasi...
            </div>
            <div id="error-display" class="error-message" style="display: none;">
                </div>
        </form>
    </div>

    <template id="recommendation-slot-template">
        <div class="recommendation-slot">
            <div class="recommendation-header">
                <h3>Rekomendasi #<span class="slot-number">1</span></h3>
                <button type="button" class="remove-button">Hapus</button>
            </div>

            <div class="form-group">
                <label for="kode_rekomendasi_X">Kode Rekomendasi:</label>
                <select class="kode-rekomendasi-select" required>
                    <option value="">-- Pilih Kode Rekomendasi --</option>
                    <option value="Bersifat Dapat Dinilai Dengan Uang">Bersifat Dapat Dinilai Dengan Uang</option>
                    <option value="Bersifat Finansial">Bersifat Finansial</option>
                    <option value="Bersifat Hukuman">Bersifat Hukuman</option>
                    <option value="Bersifat Keputusan Arbitrase">Bersifat Keputusan Arbitrase</option>
                    <option value="Pengakan Hukum">Pengakan Hukum</option>
                </select>
            </div>

            <div class="form-group hidden">
                <label for="sub_kode_rekomendasi_X">Sub Kode Rekomendasi:</label>
                <select class="sub-kode-rekomendasi-select">
                    <option value="">-- Pilih Sub Kode Rekomendasi --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Hasil Rekomendasi AI:</label>
                <textarea class="recommendation-textarea" rows="10" placeholder="Hasil rekomendasi akan muncul di sini dan bisa Anda edit."></textarea>
            </div>
        </div>
    </template>

    <script>
        const form = document.getElementById('recommendation-form');
        const kodeTemuanSelect = document.getElementById('kode_temuan');
        const subKategori1Group = document.getElementById('combo2-group');
        const subKategori1Select = document.getElementById('sub_kategori1');
        const subKategori2Group = document.getElementById('combo3-group');
        const subKategori2Select = document.getElementById('sub_kategori2');
        const judulTemuanGroup = document.getElementById('judul-temuan-group');
        const judulTemuanInput = document.getElementById('judul_temuan');
        const uraianTemuanGroup = document.getElementById('uraian-temuan-group');
        const uraianTemuanInput = document.getElementById('uraian_temuan');

        const recommendationsContainer = document.getElementById('recommendations-container');
        const addRecommendationSlotButton = document.getElementById('add-recommendation-slot');
        const recommendationSlotTemplate = document.getElementById('recommendation-slot-template');

        const loadingIndicator = document.getElementById('loading-indicator');
        const errorDisplay = document.getElementById('error-display');
        const delayPerChar = 15; // Kecepatan efek mengetik (ms)

        // Counter untuk ID unik slot rekomendasi
        let recommendationSlotCounter = 0;

        // --- Data untuk Cascading Dropdown Temuan (Awal) ---
        const temuanOptionsData = {
            'Ketidakpatuhan terhadap Peraturan': {
                'Peraturan Keuangan': {
                    'Pelanggaran Prosedur Pengadaan': null,
                    'Keterlambatan Pelaporan Keuangan': null,
                    'Penyalahgunaan Anggaran': null
                },
                'Peraturan SDM': {
                    'Tidak Sesuai Kebijakan Cuti': null,
                    'Pelanggaran Kode Etik': null
                },
                'Peraturan Lingkungan': {
                    'Pembuangan Limbah Tidak Sesuai': null
                }
            },
            'Kelemahan Sistem Pengendalian Intern': {
                'Prosedur Lemah': {
                    'Tidak Ada Pemisahan Tugas': null,
                    'Proses Otorisasi Kurang': null
                },
                'Sistem IT': {
                    'Sistem Keamanan Data Lemah': null,
                    'Akses Tidak Teratur': null
                }
            },
            'Temuan 3E (Ekonomi, Efisiensi, Efektivitas)': {
                'Ekonomi': {
                    'Pembelian Barang/Jasa Terlalu Mahal': null
                },
                'Efisiensi': {
                    'Proses Kerja Berbelit-belit': null,
                    'Pemanfaatan Aset Tidak Optimal': null
                },
                'Efektivitas': {
                    'Program Tidak Mencapai Target': null,
                    'Kualitas Output Rendah': null
                }
            },
            'Sumber Daya Manusia': {
                'Kompetensi': {
                    'Kurangnya Pelatihan': null,
                    'Ketidaksesuaian Kualifikasi': null
                },
                'Kinerja': {
                    'Penurunan Produktivitas': null,
                    'Absensi Tinggi': null
                }
            }
        };

        // --- Data untuk Cascading Dropdown Rekomendasi (Baru) ---
        const rekomendasiOptionsData = {
            'Bersifat Dapat Dinilai Dengan Uang': {
                'Penghematan Biaya': null,
                'Peningkatan Pendapatan': null,
                'Optimalisasi Aset': null
            },
            'Bersifat Finansial': {
                'Perbaikan Sistem Akuntansi': null,
                'Pengelolaan Kas': null,
                'Pengendalian Anggaran': null
            },
            'Bersifat Hukuman': {
                'Sanksi Administrasi': null,
                'Denda': null,
                'Teguran Tertulis': null
            },
            'Bersifat Keputusan Arbitrase': {
                'Penyelesaian Sengketa': null,
                'Kompensasi': null
            },
            'Pengakan Hukum': {
                'Tindak Lanjut Pidana': null,
                'Proses Investigasi': null
            }
        };

        // --- Fungsi untuk mengisi dropdown (digunakan untuk Temuan dan Rekomendasi) ---
        function populateDropdown(selectElement, options) {
            selectElement.innerHTML = '<option value="">-- Pilih --</option>'; // Reset
            if (options) {
                for (const key in options) {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = key;
                    selectElement.appendChild(option);
                }
            }
        }

        // --- Fungsi untuk mereset dan menyembunyikan semua dropdown anak Temuan dan input temuan ---
        function resetAndHideAllTemuanChildren(level = 0) {
            if (level <= 0) {
                subKategori1Select.innerHTML = '<option value="">-- Pilih Sub Kategori 1 --</option>';
                subKategori1Group.classList.add('hidden');
            }
            if (level <= 1) {
                subKategori2Select.innerHTML = '<option value="">-- Pilih Sub Kategori 2 --</option>';
                subKategori2Group.classList.add('hidden');
            }
            if (level <= 2) {
                judulTemuanInput.value = '';
                judulTemuanGroup.classList.add('hidden');
                uraianTemuanInput.value = '';
                uraianTemuanGroup.classList.add('hidden');
            }
        }

        // --- Fungsi untuk mereset dan menyembunyikan dropdown anak Rekomendasi ---
        function resetAndHideRecommendationChildren(slotElement) {
            const subKodeRekomendasiSelect = slotElement.querySelector('.sub-kode-rekomendasi-select');
            const subKodeRekomendasiGroup = subKodeRekomendasiSelect.closest('.form-group');
            subKodeRekomendasiSelect.innerHTML = '<option value="">-- Pilih Sub Kode Rekomendasi --</option>';
            subKodeRekomendasiGroup.classList.add('hidden');
        }


        // --- Event Listener untuk Kode Temuan (Awal) ---
        kodeTemuanSelect.addEventListener('change', function() {
            const selectedKode = this.value;
            resetAndHideAllTemuanChildren(0);

            if (selectedKode && temuanOptionsData[selectedKode]) {
                populateDropdown(subKategori1Select, temuanOptionsData[selectedKode]);
                subKategori1Group.classList.remove('hidden');
            }
        });

        subKategori1Select.addEventListener('change', function() {
            const selectedKode = kodeTemuanSelect.value;
            const selectedSub1 = this.value;
            resetAndHideAllTemuanChildren(1);

            if (selectedKode && selectedSub1 && temuanOptionsData[selectedKode] && temuanOptionsData[selectedKode][selectedSub1]) {
                populateDropdown(subKategori2Select, temuanOptionsData[selectedKode][selectedSub1]);
                subKategori2Group.classList.remove('hidden');
            } else if (selectedKode && selectedSub1 && temuanOptionsData[selectedKode] && temuanOptionsData[selectedKode][selectedSub1] === null) {
                judulTemuanGroup.classList.remove('hidden');
                uraianTemuanGroup.classList.remove('hidden');
            }
        });

        subKategori2Select.addEventListener('change', function() {
            const selectedKode = kodeTemuanSelect.value;
            const selectedSub1 = subKategori1Select.value;
            const selectedSub2 = this.value;
            resetAndHideAllTemuanChildren(2);

            if (selectedKode && selectedSub1 && selectedSub2 &&
                temuanOptionsData[selectedKode] && temuanOptionsData[selectedKode][selectedSub1] &&
                temuanOptionsData[selectedKode][selectedSub1][selectedSub2] === null) {
                judulTemuanGroup.classList.remove('hidden');
                uraianTemuanGroup.classList.remove('hidden');
            }
        });

        // --- Fungsi untuk menambahkan slot rekomendasi baru ---
        addRecommendationSlotButton.addEventListener('click', function() {
            recommendationSlotCounter++;
            const newSlot = recommendationSlotTemplate.content.cloneNode(true);
            const slotElement = newSlot.querySelector('.recommendation-slot');
            slotElement.dataset.slotId = recommendationSlotCounter; // Berikan ID unik

            // Update nomor slot
            slotElement.querySelector('.slot-number').textContent = recommendationSlotCounter;

            // Dapatkan elemen select di slot baru
            const kodeRekomendasiSelect = slotElement.querySelector('.kode-rekomendasi-select');
            const subKodeRekomendasiSelect = slotElement.querySelector('.sub-kode-rekomendasi-select');
            const subKodeRekomendasiGroup = subKodeRekomendasiSelect.closest('.form-group');
            const recommendationTextarea = slotElement.querySelector('.recommendation-textarea');

            // Hapus atribut id agar tidak duplikat, dan tambahkan name jika ingin dikirim secara tradisional (kita pakai JSON.stringify)
            // Atau cukup gunakan kelas seperti yang sudah ada
            // Untuk memastikan ID unik untuk label 'for' (meskipun tidak dipakai di sini)
            kodeRekomendasiSelect.id = `kode_rekomendasi_${recommendationSlotCounter}`;
            subKodeRekomendasiSelect.id = `sub_kode_rekomendasi_${recommendationSlotCounter}`;
            slotElement.querySelector('label[for^="kode_rekomendasi_X"]').setAttribute('for', `kode_rekomendasi_${recommendationSlotCounter}`);
            slotElement.querySelector('label[for^="sub_kode_rekomendasi_X"]').setAttribute('for', `sub_kode_rekomendasi_${recommendationSlotCounter}`);
            recommendationTextarea.id = `recommendation_output_${recommendationSlotCounter}`;


            // Tambahkan event listener untuk combobox rekomendasi di slot ini
            kodeRekomendasiSelect.addEventListener('change', function() {
                const selectedKode = this.value;
                resetAndHideRecommendationChildren(slotElement); // Reset anak di slot ini

                if (selectedKode && rekomendasiOptionsData[selectedKode]) {
                    populateDropdown(subKodeRekomendasiSelect, rekomendasiOptionsData[selectedKode]);
                    subKodeRekomendasiGroup.classList.remove('hidden');
                }
            });

            // Event listener untuk tombol hapus
            slotElement.querySelector('.remove-button').addEventListener('click', function() {
                slotElement.remove();
                updateSlotNumbers(); // Perbarui nomor slot setelah penghapusan
            });

            recommendationsContainer.appendChild(newSlot);
            updateSlotNumbers(); // Perbarui nomor slot saat menambahkan
        });

        // Fungsi untuk memperbarui nomor slot secara berurutan
        function updateSlotNumbers() {
            const slots = recommendationsContainer.querySelectorAll('.recommendation-slot');
            slots.forEach((slot, index) => {
                slot.querySelector('.slot-number').textContent = index + 1;
                // Opsional: perbarui juga ID input dan label jika diperlukan untuk konsistensi
                // Tapi untuk kebutuhan pengiriman data sebagai array, tidak terlalu krusial
            });
        }


        // --- Event Listener untuk Form Submit (MODIFIKASI BESAR DI SINI!) ---
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const judulTemuan = judulTemuanInput.value.trim();
            const uraianTemuan = uraianTemuanInput.value.trim();

            const kodeTemuan = kodeTemuanSelect.value;
            const subKategori1 = subKategori1Select.value;
            const subKategori2 = subKategori2Select.value;

            // Validasi awal: pastikan info temuan utama terisi
            if (!judulTemuan || !uraianTemuan || !kodeTemuan ||
                (subKategori1Group.classList.contains('hidden') === false && !subKategori1) ||
                (subKategori2Group.classList.contains('hidden') === false && !subKategori2)
            ) {
                errorDisplay.textContent = 'Mohon lengkapi semua pilihan dan input temuan utama terlebih dahulu.';
                errorDisplay.style.display = 'block';
                return;
            }

            // Kumpulkan data dari semua slot rekomendasi
            const recommendationSlots = recommendationsContainer.querySelectorAll('.recommendation-slot');
            const recommendationsData = [];

            if (recommendationSlots.length === 0) {
                 errorDisplay.textContent = 'Mohon tambahkan setidaknya satu slot rekomendasi.';
                 errorDisplay.style.display = 'block';
                 return;
            }

            let allSlotsValid = true;
            recommendationSlots.forEach(slot => {
                const kodeRekomendasiSelect = slot.querySelector('.kode-rekomendasi-select');
                const subKodeRekomendasiSelect = slot.querySelector('.sub-kode-rekomendasi-select');
                const subKodeRekomendasiGroup = subKodeRekomendasiSelect.closest('.form-group');

                const kodeRekomendasi = kodeRekomendasiSelect.value;
                const subKodeRekomendasi = subKodeRekomendasiSelect.value;

                // Validasi setiap slot rekomendasi
                if (!kodeRekomendasi || (subKodeRekomendasiGroup.classList.contains('hidden') === false && !subKodeRekomendasi)) {
                    allSlotsValid = false;
                    errorDisplay.textContent = `Mohon lengkapi semua pilihan Kode Rekomendasi di setiap slot.`;
                    errorDisplay.style.display = 'block';
                    // Kita bisa menandai slot yang invalid secara visual di sini juga
                    return; // Keluar dari forEach
                }

                recommendationsData.push({
                    kode_rekomendasi: kodeRekomendasi,
                    sub_kode_rekomendasi: subKodeRekomendasi // Akan kosong jika tidak ada sub
                });
            });

            if (!allSlotsValid) {
                return; // Berhenti jika ada slot yang tidak valid
            }


            // Reset tampilan output dan siapkan loading
            // Kosongkan semua textarea rekomendasi sebelum mulai
            recommendationSlots.forEach(slot => {
                slot.querySelector('.recommendation-textarea').value = '';
            });
            errorDisplay.style.display = 'none';
            loadingIndicator.style.display = 'block';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const res = await fetch('/generate-recommendation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        judul_temuan: judulTemuan,
                        uraian_temuan: uraianTemuan,
                        kode_temuan_kategori: kodeTemuan,
                        sub_kategori_1_data: subKategori1,
                        sub_kategori_2_data: subKategori2,
                        // Kirim array data rekomendasi yang diminta
                        recommendations_to_generate: recommendationsData
                    })
                });

                loadingIndicator.style.display = 'none'; // Sembunyikan loading setelah respons

                if (!res.ok) {
                    let errorMessage = `Terjadi kesalahan. Kode: ${res.status}`;
                    if (res.status === 419) {
                        errorMessage = 'Sesi Anda telah berakhir. Mohon refresh halaman dan coba lagi.';
                    } else if (res.status === 500) {
                        errorMessage = 'Terjadi kesalahan internal server. Mohon coba beberapa saat lagi.';
                    } else {
                        const errorText = await res.text();
                        const errorSnippet = errorText.replace(/<\/?[^>]+(>|$)/g, "").substring(0, 200);
                        errorMessage += `: ${errorSnippet}...`;
                    }
                    errorDisplay.textContent = errorMessage;
                    errorDisplay.style.display = 'block';
                    console.error('HTTP Error:', res.status, errorMessage);
                    return;
                }

                if (!res.body) {
                    errorDisplay.textContent = '❌ Streaming tidak tersedia dari server.';
                    errorDisplay.style.display = 'block';
                    console.error('Server response body is null.');
                    return;
                }

                // Handle streaming response for multiple recommendations
                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let done = false;
                let currentSlotIndex = 0;
                let currentAccumulatedText = '';

                // Mapping slot index to actual textarea elements
                const outputTextareas = recommendationsContainer.querySelectorAll('.recommendation-textarea');

                const displayCharWithTypingEffect = async (char) => {
                    if (outputTextareas[currentSlotIndex]) {
                        currentAccumulatedText += char;
                        outputTextareas[currentSlotIndex].value = currentAccumulatedText;
                        if (outputTextareas[currentSlotIndex].scrollHeight > outputTextareas[currentSlotIndex].clientHeight) {
                            outputTextareas[currentSlotIndex].scrollTop = outputTextareas[currentSlotIndex].scrollHeight;
                        }
                    }
                    await new Promise(resolve => setTimeout(resolve, delayPerChar));
                };

                while (!done) {
                    const { value, done: readerDone } = await reader.read();
                    done = readerDone;

                    if (value) {
                        const chunk = decoder.decode(value, { stream: true });
                        // Coba deteksi marker untuk rekomendasi baru
                        const marker = "[[REKOMENDASI_BARU]]";
                        let startIndex = 0;
                        let markerIndex;

                        while ((markerIndex = chunk.indexOf(marker, startIndex)) !== -1) {
                            // Proses teks sebelum marker
                            for (let i = startIndex; i < markerIndex; i++) {
                                await displayCharWithTypingEffect(chunk[i]);
                            }

                            // Pindah ke slot rekomendasi berikutnya
                            currentSlotIndex++;
                            currentAccumulatedText = ''; // Reset untuk slot baru

                            startIndex = markerIndex + marker.length;
                        }

                        // Proses sisa chunk setelah marker terakhir (atau jika tidak ada marker)
                        for (let i = startIndex; i < chunk.length; i++) {
                            await displayCharWithTypingEffect(chunk[i]);
                        }
                    }
                }

            } catch (error) {
                if (loadingIndicator.parentNode) {
                    loadingIndicator.style.display = 'none';
                }
                errorDisplay.textContent = `❌ Error: ${error.message}. Periksa koneksi atau konsol browser.`;
                errorDisplay.style.display = 'block';
                console.error('Streaming or network error:', error);
            }
        });

        // Initialize with one recommendation slot
        addRecommendationSlotButton.click();
    </script>
</body>
</html>
