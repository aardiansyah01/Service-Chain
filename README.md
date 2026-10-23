Catatan demo: Panduan ini ditujukan untuk menjalankan aplikasi pada komputer lokal. Data pada Hardhat lokal bukan data mainnet dan tidak menggunakan ETH sungguhan. Setiap transaksi blockchain memerlukan konfirmasi wallet.

Teknologi
- Laravel / PHP — routing, autentikasi, validasi, dashboard, dan proses backend.
- MySQL — akun pengguna, relasi aplikasi, metadata, serta informasi sinkronisasi transaksi.
- Solidity ^0.8.34 — smart contract ServiceChain.sol.
- Hardhat 3 — menjalankan blockchain lokal dan deployment smart contract.
- Ethers.js 6 — menghubungkan frontend dan backend dengan smart contract melalui JSON-RPC.
- MetaMask — menghubungkan wallet serta menyetujui transaksi yang mengubah data on-chain.
- Vite / npm — membangun dan menjalankan aset frontend Laravel.

Peran Pengguna
1. Owner (Pemilik Kendaraan)
Owner dapat mendaftar dan masuk ke aplikasi, menghubungkan wallet ketika diperlukan, mendaftarkan kendaraan, melihat daftar/detail kendaraan, dan meninjau riwayat servis kendaraan yang tercatat. Aksi yang menulis data ke blockchain harus menggunakan wallet yang sesuai dengan pemilik kendaraan dan disetujui melalui MetaMask.

2. Workshop (Bengkel)
Bengkel dapat mendaftar, melengkapi data bengkel, dan menghubungkan wallet. Setelah pendaftaran, status bengkel perlu diverifikasi oleh admin. Bengkel yang belum aktif tidak dapat menambahkan catatan servis ke smart contract. Bengkel aktif dapat membuat catatan servis, melihat daftar catatannya, dan membatalkan catatan yang dibuatnya sesuai aturan kontrak.
Pembatalan bukan penghapusan permanen: data servis dan hash tetap ada pada blockchain, sementara statusnya berubah menjadi cancelled.

3. Admin Website
Admin mengelola aplikasi melalui dashboard admin, melihat ringkasan, mengelola pengguna, serta meninjau dan menyetujui, menolak, atau menonaktifkan pendaftaran bengkel. Persetujuan bengkel pada aplikasi juga perlu tersinkron dengan status bengkel di smart contract.

4. Visitor (Pengunjung)
Visitor dapat membuka halaman verifikasi publik tanpa membuat akun dan tanpa menghubungkan MetaMask. Pengunjung dapat mencari kendaraan menggunakan Vehicle ID, melihat informasi kendaraan dan riwayat servis dari blockchain, lalu memeriksa hash catatan servis.

Fitur Utama
- Registrasi dan login sesuai peran pengguna.
- Pengelolaan akun dan status bengkel oleh admin website.
- Koneksi wallet MetaMask untuk aksi blockchain.
- Pendaftaran dan pembacaan data kendaraan dari smart contract.
- Pencatatan servis oleh bengkel yang sudah aktif.
- Riwayat servis berdasarkan kendaraan dan pembacaan detail catatan berdasarkan Service ID.
- Pembatalan catatan servis dengan jejak audit tetap tersimpan di blockchain.
- Verifikasi publik tanpa login melalui halaman /verifikasi.
- Pengecekan hash catatan servis untuk membandingkan input pengunjung dengan hash pada blockchain.

Gambaran Alur Aplikasi
1. Pengguna mendaftar dan login. Akun, autentikasi, dan metadata aplikasi dikelola Laravel dengan database MySQL.
2. Bengkel menghubungkan wallet. Wallet menjadi identitas yang digunakan kontrak untuk mengenali bengkel. Pendaftaran awal bengkel memiliki status PENDING.
3. Admin memverifikasi bengkel. Admin website menyetujui/menolak pendaftaran. Bengkel harus berstatus ACTIVE pada smart contract agar bisa menulis catatan servis.
4. Owner mendaftarkan kendaraan. Data kendaraan yang menjadi bagian dari transaksi dicatat pada smart contract. Wallet pemanggil transaksi menjadi pemilik awal yang tercatat on-chain.
5. Bengkel aktif mencatat servis. Bengkel mengisi data servis, menyetujui transaksi dari wallet, lalu aplikasi memverifikasi transaksi dan menyinkronkan metadata terkait ke MySQL.
6. Riwayat dibaca dan diperiksa. Owner, bengkel, atau visitor dapat melihat data yang tersedia sesuai halaman dan kewenangannya. Halaman visitor mengambil data kendaraan dan servis dari smart contract.
7. Jika catatan perlu dibatalkan, bengkel pembuat catatan atau admin kontrak dapat mengirim transaksi cancelServiceRecord. Catatan tidak dihapus; status pembatalannya dapat diperiksa kembali.

Persiapan

Pastikan perangkat sudah memiliki:
- Git.
- PHP dan Composer dengan versi yang sesuai dengan composer.json proyek.
- Node.js dan npm dengan versi yang sesuai dengan proyek.
- MySQL (misalnya melalui XAMPP).
- Ekstensi browser MetaMask.

Cara Menjalankan Aplikasi :

1. Ambil kode dan pasang dependensi
Clone repositori, kemudian masuk ke folder proyek:
git clone <URL_REPOSITORY>
cd <FOLDER_PROYEK>
composer install
npm install

2. Siapkan konfigurasi Laravel dan MySQL
Buat file .env

Windows PowerShell:
Copy-Item .env.example .env
Linux/macOS/Git Bash:
cp .env.example .env

Buat database MySQL kosong, kemudian isi konfigurasi berikut di .env sesuai database lokal:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=

Berikutnya jalankan:
php artisan key:generate
php artisan migrate

4. Jalankan blockchain lokal Hardhat
Buka terminal baru di folder proyek yang berisi konfigurasi Hardhat:
npx hardhat node

Biarkan terminal ini tetap berjalan.

6. Deploy smart contract
Buka terminal kedua pada folder proyek Hardhat
lalu jalankan perintah untuk deploy

Setelah deployment berhasil, catat alamat kontrak yang ditampilkan di terminal.

5. Atur alamat RPC dan smart contract
Pada .env Laravel, atur RPC URL dan alamat kontrak:
VITE_SERVICECHAIN_RPC_URL=http://127.0.0.1:8545
VITE_SERVICECHAIN_CONTRACT_ADDRESS=ALAMAT_KONTRAK_HASIL_DEPLOY

SERVICECHAIN_RPC_URL=http://127.0.0.1:8545
SERVICECHAIN_CONTRACT_ADDRESS=ALAMAT_KONTRAK_HASIL_DEPLOY
SERVICECHAIN_ADMIN_PRIVATE_KEY=PRIVATE_KEY_AKUN_DEPLOYER_LOKAL

lalu jalankan :
php artisan config:clear
npm run build

6. Jalankan Laravel
Buka terminal berikutnya di folder aplikasi Laravel:
php artisan serve

Untuk demo, terminal berikut perlu tetap berjalan bersamaan:
Terminal	Perintah	Fungsi
1	npx hardhat node	Menjalankan blockchain lokal
2	Perintah npx hardhat ignition deploy ... saat deployment diperlukan	Deploy kontrak pada node lokal
3	npm run dev
4	php artisan serve	Menjalankan website Laravel


Penting: Blockchain lokal Hardhat menyimpan state di memori node. Jika node dihentikan lalu dimulai ulang, data blockchain sebelumnya tidak tersedia di node yang baru. Deploy kembali smart contract dan perbarui alamat kontrak pada konfigurasi jika diperlukan. Data di MySQL tidak otomatis ikut terhapus, sehingga data sinkronisasi perlu diperiksa saat memulai ulang lingkungan lokal.

Menghubungkan MetaMask
MetaMask digunakan untuk transaksi blockchain, Ikuti langkah berikut:
1. Pasang dan buka ekstensi MetaMask pada browser.
2. Pastikan npx hardhat node sedang berjalan.
3. Buka ServiceChain, masuk ke akun pengguna yang sesuai, lalu tekan tombol Hubungkan Wallet / Connect Wallet ketika tersedia.
4. Pilih akun yang sesuai di MetaMask dan setujui permintaan koneksi.
5. Saat melakukan aksi yang menulis data ke blockchain, periksa jaringan dan akun aktif, lalu konfirmasi transaksi di MetaMask. Tunggu transaksi selesai sebelum menutup halaman.
Jika wallet meminta berpindah jaringan, pilih jaringan Hardhat lokal yang sama dengan tempat kontrak di-deploy. Wallet bengkel juga harus sama dengan wallet yang terdaftar sebagai bengkel di smart contract. Untuk aksi yang dibatasi pemilik kendaraan, wallet yang digunakan harus sesuai dengan alamat pemilik yang tersimpan on-chain.

Cara Mencoba Aplikasi
Urutan berikut disarankan untuk demo dari awal

A. Uji sebagai admin website
1. Buka halaman login dan masuk menggunakan akun admin@mail.com pass : 12345678.
2. Buka dashboard admin dan tinjau ringkasan pengguna, kendaraan, bengkel, serta catatan servis.
3. Buka menu pengguna untuk mencari atau meninjau data pengguna.
4. Buka menu bengkel untuk memeriksa pendaftaran. Setujui bengkel yang datanya valid atau gunakan opsi penolakan/nonaktifkan sesuai kebutuhan uji.
5. Pastikan status bengkel pada aplikasi dan smart contract konsisten sebelum menguji pembuatan servis.

B. Uji sebagai owner
1. Buat akun owner atau gunakan akun uji yang tersedia, lalu login.
2. Hubungkan wallet MetaMask yang ingin menjadi pemilik kendaraan.
3. Buka menu kendaraan dan daftarkan kendaraan uji.
4. Saat MetaMask muncul, periksa akun serta jaringan, lalu konfirmasi transaksi.
5. Periksa kembali kendaraan di dashboard/detail dan coba halaman riwayat servis setelah bengkel menambahkan catatan.

C. Uji sebagai bengkel
1. Daftarkan akun dengan peran bengkel dan lengkapi informasi bengkel.
2. Hubungkan wallet yang akan digunakan sebagai identitas bengkel.
3. Tunggu admin memeriksa pendaftaran dan menyetujui bengkel.
4. Pastikan status bengkel aktif dan wallet MetaMask yang digunakan sesuai dengan wallet bengkel yang terdaftar.
5. Buka menu catatan servis, pilih kendaraan yang tersedia, kemudian isi data servis.
6. Konfirmasi transaksi pada MetaMask dan tunggu sampai berhasil. Aplikasi memeriksa transaksi sebelum metadata terkait disinkronkan ke MySQL.
7. Untuk menguji pembatalan, gunakan catatan servis uji dan tombol Batalkan jika tersedia. Status akan berubah menjadi dibatalkan; record tetap dapat ditelusuri di blockchain.

D. Uji sebagai visitor
1. Buka /verifikasi atau pilih halaman verifikasi publik.
2. Masukkan Vehicle ID yang benar-benar terdaftar pada smart contract di jaringan aktif.
3. Buka detail kendaraan atau catatan servis yang ditampilkan.
4. Gunakan fitur pemeriksaan hash untuk membandingkan hash yang dimasukkan dengan hash yang tersimpan pada blockchain.
